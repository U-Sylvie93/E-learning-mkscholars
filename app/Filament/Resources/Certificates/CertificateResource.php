<?php

namespace App\Filament\Resources\Certificates;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\Certificates\Pages\CreateCertificate;
use App\Filament\Resources\Certificates\Pages\EditCertificate;
use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCompletion;
use App\Models\User;
use App\Services\CertificateService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class CertificateResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = Certificate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'certificate_number';

    protected static ?string $navigationLabel = 'Certificates';

    protected static string|UnitEnum|null $navigationGroup = 'Credentials';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Student')
                    ->options(fn () => User::query()
                        ->where('role', User::ROLE_STUDENT)
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('certificate_number')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Generated automatically'),
                TextInput::make('verification_code')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Generated automatically'),
                TextInput::make('student_name')
                    ->maxLength(255)
                    ->placeholder('Filled from student automatically if blank'),
                TextInput::make('course_title')
                    ->maxLength(255)
                    ->placeholder('Filled from course automatically if blank'),
                TextInput::make('score')
                    ->label('Final Test Score')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->helperText('Leave blank to use the best submitted published Final Test score when available.'),
                TextInput::make('signer_name')
                    ->label('Signer name')
                    ->maxLength(255)
                    ->helperText('Optional name displayed below the certificate signature line.'),
                TextInput::make('signer_title')
                    ->label('Signer title')
                    ->maxLength(255)
                    ->helperText('Optional role, for example Director of Learning.'),
                FileUpload::make('signature_image_path')
                    ->label('Signature image')
                    ->helperText('Optional transparent PNG recommended. Allowed: PNG, JPG, JPEG, WebP. Max 2MB.')
                    ->disk('public')
                    ->directory('certificates/signatures')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->maxSize(2048)
                    ->downloadable()
                    ->openable(),
                Select::make('status')
                    ->required()
                    ->options([
                        Certificate::STATUS_PENDING => 'Pending',
                        Certificate::STATUS_ISSUED => 'Issued',
                        Certificate::STATUS_REJECTED => 'Rejected',
                        Certificate::STATUS_REVOKED => 'Revoked',
                    ])
                    ->default(Certificate::STATUS_PENDING)
                    ->disabled()
                    ->dehydrated(),
                DateTimePicker::make('issued_at')
                    ->default(now())
                    ->disabled()
                    ->dehydrated(),
                Textarea::make('rejection_reason')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?Certificate $record): bool => $record?->status === Certificate::STATUS_REJECTED),
                DateTimePicker::make('revoked_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('certificate_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('verification_code')
                    ->label('Verification code')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('student_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('course_title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('course.instructor.name')
                    ->label('Instructor')
                    ->placeholder('Not assigned')
                    ->toggleable(),
                TextColumn::make('score')
                    ->label('Final Test Score')
                    ->suffix('%')
                    ->placeholder('No score')
                    ->sortable(),
                TextColumn::make('signer_name')
                    ->label('Signer')
                    ->placeholder('Default signature')
                    ->searchable(),
                TextColumn::make('signature_image_path')
                    ->label('Signature image')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Uploaded' : 'Not uploaded')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Certificate::STATUS_PENDING => 'warning',
                        Certificate::STATUS_ISSUED => 'success',
                        Certificate::STATUS_REJECTED => 'danger',
                        Certificate::STATUS_REVOKED => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('issued_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('skills_count')
                    ->counts('skills')
                    ->label('Skills')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Certificate::STATUS_PENDING => 'Pending',
                        Certificate::STATUS_ISSUED => 'Issued',
                        Certificate::STATUS_REJECTED => 'Rejected',
                        Certificate::STATUS_REVOKED => 'Revoked',
                    ]),
                SelectFilter::make('course')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('issued_at', 'desc')
            ->recordActions([
                Action::make('issue')
                    ->label('Approve & issue')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Certificate $record): bool => self::canManageWorkflow() && in_array($record->status, [Certificate::STATUS_PENDING, Certificate::STATUS_REJECTED], true))
                    ->action(fn (Certificate $record) => app(CertificateService::class)->issue($record, auth()->user())),
                Action::make('reject')
                    ->color('danger')
                    ->form([
                        Textarea::make('reason')->label('Rejection reason')->maxLength(1000),
                    ])
                    ->visible(fn (Certificate $record): bool => self::canManageWorkflow() && $record->status === Certificate::STATUS_PENDING)
                    ->action(fn (Certificate $record, array $data) => app(CertificateService::class)->reject($record, auth()->user(), $data['reason'] ?? null)),
                Action::make('revoke')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Certificate $record): bool => self::canManageWorkflow() && $record->status === Certificate::STATUS_ISSUED)
                    ->action(fn (Certificate $record) => app(CertificateService::class)->revoke($record, auth()->user())),
                Action::make('verify')
                    ->label('Public verification')
                    ->url(fn (Certificate $record): string => route('certificates.verify', $record->verification_code))
                    ->openUrlInNewTab(),
                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->url(fn (Certificate $record): string => route('admin.certificates.download', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Certificate $record): bool => $record->status === Certificate::STATUS_ISSUED),
                EditAction::make()->visible(fn (): bool => ! self::isReadOnlyViewer()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => ! self::isReadOnlyViewer()),
                ]),
            ]);
    }

    public static function normalizeCertificateData(array $data, ?Certificate $record = null): array
    {
        $duplicate = Certificate::query()
            ->where('user_id', $data['user_id'])
            ->where('course_id', $data['course_id'])
            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'user_id' => 'This student already has a certificate record for this course.',
            ]);
        }

        $student = User::find($data['user_id']);
        $course = Course::find($data['course_id']);

        if ($course && ! $course->offersCertificate()) {
            throw ValidationException::withMessages([
                'course_id' => 'This course does not offer certificates.',
            ]);
        }

        $completion = CourseCompletion::query()
            ->where('user_id', $data['user_id'])
            ->where('course_id', $data['course_id'])
            ->first();

        if ($completion && ! $completion->is_eligible_for_certificate) {
            throw ValidationException::withMessages([
                'user_id' => 'This student is not marked eligible for a certificate for this course yet.',
            ]);
        }

        $data['student_name'] = filled($data['student_name'] ?? null) ? $data['student_name'] : ($student?->name ?? 'Student');
        $data['course_title'] = filled($data['course_title'] ?? null) ? $data['course_title'] : ($course?->title ?? 'Course');
        $data['score'] = filled($data['score'] ?? null)
            ? (int) $data['score']
            : Certificate::finalTestScoreFor($data['user_id'] ?? null, $data['course_id'] ?? null);
        $data['issued_at'] ??= now();

        if (($data['status'] ?? null) === Certificate::STATUS_REVOKED) {
            $data['revoked_at'] ??= now();
        } elseif (($data['status'] ?? null) === Certificate::STATUS_ISSUED) {
            $data['revoked_at'] = null;
        }

        return $data;
    }

    public static function canManageWorkflow(): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificates::route('/'),
            'create' => CreateCertificate::route('/create'),
            'edit' => EditCertificate::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\CertificateSettings;

use App\Filament\Resources\CertificateSettings\Pages\CreateCertificateSetting;
use App\Filament\Resources\CertificateSettings\Pages\EditCertificateSetting;
use App\Filament\Resources\CertificateSettings\Pages\ListCertificateSettings;
use App\Models\CertificateSetting;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CertificateSettingResource extends Resource
{
    protected static ?string $model = CertificateSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Credentials';

    protected static ?string $navigationLabel = 'Certificate Settings';

    public static function form(Schema $schema): Schema
    {
        $image = fn (FileUpload $field): FileUpload => $field
            ->disk('public')
            ->directory('certificates/official-assets')
            ->image()
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->maxSize(2048)
            ->downloadable()
            ->openable();

        return $schema->components([
            TextInput::make('organization_name')->required()->maxLength(255),
            TextInput::make('issuer_name')->required()->maxLength(255),
            TextInput::make('issuer_title')->required()->maxLength(255),
            $image(FileUpload::make('stamp_path')->label('Official organization stamp')),
            $image(FileUpload::make('admin_signature_path')->label('Issuer/admin signature')),
            $image(FileUpload::make('logo_path')->label('Optional certificate logo')),
            Textarea::make('certificate_footer_note')->rows(3)->maxLength(1000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('organization_name')->label('Organization'),
            TextColumn::make('issuer_name')->label('Issuer'),
            TextColumn::make('issuer_title'),
            TextColumn::make('stamp_path')->label('Stamp')->formatStateUsing(fn (?string $state): string => filled($state) ? 'Uploaded' : 'Missing')->badge(),
            TextColumn::make('admin_signature_path')->label('Signature')->formatStateUsing(fn (?string $state): string => filled($state) ? 'Uploaded' : 'Missing')->badge(),
        ])->recordActions([EditAction::make()]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny() && ! CertificateSetting::query()->exists();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificateSettings::route('/'),
            'create' => CreateCertificateSetting::route('/create'),
            'edit' => EditCertificateSetting::route('/{record}/edit'),
        ];
    }
}

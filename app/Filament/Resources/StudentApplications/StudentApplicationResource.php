<?php

namespace App\Filament\Resources\StudentApplications;

use App\Filament\Resources\StudentApplications\Pages\EditStudentApplication;
use App\Filament\Resources\StudentApplications\Pages\ListStudentApplications;
use App\Models\StudentApplication;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class StudentApplicationResource extends Resource
{
    protected static ?string $model = StudentApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $navigationLabel = 'Student Applications';

    protected static string|UnitEnum|null $navigationGroup = 'Opportunities';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('opportunity')
                    ->label('Opportunity')
                    ->content(fn (?StudentApplication $record): string => $record?->opportunity?->title ?? 'Opportunity'),
                Placeholder::make('student')
                    ->label('Student')
                    ->content(fn (?StudentApplication $record): string => $record?->user?->name ?? 'Student'),
                Select::make('status')
                    ->required()
                    ->options(self::statusOptions()),
                Textarea::make('notes')
                    ->rows(5)
                    ->columnSpanFull(),
                Placeholder::make('uploaded_documents')
                    ->label('Uploaded documents')
                    ->content(function (?StudentApplication $record): HtmlString {
                        if (! $record) {
                            return new HtmlString('No documents yet.');
                        }

                        $record->loadMissing('documents.studentDocument');

                        if ($record->documents->isEmpty()) {
                            return new HtmlString('No documents yet.');
                        }

                        $items = $record->documents
                            ->map(fn ($document): string => sprintf(
                                '<li><strong>%s</strong> - %s%s</li>',
                                e($document->document_name),
                                e(str_replace('_', ' ', $document->status)),
                                $document->studentDocument ? ' via '.e($document->studentDocument->title) : '',
                            ))
                            ->implode('');

                        return new HtmlString('<ul class="list-disc space-y-1 pl-5 text-sm">'.$items.'</ul>');
                    })
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('opportunity.title')
                    ->label('Opportunity')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('documents_count')
                    ->counts('documents')
                    ->label('Documents')
                    ->sortable(),
                TextColumn::make('notes')
                    ->limit(40)
                    ->placeholder('No notes'),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Draft'),
                TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not reviewed'),
            ])
            ->filters([
                SelectFilter::make('opportunity')
                    ->relationship('opportunity', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')->options(self::statusOptions()),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudentApplications::route('/'),
            'edit' => EditStudentApplication::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            StudentApplication::STATUS_DRAFT => 'Draft',
            StudentApplication::STATUS_SUBMITTED => 'Submitted',
            StudentApplication::STATUS_UNDER_REVIEW => 'Under review',
            StudentApplication::STATUS_APPROVED => 'Approved',
            StudentApplication::STATUS_REJECTED => 'Rejected',
            StudentApplication::STATUS_WITHDRAWN => 'Withdrawn',
        ];
    }
}

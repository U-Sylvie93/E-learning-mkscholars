<?php

namespace App\Filament\Resources\ApplicationDocuments;

use App\Filament\Resources\ApplicationDocuments\Pages\EditApplicationDocument;
use App\Filament\Resources\ApplicationDocuments\Pages\ListApplicationDocuments;
use App\Models\ApplicationDocument;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ApplicationDocumentResource extends Resource
{
    protected static ?string $model = ApplicationDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Application Documents';

    protected static string|UnitEnum|null $navigationGroup = 'Opportunities';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('application')
                    ->label('Application')
                    ->content(fn (?ApplicationDocument $record): string => $record?->studentApplication?->opportunity?->title ?? 'Application'),
                Placeholder::make('student')
                    ->label('Student')
                    ->content(fn (?ApplicationDocument $record): string => $record?->studentApplication?->user?->name ?? 'Student'),
                Placeholder::make('student_document')
                    ->label('Reusable document')
                    ->content(fn (?ApplicationDocument $record): string => $record?->studentDocument?->title ?? 'Not attached from document center'),
                TextInput::make('document_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('file_path')
                    ->disabled(),
                TextInput::make('external_link')
                    ->url()
                    ->maxLength(255),
                Select::make('status')
                    ->required()
                    ->options(self::statusOptions()),
                Textarea::make('admin_feedback')
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentApplication.opportunity.title')
                    ->label('Opportunity')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentApplication.user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('studentDocument.title')
                    ->label('Reusable document')
                    ->placeholder('Direct upload'),
                TextColumn::make('admin_feedback')
                    ->limit(40)
                    ->placeholder('No feedback'),
                TextColumn::make('uploaded_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Pending'),
            ])
            ->filters([
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
            'index' => ListApplicationDocuments::route('/'),
            'edit' => EditApplicationDocument::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            ApplicationDocument::STATUS_PENDING => 'Pending',
            ApplicationDocument::STATUS_UPLOADED => 'Uploaded',
            ApplicationDocument::STATUS_APPROVED => 'Approved',
            ApplicationDocument::STATUS_REJECTED => 'Rejected',
        ];
    }
}

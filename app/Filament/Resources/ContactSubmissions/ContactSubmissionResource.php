<?php

namespace App\Filament\Resources\ContactSubmissions;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\ContactSubmissions\Pages\EditContactSubmission;
use App\Filament\Resources\ContactSubmissions\Pages\ListContactSubmissions;
use App\Models\ContactSubmission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class ContactSubmissionResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = ContactSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Contact Messages';

    protected static string|UnitEnum|null $navigationGroup = 'Communication';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->disabled()->columnSpan(1),
            TextInput::make('email')->disabled()->columnSpan(1),
            TextInput::make('interest')->disabled()->columnSpan(1),
            Textarea::make('message')->disabled()->rows(6)->columnSpanFull(),
            Select::make('status')
                ->required()
                ->options([
                    ContactSubmission::STATUS_NEW => 'New',
                    ContactSubmission::STATUS_READ => 'Read',
                    ContactSubmission::STATUS_REPLIED => 'Replied',
                    ContactSubmission::STATUS_ARCHIVED => 'Archived',
                ]),
            Textarea::make('admin_notes')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('interest')->sortable(),
                TextColumn::make('message')->limit(60)->wrap(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    ContactSubmission::STATUS_NEW => 'New',
                    ContactSubmission::STATUS_READ => 'Read',
                    ContactSubmission::STATUS_REPLIED => 'Replied',
                    ContactSubmission::STATUS_ARCHIVED => 'Archived',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactSubmissions::route('/'),
            'edit' => EditContactSubmission::route('/{record}/edit'),
        ];
    }
}

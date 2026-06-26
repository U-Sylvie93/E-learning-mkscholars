<?php

namespace App\Filament\Resources\CertificateSkills;

use App\Filament\Resources\CertificateSkills\Pages\CreateCertificateSkill;
use App\Filament\Resources\CertificateSkills\Pages\EditCertificateSkill;
use App\Filament\Resources\CertificateSkills\Pages\ListCertificateSkills;
use App\Models\CertificateSkill;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CertificateSkillResource extends Resource
{
    protected static ?string $model = CertificateSkill::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'skill_name';

    protected static ?string $navigationLabel = 'Certificate Skills';

    protected static string|UnitEnum|null $navigationGroup = 'Credentials';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('certificate_id')
                    ->relationship('certificate', 'certificate_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('skill_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('skill_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('certificate.certificate_number')
                    ->label('Certificate')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('certificate.student_name')
                    ->label('Student')
                    ->searchable(),
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
            'index' => ListCertificateSkills::route('/'),
            'create' => CreateCertificateSkill::route('/create'),
            'edit' => EditCertificateSkill::route('/{record}/edit'),
        ];
    }
}

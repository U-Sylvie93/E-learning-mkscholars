<?php

namespace App\Filament\Resources\LiveClassAttendances;

use App\Filament\Resources\LiveClassAttendances\Pages\ListLiveClassAttendances;
use App\Models\LiveClassAttendance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LiveClassAttendanceResource extends Resource
{
    protected static ?string $model = LiveClassAttendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Live Attendance';

    protected static string|UnitEnum|null $navigationGroup = 'Live Learning';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('liveClass.title')
                    ->label('Live Class')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('joined_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('left_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('liveClass')
                    ->relationship('liveClass', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        LiveClassAttendance::STATUS_REGISTERED => 'Registered',
                        LiveClassAttendance::STATUS_ATTENDED => 'Attended',
                        LiveClassAttendance::STATUS_MISSED => 'Missed',
                    ]),
            ])
            ->defaultSort('joined_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLiveClassAttendances::route('/'),
        ];
    }
}

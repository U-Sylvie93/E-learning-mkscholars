<?php

namespace App\Filament\Resources\CourseReviews;

use App\Filament\Resources\CourseReviews\Pages\EditCourseReview;
use App\Filament\Resources\CourseReviews\Pages\ListCourseReviews;
use App\Models\CourseReview;
use BackedEnum;
use Filament\Actions\Action;
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

class CourseReviewResource extends Resource
{
    protected static ?string $model = CourseReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $navigationLabel = 'Course Reviews';

    protected static string|UnitEnum|null $navigationGroup = 'Learning';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('student')->content(fn (?CourseReview $record): string => $record?->user?->name ?? 'Student'),
            Placeholder::make('course')->content(fn (?CourseReview $record): string => $record?->course?->title ?? 'Course'),
            TextInput::make('rating')
                ->numeric()
                ->minValue(1)
                ->maxValue(5)
                ->required(),
            Textarea::make('comment')
                ->rows(5)
                ->columnSpanFull(),
            Select::make('status')
                ->required()
                ->options(self::statusOptions()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')->label('Course')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Student')->searchable()->sortable(),
                TextColumn::make('rating')->suffix('/5')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('comment')->limit(60)->wrap(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::statusOptions()),
                SelectFilter::make('course')->relationship('course', 'title')->searchable()->preload(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                Action::make('publish')
                    ->requiresConfirmation()
                    ->visible(fn (CourseReview $record): bool => $record->status !== CourseReview::STATUS_PUBLISHED)
                    ->action(fn (CourseReview $record) => $record->update(['status' => CourseReview::STATUS_PUBLISHED])),
                Action::make('hide')
                    ->requiresConfirmation()
                    ->visible(fn (CourseReview $record): bool => $record->status !== CourseReview::STATUS_HIDDEN)
                    ->action(fn (CourseReview $record) => $record->update(['status' => CourseReview::STATUS_HIDDEN])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseReviews::route('/'),
            'edit' => EditCourseReview::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            CourseReview::STATUS_PENDING => 'Pending',
            CourseReview::STATUS_PUBLISHED => 'Published',
            CourseReview::STATUS_HIDDEN => 'Hidden',
        ];
    }
}

<?php

namespace App\Filament\Resources\CourseMessageThreads;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\CourseMessageThreads\Pages\EditCourseMessageThread;
use App\Filament\Resources\CourseMessageThreads\Pages\ListCourseMessageThreads;
use App\Models\CourseMessageThread;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CourseMessageThreadResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = CourseMessageThread::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Course Messages';

    protected static string|UnitEnum|null $navigationGroup = 'Communication';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('course')->content(fn (?CourseMessageThread $record): string => $record?->course?->title ?? 'Course'),
            Placeholder::make('student')->content(fn (?CourseMessageThread $record): string => $record?->student?->name ?? 'Student'),
            Placeholder::make('instructor')->content(fn (?CourseMessageThread $record): string => $record?->instructor?->name ?? 'Instructor'),
            Placeholder::make('messages')->content(fn (?CourseMessageThread $record): string => $record?->messages()
                ->with('sender')
                ->oldest()
                ->get()
                ->map(fn ($message): string => ($message->sender?->name ?? 'User').': '.$message->body)
                ->implode("\n\n") ?: 'No messages yet')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')->label('Course')->searchable()->sortable(),
                TextColumn::make('student.name')->label('Student')->searchable()->sortable(),
                TextColumn::make('instructor.name')->label('Instructor')->searchable()->sortable(),
                TextColumn::make('messages_count')->counts('messages')->label('Messages')->sortable(),
                TextColumn::make('last_message_at')->dateTime()->sortable(),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseMessageThreads::route('/'),
            'edit' => EditCourseMessageThread::route('/{record}/edit'),
        ];
    }
}

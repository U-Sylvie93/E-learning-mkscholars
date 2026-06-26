<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Academy extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    public const ICON_CODE = 'code';
    public const ICON_LANGUAGE = 'language';
    public const ICON_BOOK_OPEN = 'book-open';
    public const ICON_GRADUATION_CAP = 'graduation-cap';
    public const ICON_GLOBE = 'globe';
    public const ICON_BRIEFCASE = 'briefcase';
    public const ICON_USERS = 'users';
    public const ICON_AWARD = 'award';
    public const ICON_LAPTOP = 'laptop';
    public const ICON_TARGET = 'target';

    public const ICONS = [
        self::ICON_CODE,
        self::ICON_LANGUAGE,
        self::ICON_BOOK_OPEN,
        self::ICON_GRADUATION_CAP,
        self::ICON_GLOBE,
        self::ICON_BRIEFCASE,
        self::ICON_USERS,
        self::ICON_AWARD,
        self::ICON_LAPTOP,
        self::ICON_TARGET,
    ];

    protected $fillable = [
        'name',
        'slug',
        'summary',
        'description',
        'icon',
        'image_path',
        'status',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * @return array<string, string>
     */
    public static function iconOptions(): array
    {
        return [
            self::ICON_CODE => 'Code',
            self::ICON_LANGUAGE => 'Language',
            self::ICON_BOOK_OPEN => 'Book Open',
            self::ICON_GRADUATION_CAP => 'Graduation Cap',
            self::ICON_GLOBE => 'Globe',
            self::ICON_BRIEFCASE => 'Briefcase',
            self::ICON_USERS => 'Users',
            self::ICON_AWARD => 'Award',
            self::ICON_LAPTOP => 'Laptop',
            self::ICON_TARGET => 'Target',
        ];
    }

    public function safeIcon(): string
    {
        return in_array($this->icon, self::ICONS, true) ? $this->icon : self::ICON_BOOK_OPEN;
    }

    public function iconLabel(): string
    {
        return self::iconOptions()[$this->safeIcon()] ?? 'Academy';
    }

    public function imageUrl(): ?string
    {
        if (filled($this->image_path) && Storage::disk('public')->exists($this->image_path)) {
            return asset('storage/'.$this->image_path);
        }

        return null;
    }

    public function toPublicCard(): array
    {
        $image = $this->imageUrl();

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'level' => $this->iconLabel(),
            'summary' => $this->summary,
            'students' => $this->courses_count ? $this->courses_count.' courses' : 'New pathway',
            'courses_count' => $this->courses_count ?? 0,
            'icon' => $this->safeIcon(),
            'icon_label' => $this->iconLabel(),
            'image' => $image,
            'has_image' => filled($image),
        ];
    }
}

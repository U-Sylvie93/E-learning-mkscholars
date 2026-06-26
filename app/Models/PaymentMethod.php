<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    public const TYPE_MOMO = 'momo';
    public const TYPE_AIRTEL = 'airtel';
    public const TYPE_BANK = 'bank';
    public const TYPE_CASH = 'cash';
    public const TYPE_CARD = 'card';
    public const TYPE_OTHER = 'other';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'type',
        'account_name',
        'account_number',
        'instructions',
        'status',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_name',
        'logo_path',
        'stamp_path',
        'admin_signature_path',
        'issuer_name',
        'issuer_title',
        'certificate_footer_note',
    ];

    public static function current(): self
    {
        return self::query()->first() ?? new self(['organization_name' => 'MK Scholars']);
    }
}

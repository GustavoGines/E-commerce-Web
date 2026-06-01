<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_name',
        'store_tagline',
        'theme_name',
        'primary_color',
        'secondary_color',
        'accent_color',
        'logo_url',
        'favicon_url',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];
}

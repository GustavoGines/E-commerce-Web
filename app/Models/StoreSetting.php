<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class StoreSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_name',
        'store_tagline',
        'theme_name',
        'logo_url',
        'favicon_url',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    public static function getSettings()
    {
        try {
            if (! Schema::hasTable('store_settings')) {
                return new static;
            }

            $attributes = Cache::rememberForever('store_settings', function () {
                $setting = static::first();
                return $setting ? $setting->getAttributes() : [];
            });

            $model = new static;
            $model->setRawAttributes($attributes, true);
            $model->exists = !empty($attributes);
            return $model;
        } catch (\Exception $e) {
            return new static;
        }
    }

    protected static function booted()
    {
        static::saved(function ($setting) {
            Cache::forget('store_settings');
        });

        static::deleted(function ($setting) {
            Cache::forget('store_settings');
        });
    }
}

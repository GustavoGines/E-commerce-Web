<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'table_preferences', 'google_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'table_preferences' => 'array',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Determine if the user qualifies for global wholesale pricing.
     * A user qualifies if they have at least one paid/completed order
     * with an item quantity of 10 or more.
     */
    public function isWholesaleCustomer(): bool
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "user.{$this->id}.wholesale", 
            300, 
            fn() => $this->orders()
                ->whereIn('status', ['pagado', 'completado'])
                ->whereHas('items', function ($query) {
                    $query->where('quantity', '>=', 10);
                })
                ->exists()
        );
    }
}

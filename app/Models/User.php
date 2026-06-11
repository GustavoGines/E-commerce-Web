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
     * Cache for the wholesale status to prevent repeated queries
     * and avoid state leakage across different user instances.
     */
    protected ?bool $isWholesaleCache = null;

    /**
     * Determine if the user qualifies for global wholesale pricing.
     * A user qualifies if they have at least one paid/completed order
     * with an item quantity of 10 or more.
     */
    public function isWholesaleCustomer(): bool
    {
        if ($this->isWholesaleCache !== null) {
            return $this->isWholesaleCache;
        }

        $this->isWholesaleCache = $this->orders()
            ->whereIn('status', ['pagado', 'completado']) // BUG-09 FIX: 'completada'/'aprobada' never existed in the DB
            ->whereHas('items', function ($query) {
                $query->where('quantity', '>=', 10);
            })
            ->exists();

        return $this->isWholesaleCache;
    }
}

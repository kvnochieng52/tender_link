<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'telephone',
        'email',
        'password',
        'google_id',
        'avatar',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function userPlans(): HasMany
    {
        return $this->hasMany(UserPlan::class);
    }

    public function tenderPayments(): HasMany
    {
        return $this->hasMany(TenderPayment::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function hasActivePlan(): bool
    {
        return $this->userPlans()
            ->where('is_active', true)
            ->where('end_date', '>=', now()->toDateString())
            ->exists();
    }

    public function hasPaidForTender(int $tenderId): bool
    {
        return $this->tenderPayments()
            ->where('tender_id', $tenderId)
            ->where('status', 'paid')
            ->exists();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'plan_name',
        'period',
        'amount',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function userPlans(): HasMany
    {
        return $this->hasMany(UserPlan::class);
    }
}

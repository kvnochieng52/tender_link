<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationStatus extends Model
{
    protected $fillable = ['name', 'color', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}

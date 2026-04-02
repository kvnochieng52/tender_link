<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationProcessStatus extends Model
{
    protected $fillable = ['name', 'color', 'order', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }
}

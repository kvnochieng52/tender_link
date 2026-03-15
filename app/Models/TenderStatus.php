<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenderStatus extends Model
{
    protected $fillable = ['name', 'active'];

    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }
}

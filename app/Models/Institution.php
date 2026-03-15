<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_name',
        'institution_type_id',
        'email',
        'telephone',
        'logo',
        'profile',
        'website',
        'address',
        'created_by',
        'updated_by',
    ];

    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }

    public function institutionType(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

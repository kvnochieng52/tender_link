<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommonRequirement extends Model
{
    use HasFactory;

    protected $table = 'common_requirements';

    protected $fillable = [
        'title',
        'notes',
        'mandatory',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'mandatory' => 'boolean',
    ];
}

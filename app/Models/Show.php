<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Show extends Model
{
    use HasFactory;

    /**
     * Related episodes
     */
    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class, 'show_id', 'id');
    }
}

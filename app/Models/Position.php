<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Position extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name'];

    public function asatidz(): BelongsToMany
    {
        return $this->belongsToMany(Asatidz::class, 'asatidz_positions');
    }
}

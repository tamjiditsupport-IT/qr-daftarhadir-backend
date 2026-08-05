<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Asatidz extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asatidz';

    protected $fillable = ['id_asatidz', 'name', 'phone'];

    public function qrCard(): HasOne
    {
        return $this->hasOne(QrCard::class, 'asatidz_id');
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'asatidz_units');
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'asatidz_positions');
    }
}

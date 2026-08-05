<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['asatidz_id', 'qr_code'];

    public function asatidz(): BelongsTo
    {
        return $this->belongsTo(Asatidz::class, 'asatidz_id');
    }
}

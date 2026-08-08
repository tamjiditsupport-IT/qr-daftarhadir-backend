<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_id');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    /**
     * Recursively get this unit's ID and all its descendants' IDs.
     */
    public function getAllChildIds(): array
    {
        $ids = [$this->id];
        $children = $this->children()->get();
        
        foreach ($children as $child) {
            $ids = array_merge($ids, $child->getAllChildIds());
        }
        
        return $ids;
    }

    public function asatidz(): BelongsToMany
    {
        return $this->belongsToMany(Asatidz::class, 'asatidz_units');
    }
}

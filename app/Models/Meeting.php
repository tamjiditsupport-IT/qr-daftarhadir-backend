<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meeting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'meeting_type_id',
        'start_time',
        'late_minutes',
        'status',
        'created_by'
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(MeetingType::class, 'meeting_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approval(): HasOne
    {
        return $this->hasOne(Approval::class, 'meeting_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class, 'meeting_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'meeting_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
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
        'created_by',
        'meeting_code'
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($meeting) {
            if (empty($meeting->meeting_code)) {
                $year = date('Y', strtotime($meeting->start_time ?? now()));
                $meeting->meeting_code = 'RPT-' . $year . '-' . strtoupper(Str::random(6));
            }
        });
        
        static::created(function ($meeting) {
            $meeting->meeting_code = 'RPT-' . date('Y', strtotime($meeting->created_at)) . '-' . str_pad($meeting->id, 6, '0', STR_PAD_LEFT);
            $meeting->saveQuietly();
        });
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

    public function attachments(): HasMany
    {
        return $this->hasMany(MeetingAttachment::class, 'meeting_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MeetingHistory::class, 'meeting_id')->orderBy('created_at', 'desc');
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

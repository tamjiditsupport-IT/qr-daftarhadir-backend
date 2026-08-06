<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'asatidz_id',
        'attendance_status',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function asatidz(): BelongsTo
    {
        return $this->belongsTo(Asatidz::class, 'asatidz_id');
    }

    public function attendanceLog()
    {
        return $this->hasOne(AttendanceLog::class, 'asatidz_id', 'asatidz_id')
            ->where('meeting_id', $this->meeting_id ?? 0);
    }
}

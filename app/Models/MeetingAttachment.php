<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by'
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

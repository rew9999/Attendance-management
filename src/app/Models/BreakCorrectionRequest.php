<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'break_id',
        'requested_break_start',
        'requested_break_end',
    ];

    protected $casts = [
        'requested_break_start' => 'datetime',
        'requested_break_end' => 'datetime',
    ];

    /**
     * 修正申請とのリレーション
     */
    public function correctionRequest()
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class, 'correction_request_id');
    }

    /**
     * 元の休憩記録とのリレーション
     */
    public function break()
    {
        return $this->belongsTo(AttendanceBreak::class, 'break_id');
    }
}

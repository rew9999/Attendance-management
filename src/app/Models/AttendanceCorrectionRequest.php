<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'requested_clock_in',
        'requested_clock_out',
        'reason',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'requested_clock_in' => 'datetime',
        'requested_clock_out' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * 勤怠記録とのリレーション
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 申請者とのリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 承認者とのリレーション
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * 休憩修正申請とのリレーション
     */
    public function breakCorrectionRequests()
    {
        return $this->hasMany(BreakCorrectionRequest::class, 'correction_request_id');
    }

    /**
     * 承認待ちのスコープ
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * 承認済みのスコープ
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * 却下済みのスコープ
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}

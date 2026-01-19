<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceBreak extends Model
{
    use HasFactory;

    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
    ];

    /**
     * 勤怠記録とのリレーション
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 休憩時間を計算（分単位）
     */
    public function getBreakMinutes()
    {
        if (! $this->break_end) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($this->break_start);
        $end = \Carbon\Carbon::parse($this->break_end);

        return $start->diffInMinutes($end);
    }
}

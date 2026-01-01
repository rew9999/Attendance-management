<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'note',
        'status',
        'is_corrected',
        'corrected_at',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'is_corrected' => 'boolean',
        'corrected_at' => 'datetime',
    ];

    /**
     * ユーザーとのリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 休憩記録とのリレーション
     */
    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    /**
     * 修正申請とのリレーション
     */
    public function correctionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    /**
     * 承認待ちの修正申請を取得
     */
    public function pendingCorrectionRequest()
    {
        return $this->hasOne(AttendanceCorrectionRequest::class)->where('status', 'pending');
    }

    /**
     * 総休憩時間を計算（分単位）
     */
    public function getTotalBreakMinutes()
    {
        $total = 0;
        foreach ($this->breaks as $break) {
            if ($break->break_end) {
                $start = \Carbon\Carbon::parse($break->break_start);
                $end = \Carbon\Carbon::parse($break->break_end);
                $total += $start->diffInMinutes($end);
            }
        }
        return $total;
    }

    /**
     * 勤務時間を計算（分単位）
     */
    public function getWorkMinutes()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($this->clock_in);
        $end = \Carbon\Carbon::parse($this->clock_out);
        $totalMinutes = $start->diffInMinutes($end);

        return $totalMinutes - $this->getTotalBreakMinutes();
    }
}

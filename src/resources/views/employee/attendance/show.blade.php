<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>勤怠詳細</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/employee-attendance-detail.css') }}">
</head>
<body>
    <x-header />

    <div class="container">
        <h1>勤怠詳細</h1>

        <form method="POST" action="{{ route('employee.attendance.edit.request.post', $attendance->id) }}">
            @csrf
            <div class="detail-card">
                <div class="detail-row">
                    <div class="detail-label">名前</div>
                    <div class="detail-value name-value">{{ auth()->user()->name }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">日付</div>
                    <div class="detail-value">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</div>
                    <div class="detail-separator"></div>
                    <div class="detail-value">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">出勤・退勤</div>
                    <div class="detail-value">
                        <input type="text" class="time-input" name="requested_clock_in" placeholder="09:00" value="{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}">
                    </div>
                    <div class="detail-separator">〜</div>
                    <div class="detail-value">
                        <input type="text" class="time-input" name="requested_clock_out" placeholder="18:00" value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}">
                    </div>
                </div>

                @php
                    $breaks = $attendance->breaks->sortBy('break_start')->values();
                    $break1 = $breaks->get(0);
                    $break2 = $breaks->get(1);
                @endphp

                <div class="detail-row">
                    <div class="detail-label">休憩</div>
                    <div class="detail-value">
                        <input type="text" class="time-input" name="breaks[0][requested_break_start]" placeholder="12:00" value="{{ $break1 && $break1->break_start ? \Carbon\Carbon::parse($break1->break_start)->format('H:i') : '' }}">
                        <input type="hidden" name="breaks[0][break_id]" value="{{ $break1 ? $break1->id : '' }}">
                    </div>
                    <div class="detail-separator">〜</div>
                    <div class="detail-value">
                        <input type="text" class="time-input" name="breaks[0][requested_break_end]" placeholder="13:00" value="{{ $break1 && $break1->break_end ? \Carbon\Carbon::parse($break1->break_end)->format('H:i') : '' }}">
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">休憩2</div>
                    <div class="detail-value">
                        <input type="text" class="time-input" name="breaks[1][requested_break_start]" placeholder="" value="{{ $break2 && $break2->break_start ? \Carbon\Carbon::parse($break2->break_start)->format('H:i') : '' }}">
                        <input type="hidden" name="breaks[1][break_id]" value="{{ $break2 ? $break2->id : '' }}">
                    </div>
                    <div class="detail-separator">〜</div>
                    <div class="detail-value">
                        <input type="text" class="time-input" name="breaks[1][requested_break_end]" placeholder="" value="{{ $break2 && $break2->break_end ? \Carbon\Carbon::parse($break2->break_end)->format('H:i') : '' }}">
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">備考</div>
                    <div class="detail-value-full">
                        <textarea class="note-textarea" name="reason"></textarea>
                    </div>
                </div>
            </div>

            <div class="button-container">
                <button type="submit" class="edit-button">修正</button>
            </div>
        </form>
    </div>
</body>
</html>

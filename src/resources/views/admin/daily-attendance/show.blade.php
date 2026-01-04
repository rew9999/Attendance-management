<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>勤怠詳細</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-attendance-detail.css') }}">
</head>
<body>
    <x-header />

    <div class="container">
        <h1>勤怠詳細</h1>

        <form method="POST" action="{{ route('admin.attendance.update', $attendance->id) }}">
            @csrf
            @method('PUT')

            <div class="detail-card">
                <div class="detail-row">
                    <div class="detail-label">名前</div>
                    <div class="detail-value name-value">{{ $attendance->user->name }}</div>
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
                        <input type="text" name="clock_in" value="{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}" class="time-input" placeholder="09:00">
                    </div>
                    <div class="detail-separator">〜</div>
                    <div class="detail-value">
                        <input type="text" name="clock_out" value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}" class="time-input" placeholder="18:00">
                    </div>
                </div>

                @foreach($attendance->breaks as $index => $break)
                <div class="detail-row">
                    <div class="detail-label">休憩{{ $index > 0 ? ($index + 1) : '' }}</div>
                    <div class="detail-value">
                        <input type="text" name="breaks[{{ $break->id }}][break_start]" value="{{ $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '' }}" class="time-input" placeholder="12:00">
                    </div>
                    <div class="detail-separator">〜</div>
                    <div class="detail-value">
                        <input type="text" name="breaks[{{ $break->id }}][break_end]" value="{{ $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '' }}" class="time-input" placeholder="13:00">
                    </div>
                </div>
                @endforeach

                <div class="detail-row">
                    <div class="detail-label">備考</div>
                    <div class="detail-value-full">
                        <textarea name="remarks" class="remarks-area">{{ $attendance->remarks ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <div class="button-container">
                <button type="submit" class="submit-button">修正</button>
            </div>
        </form>
    </div>
</body>
</html>

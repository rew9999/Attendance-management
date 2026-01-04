<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $user->name }}さんの勤怠</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-staff-attendance.css') }}">
</head>
<body>
    <x-header />

    <div class="container">
        <h1>{{ $user->name }}さんの勤怠</h1>

        <div class="month-navigation">
            @php
                $currentMonth = request('month') ? \Carbon\Carbon::parse(request('month')) : \Carbon\Carbon::now();
                $prevMonth = $currentMonth->copy()->subMonth();
                $nextMonth = $currentMonth->copy()->addMonth();
            @endphp

            <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $prevMonth->format('Y-m')]) }}" class="nav-button">← 前月</a>

            <div class="month-display">
                <img src="{{ asset('images/カレンダーアイコン8.svg') }}" alt="カレンダー" class="calendar-icon">
                <span class="month-text">{{ $currentMonth->format('Y/m') }}</span>
            </div>

            <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $nextMonth->format('Y-m')]) }}" class="nav-button">翌月 →</a>
        </div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $attendance)
                <tr>
                    <td>
                        @php
                            $date = \Carbon\Carbon::parse($attendance->date);
                            $dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek];
                        @endphp
                        {{ $date->format('m/d') }}({{ $dayOfWeek }})
                    </td>
                    <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '-' }}</td>
                    <td>
                        @php
                            $breakMinutes = $attendance->getTotalBreakMinutes();
                            $breakHours = floor($breakMinutes / 60);
                            $breakMins = $breakMinutes % 60;
                        @endphp
                        {{ sprintf('%d:%02d', $breakHours, $breakMins) }}
                    </td>
                    <td>
                        @php
                            $workMinutes = $attendance->getWorkMinutes();
                            $workHours = floor($workMinutes / 60);
                            $workMins = $workMinutes % 60;
                        @endphp
                        {{ sprintf('%d:%02d', $workHours, $workMins) }}
                    </td>
                    <td>
                        <a href="{{ route('admin.attendance.date', $attendance->id) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-message">勤怠データがありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($attendances->hasPages())
        <div class="pagination">
            {{ $attendances->appends(['month' => request('month')])->links() }}
        </div>
        @endif

        <div class="button-container">
            <a href="{{ route('admin.attendance.staff.export', ['id' => $user->id, 'month' => $currentMonth->format('Y-m')]) }}" class="export-button">CSV出力</a>
        </div>
    </div>
</body>
</html>

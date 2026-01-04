<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>勤怠一覧</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/employee-attendance-list.css') }}">
</head>
<body>
    <x-header />

    <div class="container">
        <h1>勤怠一覧</h1>

        <!-- 月選択フォーム -->
        <div class="filter-section">
            <form method="GET" action="{{ route('employee.attendance.list') }}">
                <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" onchange="this.form.submit()">
            </form>
        </div>

        <!-- 勤怠データテーブル -->
        <div class="table-wrapper">
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
                            <td>{{ \Carbon\Carbon::parse($attendance->date)->format('Y/m/d') }}</td>
                            <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '-' }}</td>
                            <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '-' }}</td>
                            <td>
                                @php
                                    $totalBreakMinutes = $attendance->breaks->sum(function($break) {
                                        if ($break->break_start && $break->break_end) {
                                            return \Carbon\Carbon::parse($break->break_start)->diffInMinutes(\Carbon\Carbon::parse($break->break_end));
                                        }
                                        return 0;
                                    });
                                    $hours = floor($totalBreakMinutes / 60);
                                    $minutes = $totalBreakMinutes % 60;
                                @endphp
                                {{ sprintf('%d:%02d', $hours, $minutes) }}
                            </td>
                            <td>
                                @if($attendance->clock_in && $attendance->clock_out)
                                    @php
                                        $totalMinutes = \Carbon\Carbon::parse($attendance->clock_in)->diffInMinutes(\Carbon\Carbon::parse($attendance->clock_out));
                                        $workMinutes = $totalMinutes - $totalBreakMinutes;
                                        $workHours = floor($workMinutes / 60);
                                        $workMins = $workMinutes % 60;
                                    @endphp
                                    {{ sprintf('%d:%02d', $workHours, $workMins) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('employee.attendance.data', $attendance->id) }}" class="detail-btn">詳細</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center;">勤怠データがありません</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- ページネーション -->
        <div class="pagination">
            {{ $attendances->links() }}
        </div>
    </div>
</body>
</html>

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

    <main>
        <h1 class="page-title">勤怠一覧</h1>

        <div class="month-navigation">
            <a href="{{ route('employee.attendance.list', ['month' => $prevMonth->format('Y-m')]) }}" class="nav-button">← 前月</a>

            <div class="month-display">
                <img src="{{ asset('images/カレンダーアイコン8.svg') }}" alt="カレンダー" class="calendar-icon">
                <span class="month-text">{{ $currentMonth->format('Y/m') }}</span>
            </div>

            <a href="{{ route('employee.attendance.list', ['month' => $nextMonth->format('Y-m')]) }}" class="nav-button">翌月 →</a>
        </div>

        <div class="attendance-table-container">
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
                    @foreach($allDates as $date => $value)
                        @php
                            $attendance = $attendances->get($date);
                            $carbonDate = \Carbon\Carbon::parse($date);
                            $dayOfWeek = $carbonDate->locale('ja')->isoFormat('(ddd)');

                            // 休憩時間の合計を計算（モデルのメソッドを使用）
                            $totalBreakMinutes = $attendance ? $attendance->getTotalBreakMinutes() : 0;

                            // 勤務時間の合計を計算（モデルのメソッドを使用）
                            $totalWorkMinutes = $attendance ? $attendance->getWorkMinutes() : 0;
                        @endphp
                        <tr>
                            <td>{{ $carbonDate->format('m/d') }}{{ $dayOfWeek }}</td>
                            <td>{{ $attendance && $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                            <td>{{ $attendance && $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                            <td>{{ $totalBreakMinutes > 0 ? sprintf('%d:%02d', floor($totalBreakMinutes / 60), $totalBreakMinutes % 60) : '' }}</td>
                            <td>{{ $totalWorkMinutes > 0 ? sprintf('%d:%02d', floor($totalWorkMinutes / 60), $totalWorkMinutes % 60) : '' }}</td>
                            <td>
                                @if($attendance)
                                    <a href="{{ route('employee.attendance.data', $attendance->id) }}" class="detail-button">詳細</a>
                                @else
                                    <span class="detail-button disabled">詳細</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>

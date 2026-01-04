<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>勤怠一覧 - Attendance Management</title>
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-attendance-list.css') }}">
</head>

<body>
    @include('components.header')

    <main>
        <div class="container">
            <h1>{{ $date->format('Y年n月j日') }}の勤怠</h1>

            <div class="date-navigation">
                <a href="{{ route('admin.attendance.list', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}" class="nav-button">← 前日</a>

                <form method="GET" action="{{ route('admin.attendance.list') }}" class="date-form">
                    <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" onchange="this.form.submit()">
                </form>

                <a href="{{ route('admin.attendance.list', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}" class="nav-button">翌日 →</a>
            </div>

            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>名前</th>
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
                        <td>{{ $attendance->user->name }}</td>
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
                            <a href="{{ route('admin.attendance.date', ['id' => $attendance->id]) }}" class="detail-button">詳細</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="empty-message">勤怠データがありません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>

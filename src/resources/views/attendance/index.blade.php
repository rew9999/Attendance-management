<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>打刻</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
</head>
<body>
    <x-header />

    <main>
        <div class="status-badge">
            @if(!$attendance)
                勤務外
            @elseif($status === 'finished')
                退勤済
            @elseif($status === 'on_break')
                休憩中
            @elseif($status === 'working')
                出勤中
            @endif
        </div>

        <div class="date-time">
            <p class="date">{{ now()->locale('ja')->isoFormat('YYYY年M月D日(ddd)') }}</p>
            <p class="time">{{ now()->format('H:i') }}</p>
        </div>

        <div class="button-container">
            @if(!$attendance)
                {{-- 出勤前 --}}
                <form method="POST" action="{{ route('attendance.clock-in') }}">
                    @csrf
                    <button type="submit" class="btn-primary">出勤</button>
                </form>
            @elseif($status === 'working')
                {{-- 出勤中 --}}
                <form method="POST" action="{{ route('attendance.clock-out') }}">
                    @csrf
                    <button type="submit" class="btn-primary">退勤</button>
                </form>
                <form method="POST" action="{{ route('attendance.break-start') }}">
                    @csrf
                    <button type="submit" class="btn-secondary">休憩入</button>
                </form>
            @elseif($status === 'on_break')
                {{-- 休憩中 --}}
                <form method="POST" action="{{ route('attendance.break-end') }}">
                    @csrf
                    <button type="submit" class="btn-secondary">休憩戻</button>
                </form>
            @elseif($status === 'finished')
                {{-- 退勤済 --}}
                <p class="finished-message">お疲れ様でした。</p>
            @endif
        </div>
    </main>
</body>
</html>

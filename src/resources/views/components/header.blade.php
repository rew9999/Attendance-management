{{-- resources/views/components/header.blade.php --}}
<header>
    @if(auth()->check() && auth()->user()->role === 'admin')
    {{-- 管理者用ヘッダー --}}
    <nav class="admin-header">
        <a href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
        <a href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
        <a href="{{ route('admin.stamp_correction_request.list') }}">申請一覧</a>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit">ログアウト</button>
        </form>
    </nav>
    @elseif(auth()->check() && auth()->user()->role === 'employee')
    {{-- 一般ユーザー用ヘッダー --}}
    <nav class="user-header">
        <a href="{{ route('attendance.index') }}">勤怠</a>
        <a href="{{ route('employee.attendance.list') }}">勤怠一覧</a>
        <a href="{{ route('employee.correction.index') }}">申請</a>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit">ログアウト</button>
        </form>
    </nav>
    @endif
</header>
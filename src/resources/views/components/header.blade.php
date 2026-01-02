{{-- resources/views/layouts/header.blade.php --}}
<header>
    @if(auth()->guard('admin')->check())
    {{-- 管理者用ヘッダー --}}
    <nav class="admin-header">
        <a href="{{ route('attendance.list') }}">勤怠一覧</a>
        <a href="{{ route('staff.list') }}">スタッフ一覧</a>
        <a href="{{ route('stamp_correction_request.list') }}">申請一覧</a>
        <a href="{{ route('admin.users') }}">ログアウト</a>
    </nav>
    @elseif(auth()->guard('employee')->check())
    {{-- 一般ユーザー用ヘッダー --}}
    <nav class="user-header">
        <a href="{{ route('attendance.index') }}">勤怠</a>
        <a href="{{ route('employee.attendance.list') }}">勤怠一覧</a>
        <a href="{{ route('employee.attendance.edit.request') }}">申請</a>
        <a href="{{ route('profile') }}">ログアウト</a>
    </nav>
    @endif
</header>
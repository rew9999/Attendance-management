<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スタッフ一覧</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-staff-list.css') }}">
</head>
<body>
    <x-header />

    <div class="container">
        <h1>スタッフ一覧</h1>

        <table class="staff-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $employee)
                <tr>
                    <td>{{ $employee->name }}</td>
                    <td>{{ $employee->email }}</td>
                    <td>
                        <a href="{{ route('admin.attendance.staff', $employee->id) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="empty-message">スタッフが登録されていません</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($staff->hasPages())
        <div class="pagination">
            {{ $staff->links() }}
        </div>
        @endif
    </div>
</body>
</html>

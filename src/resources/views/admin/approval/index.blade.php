<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申請一覧</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-approval-list.css') }}">
</head>
<body>
    <x-header />

    <div class="container">
        <h1>申請一覧</h1>

        <div class="tabs">
            <a href="{{ route('admin.stamp_correction_request.list', ['status' => 'pending']) }}" class="tab {{ request('status', 'pending') === 'pending' ? 'active' : '' }}">承認待ち</a>
            <a href="{{ route('admin.stamp_correction_request.list', ['status' => 'approved']) }}" class="tab {{ request('status') === 'approved' ? 'active' : '' }}">承認済み</a>
        </div>

        <table class="approval-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $request)
                <tr>
                    <td>{{ $request->status === 'pending' ? '承認待ち' : '承認済み' }}</td>
                    <td>{{ $request->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}</td>
                    <td>{{ $request->reason }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}</td>
                    <td>
                        <a href="{{ route('admin.stamp_correction_request.approve', $request->id) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-message">申請がありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($requests->hasPages())
        <div class="pagination">
            {{ $requests->appends(['status' => request('status')])->links() }}
        </div>
        @endif
    </div>
</body>
</html>

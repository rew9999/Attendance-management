<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申請一覧</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/employee-correction-list.css') }}">
</head>
<body>
    <x-header />

    <main>
        <h1 class="page-title">申請一覧</h1>

        <div class="tab-navigation">
            <a href="{{ route('employee.correction.index') }}" class="tab-button {{ ($status ?? 'pending') === 'pending' ? 'active' : '' }}">承認待ち</a>
            <a href="{{ route('employee.correction.index', ['status' => 'approved']) }}" class="tab-button {{ ($status ?? 'pending') === 'approved' ? 'active' : '' }}">承認済み</a>
        </div>

        <div class="table-container">
            <table class="request-table">
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
                        <td>
                            @if($request->status === 'pending')
                                <span class="status-badge pending">承認待ち</span>
                            @elseif($request->status === 'approved')
                                <span class="status-badge approved">承認済み</span>
                            @else
                                <span class="status-badge rejected">却下</span>
                            @endif
                        </td>
                        <td>{{ $request->user->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}</td>
                        <td>{{ $request->reason }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}</td>
                        <td>
                            <a href="{{ route('employee.correction.show', $request->id) }}" class="detail-link">詳細</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="empty-message">申請データがありません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
        <div class="pagination">
            {{ $requests->links() }}
        </div>
        @endif
    </main>
</body>
</html>

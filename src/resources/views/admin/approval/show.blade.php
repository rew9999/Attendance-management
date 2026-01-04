<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>勤怠詳細</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-approval-detail.css') }}">
</head>
<body>
    <x-header />

    <div class="container">
        <h1>勤怠詳細</h1>

        <div class="detail-card">
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-value name-value">{{ $correctionRequest->user->name }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">日付</div>
                <div class="detail-value">{{ \Carbon\Carbon::parse($correctionRequest->attendance->date)->format('Y年') }}</div>
                <div class="detail-separator"></div>
                <div class="detail-value">{{ \Carbon\Carbon::parse($correctionRequest->attendance->date)->format('n月j日') }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">出勤・退勤</div>
                <div class="detail-value">{{ $correctionRequest->requested_clock_in ? \Carbon\Carbon::parse($correctionRequest->requested_clock_in)->format('H:i') : '-' }}</div>
                <div class="detail-separator">〜</div>
                <div class="detail-value">{{ $correctionRequest->requested_clock_out ? \Carbon\Carbon::parse($correctionRequest->requested_clock_out)->format('H:i') : '-' }}</div>
            </div>

            @foreach($correctionRequest->breakCorrectionRequests as $index => $breakCorrection)
            <div class="detail-row">
                <div class="detail-label">休憩{{ $index > 0 ? ($index + 1) : '' }}</div>
                <div class="detail-value">{{ $breakCorrection->requested_break_start ? \Carbon\Carbon::parse($breakCorrection->requested_break_start)->format('H:i') : '-' }}</div>
                <div class="detail-separator">〜</div>
                <div class="detail-value">{{ $breakCorrection->requested_break_end ? \Carbon\Carbon::parse($breakCorrection->requested_break_end)->format('H:i') : '-' }}</div>
            </div>
            @endforeach

            @if($correctionRequest->breakCorrectionRequests->count() === 0)
            <div class="detail-row">
                <div class="detail-label">休憩</div>
                <div class="detail-value"></div>
                <div class="detail-separator"></div>
                <div class="detail-value"></div>
            </div>
            @endif

            <div class="detail-row">
                <div class="detail-label">備考</div>
                <div class="detail-value-full">
                    {{ $correctionRequest->reason ?? '' }}
                </div>
            </div>
        </div>

        <div class="button-container">
            @if($correctionRequest->status === 'pending')
            <form method="POST" action="{{ route('admin.stamp_correction_request.approve.post', $correctionRequest->id) }}" class="approve-form">
                @csrf
                <button type="submit" class="approve-button">承認</button>
            </form>
            @else
            <button type="button" class="approved-button" disabled>承認済み</button>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const approveForm = document.querySelector('.approve-form');

            if (approveForm) {
                approveForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const url = this.action;

                    fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const approveButton = document.querySelector('.approve-button');
                            approveButton.textContent = '承認済み';
                            approveButton.classList.remove('approve-button');
                            approveButton.classList.add('approved-button');
                            approveButton.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            }
        });
    </script>
</body>
</html>

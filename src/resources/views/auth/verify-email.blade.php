<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メール認証</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
</head>
<body>
    <x-auth-header />

    <main>
        <p class="message">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>

        <button type="button" class="verify-button">認証はこちらから</button>

        <form method="POST" action="{{ route('verification.send') }}" class="resend-form">
            @csrf
            <button type="submit" class="resend-link">認証メールを再送する</button>
        </form>

        @if (session('status') == 'verification-link-sent')
            <p class="success-message">認証メールを再送信しました。</p>
        @endif
    </main>
</body>
</html>

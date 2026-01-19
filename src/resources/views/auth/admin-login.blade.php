<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>attendance management</title>
    <link rel="stylesheet" href="{{ asset('css/admin-login.css') }}">
</head>

<body>
    <x-auth-header />
    <main>
        <h1>管理者ログイン</h1>
        <form method="POST" action="/login">
            @csrf
            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}">
                @error('email')
                <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password">
                @error('password')
                <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit">管理者ログインする</button>
        </form>
    </main>
</body>

</html>
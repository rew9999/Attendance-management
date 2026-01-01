<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <form method="POST" action="/login">
        @csrf
        <input type="email" name="email" value="{{ old('email') }}">
        @error('email')
            <div>{{ $message }}</div>
        @enderror

        <input type="password" name="password">
        @error('password')
            <div>{{ $message }}</div>
        @enderror

        <button type="submit">Login</button>
    </form>
</body>
</html>

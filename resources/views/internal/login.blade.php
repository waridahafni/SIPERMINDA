<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Internal - SIPERMINDA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: { 50: '#e6eef7', 100: '#b3cceb', 200: '#80aadf', 300: '#4d88d3', 400: '#1a66c7', 500: '#003D7A', 600: '#003162', 700: '#00254a', 800: '#001832', 900: '#000c1a' }
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans antialiased bg-gradient-to-br from-primary-500 to-primary-800 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white">SIPERMINDA</h1>
            <p class="text-primary-200 mt-1">Sistem Informasi Permintaan Data Statistik</p>
        </div>

        <div class="bg-white rounded-xl shadow-2xl p-8">
            <h2 class="text-xl font-bold text-gray-800 text-center">Login Internal</h2>
            <p class="text-gray-500 text-sm text-center mt-1">Masuk untuk staf / admin BPS</p>

            <form method="POST" action="{{ route('internal.login.post') }}" class="mt-6 space-y-4">
                @csrf

                @if($errors->any())
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 rounded text-sm">
                        @foreach($errors->all() as $err)
                            <p>{{ $err }}</p>
                        @endforeach
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="nama@bps.go.id"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="Masukkan password"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                    <label for="remember" class="ml-2 text-sm text-gray-600">Ingat saya</label>
                </div>

                <button type="submit" class="w-full bg-primary-500 text-white py-3 rounded-lg font-semibold hover:bg-primary-600 transition shadow">Login</button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('beranda') }}" class="text-sm text-primary-500 hover:underline">&larr; Kembali ke Beranda Publik</a>
            </div>
        </div>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect — DB Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-950 min-h-screen flex items-center justify-center p-4">

    <div class="absolute inset-0 opacity-40" style="background-image:url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%239C92AC%22 fill-opacity=%220.03%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')"></div>

    <div class="relative w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-orange-500/10 border border-orange-500/20 rounded-2xl mb-4">
                <span class="text-2xl">⚡</span>
            </div>
            <h1 class="text-2xl font-bold text-white">DB<span class="text-orange-400">Manager</span></h1>
            <p class="text-slate-500 text-sm mt-1">MySQL Database Administration</p>
        </div>

        @if(session('error'))
        <div class="mb-4 flex items-center gap-2.5 bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl text-sm">
            <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
            {{ session('error') }}
        </div>
        @endif
        @if(session('success'))
        <div class="mb-4 flex items-center gap-2.5 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-4 py-3 rounded-xl text-sm">
            <i class="fa-solid fa-circle-check flex-shrink-0"></i>
            {{ session('success') }}
        </div>
        @endif
        @if($errors->any())
        <div class="mb-4 bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl text-sm">
            <ul class="space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-7">
            <h2 class="text-sm font-semibold text-slate-300 mb-5">Connect to MySQL Server</h2>

            <form action="{{ route('db-manager.do-connect') }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">
                            <i class="fa-solid fa-server mr-1"></i> Host
                        </label>
                        <input type="text" name="host" value="{{ old('host', '127.0.0.1') }}" required
                               class="w-full bg-slate-800 text-white rounded-lg px-3 py-2.5 text-sm border border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent placeholder-slate-600 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Port</label>
                        <input type="number" name="port" value="{{ old('port', '3306') }}" required
                               class="w-full bg-slate-800 text-white rounded-lg px-3 py-2.5 text-sm border border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1.5">
                        <i class="fa-solid fa-user mr-1"></i> Username
                    </label>
                    <input type="text" name="username" value="{{ old('username', 'root') }}" required
                           class="w-full bg-slate-800 text-white rounded-lg px-3 py-2.5 text-sm border border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1.5">
                        <i class="fa-solid fa-lock mr-1"></i> Password
                    </label>
                    <input type="password" name="password"
                           class="w-full bg-slate-800 text-white rounded-lg px-3 py-2.5 text-sm border border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                           placeholder="Leave blank for no password">
                </div>

                <button type="submit"
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm flex items-center justify-center gap-2 mt-2">
                    <i class="fa-solid fa-plug"></i> Connect
                </button>
            </form>
        </div>

        <p class="text-center text-slate-700 text-xs mt-5">Credentials are stored in your session only.</p>
    </div>
</body>
</html>

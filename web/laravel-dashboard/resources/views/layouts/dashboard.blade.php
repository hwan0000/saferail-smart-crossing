<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Railway AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f4f4f5; /* zinc-100 */
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        /* Custom scrollbar for modern feel */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #d4d4d8;
            border-radius: 10px;
        }
    </style>
</head>
<body class="text-zinc-800 antialiased h-screen flex overflow-hidden selection:bg-emerald-500 selection:text-white">

    <!-- Sidebar (Dark Command Center Theme) -->
    <aside class="hidden md:flex w-64 bg-[#09090b] flex-col h-full shrink-0 relative overflow-hidden">
        <!-- Background subtle glow -->
        <div class="absolute top-0 left-0 w-full h-32 bg-emerald-500/10 blur-3xl rounded-full"></div>
        
        <!-- Station Info -->
        <div class="h-20 flex items-center px-6 border-b border-zinc-800/50 relative z-10">
            <div class="flex items-center gap-3 w-full">
                <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center border border-emerald-500/30 shadow-[0_0_15px_rgba(16,185,129,0.2)]">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                </div>
                <div class="flex-1">
                    <div class="font-bold text-white text-sm tracking-wide">STATION 042</div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_5px_#10b981]"></div>
                        <div class="text-[10px] text-zinc-400 font-mono uppercase tracking-widest">Active</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nav Links -->
        <nav class="flex-1 py-6 px-4 space-y-1.5 overflow-y-auto relative z-10">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('dashboard') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'text-zinc-400 hover:text-white hover:bg-zinc-800/50 border border-transparent' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span class="font-medium text-sm">Overview</span>
            </a>
            
            <a href="{{ route('dashboard.sensors') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('dashboard.sensors') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'text-zinc-400 hover:text-white hover:bg-zinc-800/50 border border-transparent' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                <span class="font-medium text-sm">Sensor Grid</span>
            </a>

            <a href="{{ route('dashboard.analytics') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('dashboard.analytics') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'text-zinc-400 hover:text-white hover:bg-zinc-800/50 border border-transparent' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span class="font-medium text-sm">AI Analytics</span>
            </a>

            <a href="{{ route('dashboard.devices') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('dashboard.devices') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'text-zinc-400 hover:text-white hover:bg-zinc-800/50 border border-transparent' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                <span class="font-medium text-sm">Device Control</span>
            </a>

            <a href="{{ route('dashboard.history') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('dashboard.history') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'text-zinc-400 hover:text-white hover:bg-zinc-800/50 border border-transparent' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="font-medium text-sm">History Log</span>
            </a>
        </nav>

        <!-- Emergency Stop -->
        <div class="p-4 mt-auto relative z-10 border-t border-zinc-800/50">
            <button class="w-full bg-red-500/10 border border-red-500/30 text-red-500 hover:bg-red-500 hover:text-white hover:shadow-[0_0_20px_rgba(239,68,68,0.4)] py-3 px-4 rounded-xl flex items-center justify-center gap-2 transition-all duration-300">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"></path></svg>
                <span class="font-bold text-sm tracking-wide">SYSTEM HALT</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <!-- Top Nav (Glassmorphism) -->
        <header class="h-16 bg-white/60 backdrop-blur-md border-b border-zinc-200/50 flex items-center justify-between px-4 md:px-8 shrink-0 z-20">
             
             <!-- Mobile Menu Toggle -->
             <button class="md:hidden p-2 text-zinc-500 hover:text-zinc-800">
                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
             </button>

             <div class="hidden md:flex items-center gap-8 text-sm font-semibold text-zinc-400">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'text-zinc-900 border-b-2 border-emerald-500' : 'hover:text-zinc-900' }} py-5 transition-colors">Dashboard</a>
                <a href="{{ route('dashboard.sensors') }}" class="{{ request()->routeIs('dashboard.sensors') ? 'text-zinc-900 border-b-2 border-emerald-500' : 'hover:text-zinc-900' }} py-5 transition-colors">Sensors</a>
                <a href="{{ route('dashboard.analytics') }}" class="{{ request()->routeIs('dashboard.analytics') ? 'text-zinc-900 border-b-2 border-emerald-500' : 'hover:text-zinc-900' }} py-5 transition-colors">Predictions</a>
                <a href="{{ route('dashboard.devices') }}" class="{{ request()->routeIs('dashboard.devices') ? 'text-zinc-900 border-b-2 border-emerald-500' : 'hover:text-zinc-900' }} py-5 transition-colors">Devices</a>
                <a href="{{ route('dashboard.history') }}" class="{{ request()->routeIs('dashboard.history') ? 'text-zinc-900 border-b-2 border-emerald-500' : 'hover:text-zinc-900' }} py-5 transition-colors">History</a>
             </div>
             
             <div class="flex items-center gap-2 md:gap-4">
                 <!-- Status Badge -->
                 <div id="global-status-badge" class="bg-emerald-50 text-emerald-600 border border-emerald-200 px-3 py-1.5 rounded-lg flex items-center gap-2 text-xs font-bold tracking-wide shadow-sm font-mono">
                     <div id="global-status-dot" class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_5px_#10b981]"></div>
                     <span id="global-status-text">STATUS: ONLINE</span>
                 </div>
                 <!-- User Avatar Mock -->
                 <div class="w-9 h-9 rounded-full bg-zinc-200 border-2 border-white shadow-sm flex items-center justify-center">
                    <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                 </div>
             </div>
        </header>

        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8 relative">
            <!-- Decorative background elements -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-emerald-50 rounded-full blur-[100px] -z-10 opacity-50"></div>
            @yield('content')
        </div>
    </main>

</body>
</html>

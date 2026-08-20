@extends('layouts.dashboard')

@section('content')
@php
    $updatedAt      = $latestSensor?->recorded_at ?? null;
    $isDisconnected = !$updatedAt || $updatedAt->diffInSeconds(now()) > 6;
@endphp
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8 relative z-10">
        <h1 class="text-3xl font-extrabold text-zinc-900 mb-1">Sensor Grid: Dual HC-SR04</h1>
        <p class="text-zinc-500 text-sm font-medium">Live telemetry from approach & exit sensors — Station 042</p>
    </div>

    <!-- LIVE STATS CARDS (diisi AJAX) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 relative z-10">

        <!-- Sensor 1 - Approach -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-6 relative overflow-hidden hover:shadow-md transition-all">
            <div class="flex justify-between items-start mb-2">
                <h3 class="text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Sensor 1 — Approach</h3>
                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
            </div>
            <div class="flex items-baseline gap-1">
                <span id="s-dist-approach" class="text-4xl font-extrabold {{ $isDisconnected ? 'text-zinc-300' : 'text-zinc-800' }} tracking-tight">{{ $isDisconnected ? '—' : (round($latestSensor->distance_cm) ?? '—') }}</span>
                <span class="text-xs text-zinc-500 font-semibold">cm</span>
            </div>
            <p class="text-[10px] text-zinc-400 mt-2 font-mono uppercase tracking-wider">Pin TRIG=11 / ECHO=12</p>
        </div>

        <!-- Sensor 2 - Exit -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-6 relative overflow-hidden hover:shadow-md transition-all">
            <div class="flex justify-between items-start mb-2">
                <h3 class="text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Sensor 2 — Exit</h3>
                <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
            </div>
            <div class="flex items-baseline gap-1">
                <span id="s-dist-exit" class="text-4xl font-extrabold {{ $isDisconnected ? 'text-zinc-300' : 'text-zinc-800' }} tracking-tight">{{ $isDisconnected ? '—' : (round($latestSensor->distance_exit_cm) ?? '—') }}</span>
                <span class="text-xs text-zinc-500 font-semibold">cm</span>
            </div>
            <p class="text-[10px] text-zinc-400 mt-2 font-mono uppercase tracking-wider">Pin TRIG=6 / ECHO=7</p>
        </div>

        <!-- Status -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-6 relative overflow-hidden hover:shadow-md transition-all">
            <h3 class="text-[10px] font-bold text-zinc-400 tracking-widest uppercase mb-2">Crossing Status</h3>
            <div id="s-status-badge" class="inline-flex items-center gap-2 {{ $isDisconnected ? 'bg-zinc-100 text-zinc-500 border-zinc-300' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }} border px-3 py-1.5 rounded-lg text-sm font-bold uppercase tracking-wide">
                <div class="w-2 h-2 {{ $isDisconnected ? 'bg-zinc-400' : 'bg-emerald-500' }} rounded-full"></div>
                {{ $isDisconnected ? 'DISCONNECTED' : 'SAFE' }}
            </div>
            <p class="text-[10px] text-zinc-400 mt-3 font-mono">From AI Prediction</p>
        </div>

        <!-- Total Records -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-6 relative overflow-hidden hover:shadow-md transition-all">
            <h3 class="text-[10px] font-bold text-zinc-400 tracking-widest uppercase mb-2">Total Readings</h3>
            <span class="text-4xl font-extrabold text-zinc-800 tracking-tight">{{ $totalReadings ?? '—' }}</span>
            <p class="text-[10px] text-zinc-400 mt-2 font-mono uppercase tracking-wider">Records in database</p>
        </div>
    </div>

    <!-- Sensor Status Visual -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 relative z-10">

        <!-- Sensor 1: Approach -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center border border-emerald-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-zinc-800">Sensor 1 — Approach Detector</h3>
                    <p class="text-xs text-zinc-400">Mendeteksi objek yang MENDEKAT ke perlintasan</p>
                </div>
            </div>
            <div class="flex items-baseline gap-2 mb-3">
                <span id="s1-val" class="text-5xl font-extrabold text-zinc-800 tracking-tight">—</span>
                <span class="text-zinc-500">cm</span>
            </div>
            <div class="w-full h-3 bg-zinc-100 rounded-full overflow-hidden mb-2">
                <div id="s1-bar" class="h-full bg-emerald-400 rounded-full transition-all duration-500" style="width: 100%"></div>
            </div>
            <div class="flex justify-between text-[10px] text-zinc-400 font-mono">
                <span>0 cm (CLOSE)</span>
                <span>THRESHOLD: 50cm</span>
                <span>400 cm (FAR)</span>
            </div>
            <div id="s1-trigger" class="mt-4 flex items-center gap-2 text-sm font-bold {{ $isDisconnected ? 'text-zinc-400' : 'text-emerald-600' }}">
                <div class="w-2.5 h-2.5 {{ $isDisconnected ? 'bg-zinc-400' : 'bg-emerald-500' }} rounded-full"></div>
                {{ $isDisconnected ? 'DISCONNECTED — No signal from Arduino' : 'CLEAR — No Object Detected' }}
            </div>
        </div>

        <!-- Sensor 2: Exit -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center border border-blue-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 17l-4 4m0 0l-4-4m4 4V3"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-zinc-800">Sensor 2 — Exit Detector</h3>
                    <p class="text-xs text-zinc-400">Mendeteksi objek yang KELUAR dari perlintasan</p>
                </div>
            </div>
            <div class="flex items-baseline gap-2 mb-3">
                <span id="s2-val" class="text-5xl font-extrabold text-zinc-800 tracking-tight">—</span>
                <span class="text-zinc-500">cm</span>
            </div>
            <div class="w-full h-3 bg-zinc-100 rounded-full overflow-hidden mb-2">
                <div id="s2-bar" class="h-full bg-blue-400 rounded-full transition-all duration-500" style="width: 100%"></div>
            </div>
            <div class="flex justify-between text-[10px] text-zinc-400 font-mono">
                <span>0 cm (CLOSE)</span>
                <span>THRESHOLD: 50cm</span>
                <span>400 cm (FAR)</span>
            </div>
            <div id="s2-trigger" class="mt-4 flex items-center gap-2 text-sm font-bold {{ $isDisconnected ? 'text-zinc-400' : 'text-blue-600' }}">
                <div class="w-2.5 h-2.5 {{ $isDisconnected ? 'bg-zinc-400' : 'bg-blue-500' }} rounded-full"></div>
                {{ $isDisconnected ? 'DISCONNECTED — No signal from Arduino' : 'STANDBY — Waiting for Exit Signal' }}
            </div>
        </div>
    </div>

    <!-- Recent Readings Table -->
    <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm overflow-hidden relative z-10">
        <div class="p-6 border-b border-zinc-100 flex items-center justify-between">
            <h3 class="font-bold text-zinc-800">Recent Sensor Readings</h3>
            <span class="text-xs text-zinc-400 font-mono">Last {{ $recentReadings?->count() ?? 0 }} records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Time</th>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Distance (cm)</th>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Event</th>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($recentReadings ?? [] as $r)
                    <tr class="hover:bg-zinc-50 transition-colors">
                        <td class="px-6 py-3 font-mono text-zinc-500 text-xs">
                            {{ $r->recorded_at ? $r->recorded_at->format('H:i:s') : $r->created_at->format('H:i:s') }}
                        </td>
                        <td class="px-6 py-3 font-bold {{ $r->distance_cm < 50 ? 'text-red-600' : ($r->distance_cm < 150 ? 'text-amber-600' : 'text-zinc-800') }}">
                            {{ number_format($r->distance_cm, 1) }} cm
                        </td>
                        <td class="px-6 py-3 text-zinc-500 font-mono text-xs">{{ $r->event_type ?? 'reading' }}</td>
                        <td class="px-6 py-3">
                            @php
                                $sc = match($r->status) { 'danger' => 'red', 'warning' => 'amber', default => 'emerald' };
                            @endphp
                            <span class="inline-flex items-center gap-1 bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">
                                <div class="w-1.5 h-1.5 bg-{{ $sc }}-500 rounded-full"></div>
                                {{ strtoupper($r->status ?? 'normal') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-zinc-400 text-sm">No sensor data yet. Connect Arduino to start.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const SENSOR_STALE_MS = 6000;

function setSensorsDisconnected() {
    document.getElementById('s-dist-approach').innerText = '—';
    document.getElementById('s-dist-approach').className = 'text-4xl font-extrabold text-zinc-300 tracking-tight';
    document.getElementById('s-dist-exit').innerText = '—';
    document.getElementById('s-dist-exit').className = 'text-4xl font-extrabold text-zinc-300 tracking-tight';

    const badge = document.getElementById('s-status-badge');
    badge.className = 'inline-flex items-center gap-2 bg-zinc-100 text-zinc-500 border border-zinc-300 px-3 py-1.5 rounded-lg text-sm font-bold uppercase tracking-wide';
    badge.innerHTML = '<div class="w-2 h-2 bg-zinc-400 rounded-full"></div>DISCONNECTED';

    ['s1', 's2'].forEach(prefix => {
        const el  = document.getElementById(prefix + '-val');
        const bar = document.getElementById(prefix + '-bar');
        const trig = document.getElementById(prefix + '-trigger');
        el.innerText = '—';
        el.className = 'text-5xl font-extrabold text-zinc-300 tracking-tight';
        bar.style.width = '0%';
        bar.className = 'h-full bg-zinc-300 rounded-full transition-all duration-500';
        trig.innerHTML = '<div class="w-2.5 h-2.5 bg-zinc-400 rounded-full"></div><span class="text-zinc-400">DISCONNECTED — No signal from Arduino</span>';
    });
}

function fetchSensorData() {
    fetch('/api/v1/dashboard/latest')
        .then(r => r.json())
        .then(data => {
            if (!data.success) { setSensorsDisconnected(); return; }
            const sensor = data.latestSensor;
            const pred   = data.latestPrediction;

            const ageMs = sensor?.recorded_at ? (new Date(data.server_time) - new Date(sensor.recorded_at)) : Infinity;
            if (!sensor || ageMs > SENSOR_STALE_MS) { setSensorsDisconnected(); return; }

            const dA = Math.round(sensor.distance_cm || 999);
            const dE = Math.round(sensor.distance_exit_cm || 999);

            // Cards
            document.getElementById('s-dist-approach').innerText = dA < 999 ? dA : '—';
            document.getElementById('s-dist-exit').innerText = dE < 999 ? dE : '—';

            // Sensor 1 bar & trigger
            const s1El = document.getElementById('s1-val');
            const s1Bar = document.getElementById('s1-bar');
            const s1Trig = document.getElementById('s1-trigger');
            s1El.innerText = dA < 999 ? dA : '—';
            s1Bar.style.width = dA < 999 ? Math.min(100, (dA/400)*100) + '%' : '100%';
            if (dA <= 50) {
                s1El.className = 'text-5xl font-extrabold text-red-600 tracking-tight animate-pulse';
                s1Bar.className = 'h-full bg-red-500 rounded-full transition-all duration-500';
                s1Trig.innerHTML = '<div class="w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse"></div><span class="text-red-600">🚨 OBJECT DETECTED — GATE CLOSING!</span>';
            } else {
                s1El.className = 'text-5xl font-extrabold text-zinc-800 tracking-tight';
                s1Bar.className = 'h-full bg-emerald-400 rounded-full transition-all duration-500';
                s1Trig.innerHTML = '<div class="w-2.5 h-2.5 bg-emerald-500 rounded-full"></div><span class="text-emerald-600">CLEAR — No Object Detected</span>';
            }

            // Sensor 2 bar & trigger
            const s2El = document.getElementById('s2-val');
            const s2Bar = document.getElementById('s2-bar');
            const s2Trig = document.getElementById('s2-trigger');
            s2El.innerText = dE < 999 ? dE : '—';
            s2Bar.style.width = dE < 999 ? Math.min(100, (dE/400)*100) + '%' : '100%';
            if (dE <= 50) {
                s2El.className = 'text-5xl font-extrabold text-blue-600 tracking-tight animate-pulse';
                s2Bar.className = 'h-full bg-blue-600 rounded-full transition-all duration-500';
                s2Trig.innerHTML = '<div class="w-2.5 h-2.5 bg-blue-600 rounded-full animate-pulse"></div><span class="text-blue-700">✅ EXIT DETECTED — GATE OPENING!</span>';
            } else {
                s2El.className = 'text-5xl font-extrabold text-zinc-800 tracking-tight';
                s2Bar.className = 'h-full bg-blue-400 rounded-full transition-all duration-500';
                s2Trig.innerHTML = '<div class="w-2.5 h-2.5 bg-blue-500 rounded-full"></div><span class="text-blue-600">STANDBY — Waiting for Exit Signal</span>';
            }

            // Status badge
            const status = pred ? pred.crossing_status : 'safe';
            const badge = document.getElementById('s-status-badge');
            if (status === 'danger') {
                badge.className = 'inline-flex items-center gap-2 bg-red-50 text-red-700 border border-red-200 px-3 py-1.5 rounded-lg text-sm font-bold uppercase tracking-wide';
                badge.innerHTML = '<div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>DANGER';
            } else if (status === 'warning') {
                badge.className = 'inline-flex items-center gap-2 bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1.5 rounded-lg text-sm font-bold uppercase tracking-wide';
                badge.innerHTML = '<div class="w-2 h-2 bg-amber-500 rounded-full"></div>WARNING';
            } else {
                badge.className = 'inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-lg text-sm font-bold uppercase tracking-wide';
                badge.innerHTML = '<div class="w-2 h-2 bg-emerald-500 rounded-full"></div>SAFE';
            }
        })
        .catch(() => setSensorsDisconnected());
}
document.addEventListener('DOMContentLoaded', () => {
    fetchSensorData();
    setInterval(fetchSensorData, 800);
});
</script>
@endsection

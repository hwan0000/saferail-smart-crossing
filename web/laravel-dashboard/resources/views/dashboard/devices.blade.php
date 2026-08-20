@extends('layouts.dashboard')

@section('content')
<div class="max-w-6xl mx-auto">

    <!-- Header -->
    <div class="flex justify-between items-end mb-8 relative z-10">
        <div>
            <h1 class="text-4xl font-extrabold text-zinc-900 mb-2 tracking-tight">Device Control Panel</h1>
            <p class="text-zinc-500 font-medium">Real-time actuator status & signal control for Station 042.</p>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 bg-zinc-900 text-white rounded-xl shadow-lg border border-zinc-700 text-sm font-bold">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7z"></path></svg>
            AUTO MODE
        </div>
    </div>

    <!-- Live Device Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 relative z-10">

        <!-- Barrier Gate -->
        <div class="bg-white/80 backdrop-blur-md rounded-3xl border border-white shadow-sm p-8 relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center border border-blue-100 shadow-inner">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold text-zinc-900">Barrier Gate (Servo)</h2>
                        <p class="text-zinc-500 text-sm font-medium mt-0.5">SG90 Servo Motor — Pin 3</p>
                    </div>
                </div>
            </div>

            <div class="bg-zinc-50 rounded-2xl p-6 mb-6 border border-zinc-100">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Gate Position</span>
                    <div id="dev-barrier-badge" class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-lg text-xs font-bold uppercase">RAISED (OPEN)</div>
                </div>
                <div class="w-full h-4 bg-zinc-200 rounded-full overflow-hidden">
                    <div id="dev-barrier-bar" class="h-full bg-emerald-400 rounded-full transition-all duration-700" style="width: 100%"></div>
                </div>
                <div class="flex justify-between text-[10px] text-zinc-400 font-mono mt-2">
                    <span>0° (CLOSED)</span>
                    <span>90° (OPEN)</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white rounded-xl border border-zinc-100 p-4 text-center">
                    <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1">Voltage</div>
                    <div class="text-xl font-bold text-zinc-700">12.4V</div>
                </div>
                <div class="bg-white rounded-xl border border-zinc-100 p-4 text-center">
                    <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1">Mode</div>
                    <div class="text-xl font-bold text-zinc-700">AUTO</div>
                </div>
            </div>
        </div>

        <!-- Traffic Signal -->
        <div class="bg-white/80 backdrop-blur-md rounded-3xl border border-white shadow-sm p-8 relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-zinc-100 text-zinc-600 rounded-2xl flex items-center justify-center border border-zinc-200 shadow-inner">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold text-zinc-900">Traffic LED Signal</h2>
                        <p class="text-zinc-500 text-sm font-medium mt-0.5">3-LED Traffic Light — Pin 8/9/10</p>
                    </div>
                </div>
            </div>

            <!-- Traffic Light Visual -->
            <div class="bg-zinc-900 rounded-2xl p-6 mb-6 flex justify-center">
                <div class="flex flex-col gap-3">
                    <div id="tl-red" class="w-14 h-14 rounded-full bg-zinc-700 border-2 border-zinc-600 transition-all duration-500 flex items-center justify-center text-xs font-bold text-zinc-500">R</div>
                    <div id="tl-yellow" class="w-14 h-14 rounded-full bg-zinc-700 border-2 border-zinc-600 transition-all duration-500 flex items-center justify-center text-xs font-bold text-zinc-500">Y</div>
                    <div id="tl-green" class="w-14 h-14 rounded-full bg-emerald-500 border-2 border-emerald-400 shadow-[0_0_20px_rgba(16,185,129,0.6)] transition-all duration-500 flex items-center justify-center text-xs font-bold text-white">G</div>
                </div>
            </div>

            <div class="text-center">
                <div id="dev-signal-badge" class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-2 rounded-xl font-bold text-sm uppercase tracking-wide">
                    <div class="w-2.5 h-2.5 bg-emerald-500 rounded-full"></div>
                    GREEN — PROCEED
                </div>
            </div>
        </div>
    </div>

    <!-- Buzzer & System Status -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 relative z-10">
        <div class="bg-white/80 rounded-2xl border border-white shadow-sm p-6 text-center">
            <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-3">Buzzer</div>
            <div id="dev-buzzer" class="text-xl font-bold text-zinc-300">SILENT</div>
            <p class="text-xs text-zinc-400 mt-1">Pin 2 — Piezo</p>
        </div>
        <div class="bg-white/80 rounded-2xl border border-white shadow-sm p-6 text-center">
            <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-3">Crossing Status</div>
            <div id="dev-crossing-status" class="text-xl font-bold text-emerald-600">SAFE</div>
        </div>
        <div class="bg-white/80 rounded-2xl border border-white shadow-sm p-6 text-center">
            <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-3">System Uptime</div>
            <div class="text-xl font-bold text-zinc-800 font-mono" id="dev-uptime">—</div>
        </div>
    </div>

    <!-- Device Event History -->
    <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm overflow-hidden relative z-10">
        <div class="p-6 border-b border-zinc-100">
            <h3 class="font-bold text-zinc-800">Device State History</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Time</th>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Device</th>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">State</th>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Mode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($deviceHistory ?? [] as $d)
                    <tr class="hover:bg-zinc-50 transition-colors">
                        <td class="px-6 py-3 font-mono text-zinc-500 text-xs">{{ ($d->changed_at ?? $d->created_at)?->format('d/m H:i:s') }}</td>
                        <td class="px-6 py-3 font-bold text-zinc-700">{{ str_replace('_', ' ', ucfirst($d->device_name)) }}</td>
                        <td class="px-6 py-3">
                            @php $dc = in_array($d->state, ['closed','red']) ? 'red' : (in_array($d->state, ['yellow']) ? 'amber' : 'emerald'); @endphp
                            <span class="inline-flex items-center gap-1 bg-{{ $dc }}-50 text-{{ $dc }}-700 border border-{{ $dc }}-200 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">
                                {{ strtoupper($d->state) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-zinc-500 font-mono text-xs uppercase">{{ $d->mode ?? 'auto' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-zinc-400 text-sm">No device events yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let startTime = Date.now();
function fetchDeviceData() {
    fetch('/api/v1/dashboard/latest')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const bGate = data.barrierStatus;
            const tSig  = data.signalStatus;
            const pred  = data.latestPrediction;
            const status = pred ? pred.crossing_status : 'safe';

            // Barrier
            const bBadge = document.getElementById('dev-barrier-badge');
            const bBar   = document.getElementById('dev-barrier-bar');
            const barrierState = (status === 'danger') ? 'closed' : (bGate ? bGate.state : 'open');
            if (barrierState === 'closed') {
                bBadge.className = 'bg-red-50 text-red-700 border border-red-200 px-3 py-1 rounded-lg text-xs font-bold uppercase';
                bBadge.innerText = 'LOWERED (CLOSED)';
                bBar.style.width = '0%';
                bBar.className = 'h-full bg-red-500 rounded-full transition-all duration-700';
            } else {
                bBadge.className = 'bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-lg text-xs font-bold uppercase';
                bBadge.innerText = 'RAISED (OPEN)';
                bBar.style.width = '100%';
                bBar.className = 'h-full bg-emerald-400 rounded-full transition-all duration-700';
            }

            // Traffic Light Visual
            const red    = document.getElementById('tl-red');
            const yellow = document.getElementById('tl-yellow');
            const green  = document.getElementById('tl-green');
            const sBadge = document.getElementById('dev-signal-badge');
            const sigState = (status === 'danger') ? 'red' : ((status === 'warning') ? 'yellow' : (tSig ? tSig.state : 'green'));

            // Reset all
            red.className    = 'w-14 h-14 rounded-full bg-zinc-700 border-2 border-zinc-600 transition-all duration-500 flex items-center justify-center text-xs font-bold text-zinc-500';
            yellow.className = 'w-14 h-14 rounded-full bg-zinc-700 border-2 border-zinc-600 transition-all duration-500 flex items-center justify-center text-xs font-bold text-zinc-500';
            green.className  = 'w-14 h-14 rounded-full bg-zinc-700 border-2 border-zinc-600 transition-all duration-500 flex items-center justify-center text-xs font-bold text-zinc-500';

            if (sigState === 'red') {
                red.className = 'w-14 h-14 rounded-full bg-red-500 border-2 border-red-400 shadow-[0_0_20px_rgba(239,68,68,0.6)] transition-all duration-500 flex items-center justify-center text-xs font-bold text-white animate-pulse';
                sBadge.className = 'inline-flex items-center gap-2 bg-red-50 text-red-700 border border-red-200 px-4 py-2 rounded-xl font-bold text-sm uppercase tracking-wide';
                sBadge.innerHTML = '<div class="w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse"></div>RED — STOP';
            } else if (sigState === 'yellow') {
                yellow.className = 'w-14 h-14 rounded-full bg-amber-400 border-2 border-amber-300 shadow-[0_0_20px_rgba(251,191,36,0.6)] transition-all duration-500 flex items-center justify-center text-xs font-bold text-white';
                sBadge.className = 'inline-flex items-center gap-2 bg-amber-50 text-amber-700 border border-amber-200 px-4 py-2 rounded-xl font-bold text-sm uppercase tracking-wide';
                sBadge.innerHTML = '<div class="w-2.5 h-2.5 bg-amber-500 rounded-full"></div>YELLOW — CAUTION';
            } else {
                green.className = 'w-14 h-14 rounded-full bg-emerald-500 border-2 border-emerald-400 shadow-[0_0_20px_rgba(16,185,129,0.6)] transition-all duration-500 flex items-center justify-center text-xs font-bold text-white';
                sBadge.className = 'inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-2 rounded-xl font-bold text-sm uppercase tracking-wide';
                sBadge.innerHTML = '<div class="w-2.5 h-2.5 bg-emerald-500 rounded-full"></div>GREEN — PROCEED';
            }

            // Buzzer, Crossing Status
            const buzzer = document.getElementById('dev-buzzer');
            const csEl   = document.getElementById('dev-crossing-status');
            if (status === 'danger') {
                buzzer.innerText = '🔊 BEEPING';
                buzzer.className = 'text-xl font-bold text-red-600 animate-pulse';
                csEl.innerText = 'DANGER';
                csEl.className = 'text-xl font-bold text-red-600 animate-pulse';
            } else if (status === 'warning') {
                buzzer.innerText = '🔔 ALERT';
                buzzer.className = 'text-xl font-bold text-amber-500';
                csEl.innerText = 'WARNING';
                csEl.className = 'text-xl font-bold text-amber-500';
            } else {
                buzzer.innerText = 'SILENT';
                buzzer.className = 'text-xl font-bold text-zinc-300';
                csEl.innerText = 'SAFE';
                csEl.className = 'text-xl font-bold text-emerald-600';
            }

            // Uptime
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const m = Math.floor(elapsed / 60);
            const s = elapsed % 60;
            document.getElementById('dev-uptime').innerText = m + 'm ' + s + 's';
        });
}
document.addEventListener('DOMContentLoaded', () => {
    fetchDeviceData();
    setInterval(fetchDeviceData, 800);
});
</script>
@endsection

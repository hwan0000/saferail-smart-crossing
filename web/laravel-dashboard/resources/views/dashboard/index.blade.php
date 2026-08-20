@extends('layouts.dashboard')

@section('content')
@php
    // Same staleness rule as the JS poller (STALE_MS below): no row ever
    // received, or the newest one is older than this, means Node-RED /
    // Arduino isn't talking to Laravel right now -- render that truthfully
    // instead of defaulting to "safe" on the very first paint.
    $updatedAt      = $latestSensor?->recorded_at ?? $latestPrediction?->predicted_at ?? null;
    $isDisconnected = !$updatedAt || $updatedAt->diffInSeconds(now()) > 6;

    $status       = $isDisconnected ? 'disconnected' : ($latestPrediction?->crossing_status ?? $latestSensor?->status ?? 'safe');
    $confidence   = $latestPrediction?->confidence ?? 0;
    $barrierState = $isDisconnected ? null : ($barrierStatus?->state ?? 'open');
    $signalState  = $isDisconnected ? null : ($signalStatus?->state ?? 'green');
@endphp

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between items-start md:items-end gap-4 mb-8 relative z-10">
        <div>
            <h1 class="text-4xl font-extrabold text-zinc-900 mb-2 tracking-tight">Crossing Status</h1>
            <p class="text-zinc-500 font-medium">Real-time condition overview & AI Monitoring</p>
        </div>
        <div class="flex items-center gap-4 text-sm font-medium">
            <div class="flex items-center gap-3 bg-white border border-zinc-200/60 px-5 py-2.5 rounded-xl shadow-sm text-zinc-600">
                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="font-mono" id="live-updated-at">
                    @if($updatedAt)
                        Updated: {{ $updatedAt->diffForHumans() }}
                    @else
                        Connecting to Arduino...
                    @endif
                </span>
                <div class="w-px h-4 bg-zinc-300 mx-1"></div>
                <div class="flex items-center gap-2 {{ $isDisconnected ? 'text-zinc-400' : 'text-emerald-600' }} font-bold" id="live-online-status">
                    <div id="live-online-dot" class="w-2.5 h-2.5 {{ $isDisconnected ? 'bg-zinc-400' : 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)] animate-pulse' }} rounded-full"></div>
                    <span id="live-online-text">{{ $isDisconnected ? 'Disconnected' : 'System Active' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 relative z-10">
        <!-- Main Status Card -->
        <div id="main-status-card" class="lg:col-span-2 bg-white/80 backdrop-blur-md rounded-3xl border border-white shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-12 flex flex-col items-center justify-center text-center relative overflow-hidden transition-all duration-500">
            
            @php
                $boxClasses = match($status) {
                    'danger'  => 'bg-red-50 text-red-500 border-red-200 scale-110',
                    'warning' => 'bg-amber-50 text-amber-500 border-amber-200',
                    'disconnected' => 'bg-zinc-100 text-zinc-400 border-zinc-200',
                    default   => 'bg-emerald-50 text-emerald-500 border-emerald-100 -rotate-3',
                };
                $labelClasses = match($status) {
                    'danger'  => 'text-red-600 animate-pulse',
                    'warning' => 'text-amber-500',
                    'disconnected' => 'text-zinc-400',
                    default   => 'text-emerald-600',
                };
                $labelText = match($status) {
                    'danger' => 'DANGER',
                    'warning' => 'WARNING',
                    'disconnected' => 'DISCONNECTED',
                    default => 'SAFE',
                };
                $descText = match($status) {
                    'danger' => 'Obstacle / Train detected! Gate closed, Red Light ACTIVE.',
                    'warning' => 'Object approaching crossing. Caution, preparing to close.',
                    'disconnected' => 'No data from Node-RED / Arduino. Last known state may be stale.',
                    default => 'No approaching train or obstacle detected.',
                };
            @endphp
            <div id="status-icon-box" class="w-28 h-28 {{ $boxClasses }} rounded-3xl flex items-center justify-center mb-6 shadow-inner border transform transition-all duration-500 relative">
                <svg id="status-icon-svg" class="w-14 h-14 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <h2 id="status-label" class="text-6xl font-black {{ $labelClasses }} tracking-tight mb-4 transition-colors duration-500">{{ $labelText }}</h2>
            <p id="status-desc" class="text-xl text-zinc-600 font-medium mb-3 transition-all duration-500">{{ $descText }}</p>

            <p class="text-sm text-zinc-400 font-semibold bg-zinc-50 px-4 py-1.5 rounded-full border border-zinc-100">
                AI Confidence: <span id="status-confidence" class="text-emerald-500 font-bold">98.0%</span>
                &nbsp;·&nbsp; Mode: <span class="font-mono text-zinc-600">AUTOMATIC</span>
            </p>
        </div>

        <!-- Right Side Cards -->
        <div class="flex flex-col gap-6">
            @php
                $entrySide = $isDisconnected ? null : $latestSensor?->entry_side;
                $tagFor = fn($side) => match(true) {
                    $entrySide === $side => ['ENTRY', 'bg-red-50 text-red-600 border-red-200'],
                    $entrySide !== null  => ['EXIT', 'bg-blue-50 text-blue-600 border-blue-200'],
                    default              => [null, ''],
                };
                [$tagA, $tagAClass] = $tagFor('A');
                [$tagB, $tagBClass] = $tagFor('B');
            @endphp
            <!-- Sensor A Card -->
            <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-6 flex-1 flex flex-col hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-2">
                        <h3 class="text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Sensor A</h3>
                        <span id="sensor-a-tag" class="text-[9px] font-bold px-1.5 py-0.5 rounded border {{ $tagAClass }} {{ $tagA ? '' : 'hidden' }}">{{ $tagA }}</span>
                    </div>
                    <div class="p-1.5 bg-zinc-50 rounded-lg">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1 mt-auto">
                    <span id="distance-val" class="text-5xl font-extrabold text-zinc-800 tracking-tight">--</span>
                    <span class="text-zinc-500 font-medium">cm</span>
                </div>
                <div class="w-full h-2 bg-zinc-100 rounded-full mt-6 overflow-hidden">
                    <div id="distance-bar" class="h-full bg-emerald-400 rounded-full transition-all duration-500" style="width: 100%"></div>
                </div>
            </div>

            <!-- Sensor B Card -->
            <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-6 flex-1 flex flex-col hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-2">
                        <h3 class="text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Sensor B</h3>
                        <span id="sensor-b-tag" class="text-[9px] font-bold px-1.5 py-0.5 rounded border {{ $tagBClass }} {{ $tagB ? '' : 'hidden' }}">{{ $tagB }}</span>
                    </div>
                    <div class="p-1.5 bg-zinc-50 rounded-lg">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1 mt-auto">
                    <span id="exit-distance-val" class="text-5xl font-extrabold text-zinc-800 tracking-tight">--</span>
                    <span class="text-zinc-500 font-medium">cm</span>
                </div>
                <div class="w-full h-2 bg-zinc-100 rounded-full mt-6 overflow-hidden">
                    <div id="exit-distance-bar" class="h-full bg-blue-400 rounded-full transition-all duration-500" style="width: 100%"></div>
                </div>
            </div>
        </div>


    </div>

    <!-- Infrastructure Section -->
    <div class="flex items-center gap-3 mb-6 relative z-10">
        <h3 class="text-xl font-extrabold text-zinc-800">Actuator & Signal Status</h3>
        <div class="h-px bg-zinc-200 flex-1"></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
        <!-- Barrier -->
        <div class="bg-white rounded-2xl border border-zinc-100 shadow-sm p-6 flex items-center justify-between transition-colors">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 bg-blue-50/50 text-blue-500 rounded-xl flex items-center justify-center border border-blue-100">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-zinc-800 text-lg">Barrier Gate (Servo)</h4>
                    <p class="text-xs text-zinc-500 font-medium mt-0.5">Physical Gate Motor</p>
                </div>
            </div>
            <div class="text-right bg-zinc-50 px-4 py-2 rounded-xl border border-zinc-100">
                <div id="barrier-label" class="font-bold {{ $isDisconnected ? 'text-zinc-400' : ($barrierState === 'closed' ? 'text-red-600' : 'text-emerald-600') }} text-sm tracking-wide">{{ $isDisconnected ? 'UNKNOWN' : ($barrierState === 'closed' ? 'LOWERED (CLOSED)' : 'RAISED (OPEN)') }}</div>
                <div class="text-[10px] text-zinc-500 flex items-center justify-end gap-1.5 mt-1 font-mono uppercase tracking-wider">
                    <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></div>
                    Auto Mode
                </div>
            </div>
        </div>

        <!-- Traffic Signal -->
        <div class="bg-white rounded-2xl border border-zinc-100 shadow-sm p-6 flex items-center justify-between transition-colors">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 bg-zinc-50 text-zinc-500 rounded-xl flex items-center justify-center border border-zinc-100">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <h4 class="font-extrabold text-zinc-800 text-lg">Traffic LED Signal</h4>
                    <p class="text-xs text-zinc-500 font-medium mt-0.5">Green / Yellow / Red Lamp</p>
                </div>
            </div>
            <div class="text-right bg-zinc-50 px-4 py-2 rounded-xl border border-zinc-100">
                @php
                    $signalClass = match(true) {
                        $isDisconnected => 'text-zinc-400',
                        $signalState === 'red' => 'text-red-600',
                        $signalState === 'yellow' => 'text-amber-500',
                        default => 'text-emerald-600',
                    };
                    $signalText = match(true) {
                        $isDisconnected => 'UNKNOWN',
                        $signalState === 'red' => 'RED / STOP',
                        $signalState === 'yellow' => 'YELLOW / CAUTION',
                        default => 'GREEN / PROCEED',
                    };
                @endphp
                <div id="signal-label" class="font-bold {{ $signalClass }} text-sm tracking-wide">{{ $signalText }}</div>
                <div class="text-[10px] text-zinc-500 flex items-center justify-end gap-1.5 mt-1 font-mono uppercase tracking-wider">
                    <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></div>
                    Auto Mode
                </div>
            </div>
        </div>
    </div>
</div>

<!-- REAL-TIME JAVASCRIPT AJAX POLLING (NO PAGE RELOAD) -->
<script>
// Device is considered disconnected if the newest row in the DB is older
// than this, OR the /dashboard/latest request itself fails. Arduino sends
// at least a heartbeat every 2s, so anything well past that means Node-RED
// (or the Arduino) stopped talking to Laravel -- not that the crossing is safe.
const STALE_MS = 6000;

function updateEntryTags(entrySide) {
    const tagA = document.getElementById('sensor-a-tag');
    const tagB = document.getElementById('sensor-b-tag');
    if (!entrySide || entrySide === 'none') {
        tagA.className = 'text-[9px] font-bold px-1.5 py-0.5 rounded border hidden';
        tagB.className = 'text-[9px] font-bold px-1.5 py-0.5 rounded border hidden';
        return;
    }
    const entryClass = 'text-[9px] font-bold px-1.5 py-0.5 rounded border bg-red-50 text-red-600 border-red-200';
    const exitClass  = 'text-[9px] font-bold px-1.5 py-0.5 rounded border bg-blue-50 text-blue-600 border-blue-200';
    tagA.innerText = entrySide === 'A' ? 'ENTRY' : 'EXIT';
    tagA.className = entrySide === 'A' ? entryClass : exitClass;
    tagB.innerText = entrySide === 'B' ? 'ENTRY' : 'EXIT';
    tagB.className = entrySide === 'B' ? entryClass : exitClass;
}

function setDisconnected() {
    const statusLabel = document.getElementById('status-label');
    const statusDesc  = document.getElementById('status-desc');
    const statusBox   = document.getElementById('status-icon-box');
    statusLabel.innerText = 'DISCONNECTED';
    statusLabel.className = 'text-6xl font-black text-zinc-400 tracking-tight mb-4';
    statusDesc.innerText = 'No data from Node-RED / Arduino. Last known state may be stale.';
    statusBox.className = 'w-28 h-28 bg-zinc-100 text-zinc-400 rounded-3xl flex items-center justify-center mb-6 shadow-inner border border-zinc-200 transition-all duration-500';

    ['distance-val', 'exit-distance-val'].forEach(id => {
        const el = document.getElementById(id);
        el.innerText = '—';
        el.className = 'text-5xl font-extrabold text-zinc-300 tracking-tight';
    });
    ['distance-bar', 'exit-distance-bar'].forEach(id => {
        const el = document.getElementById(id);
        el.style.width = '0%';
        el.className = 'h-full bg-zinc-300 rounded-full transition-all duration-500';
    });

    ['barrier-label', 'signal-label'].forEach(id => {
        const el = document.getElementById(id);
        el.innerText = 'UNKNOWN';
        el.className = 'font-bold text-zinc-400 text-sm tracking-wide';
    });

    updateEntryTags(null);

    document.getElementById('live-updated-at').innerText = 'Disconnected';
    document.getElementById('live-online-status').className = 'flex items-center gap-2 text-zinc-400 font-bold';
    document.getElementById('live-online-dot').className = 'w-2.5 h-2.5 bg-zinc-400 rounded-full';
    document.getElementById('live-online-text').innerText = 'Disconnected';

    const badge = document.getElementById('global-status-badge');
    const dot   = document.getElementById('global-status-dot');
    const text  = document.getElementById('global-status-text');
    if (badge) badge.className = 'bg-zinc-100 text-zinc-500 border border-zinc-300 px-3 py-1.5 rounded-lg flex items-center gap-2 text-xs font-bold tracking-wide shadow-sm font-mono';
    if (dot) dot.className = 'w-2 h-2 bg-zinc-400 rounded-full';
    if (text) text.innerText = 'STATUS: DISCONNECTED';
}

function fetchRealtimeData() {
    fetch('/api/v1/dashboard/latest')
        .then(response => response.json())
        .then(data => {
            if (!data.success) { setDisconnected(); return; }

            const pred   = data.latestPrediction;
            const sensor = data.latestSensor;
            const bGate  = data.barrierStatus;
            const tSig   = data.signalStatus;

            // No data has ever arrived, or the newest row is too old --
            // treat as disconnected rather than assuming "safe".
            const latestTimestamp = sensor?.recorded_at ?? pred?.predicted_at ?? null;
            const ageMs = latestTimestamp ? (new Date(data.server_time) - new Date(latestTimestamp)) : Infinity;
            if (!latestTimestamp || ageMs > STALE_MS) {
                setDisconnected();
                return;
            }

            // 1. Update Sensor A Distance
            if (sensor && sensor.distance_cm !== undefined) {
                const dist = Math.round(sensor.distance_cm);
                const distEl = document.getElementById('distance-val');
                distEl.innerText = dist < 999 ? dist : '—';

                const distBar = document.getElementById('distance-bar');
                const pct = dist < 999 ? Math.min(100, Math.max(5, (dist / 400) * 100)) : 100;
                distBar.style.width = pct + '%';

                if (dist <= 50) {
                    distEl.className = "text-5xl font-extrabold text-red-600 animate-pulse tracking-tight";
                    distBar.className = "h-full bg-red-500 rounded-full transition-all duration-500";
                } else if (dist <= 150) {
                    distEl.className = "text-5xl font-extrabold text-amber-500 tracking-tight";
                    distBar.className = "h-full bg-amber-400 rounded-full transition-all duration-500";
                } else {
                    distEl.className = "text-5xl font-extrabold text-zinc-800 tracking-tight";
                    distBar.className = "h-full bg-emerald-400 rounded-full transition-all duration-500";
                }
            }

            // 1.2 Update Sensor B Distance
            if (sensor && sensor.distance_exit_cm !== undefined) {
                const distExit = Math.round(sensor.distance_exit_cm);
                const distExitEl = document.getElementById('exit-distance-val');
                distExitEl.innerText = distExit < 999 ? distExit : '—';

                const distExitBar = document.getElementById('exit-distance-bar');
                const pctExit = distExit < 999 ? Math.min(100, Math.max(5, (distExit / 400) * 100)) : 100;
                distExitBar.style.width = pctExit + '%';

                if (distExit <= 50) {
                    distExitEl.className = "text-5xl font-extrabold text-blue-600 animate-pulse tracking-tight";
                    distExitBar.className = "h-full bg-blue-600 rounded-full transition-all duration-500";
                } else if (distExit <= 150) {
                    distExitEl.className = "text-5xl font-extrabold text-blue-400 tracking-tight";
                    distExitBar.className = "h-full bg-blue-400 rounded-full transition-all duration-500";
                } else {
                    distExitEl.className = "text-5xl font-extrabold text-zinc-800 tracking-tight";
                    distExitBar.className = "h-full bg-zinc-300 rounded-full transition-all duration-500";
                }
            }

            updateEntryTags(sensor?.entry_side ?? null);

            // 2. Status Main Card -- trust what the device/server already
            // computed (debounced + fail-safe on the Arduino side). Do NOT
            // recompute from raw distance here; a duplicate naive threshold
            // client-side is exactly the bug that made the server-side one
            // unreliable for bidirectional crossings.
            const status = pred?.crossing_status ?? sensor?.status ?? 'safe';
            const statusLabel = document.getElementById('status-label');
            const statusDesc  = document.getElementById('status-desc');
            const statusBox   = document.getElementById('status-icon-box');
            const statusConf  = document.getElementById('status-confidence');
            if (status === 'danger') {
                statusLabel.innerText = 'DANGER';
                statusLabel.className = 'text-6xl font-black text-red-600 tracking-tight mb-4 animate-pulse';
                statusDesc.innerText = 'Obstacle / Train detected! Gate closed, Red Light ACTIVE.';
                statusBox.className = 'w-28 h-28 bg-red-50 text-red-500 rounded-3xl flex items-center justify-center mb-6 shadow-inner border border-red-200 transform scale-110 transition-all duration-500';
            } else if (status === 'warning') {
                statusLabel.innerText = 'WARNING';
                statusLabel.className = 'text-6xl font-black text-amber-500 tracking-tight mb-4';
                statusDesc.innerText = 'Object approaching crossing. Caution, preparing to close.';
                statusBox.className = 'w-28 h-28 bg-amber-50 text-amber-500 rounded-3xl flex items-center justify-center mb-6 shadow-inner border border-amber-200 transition-all duration-500';
            } else {
                statusLabel.innerText = 'SAFE';
                statusLabel.className = 'text-6xl font-black text-emerald-600 tracking-tight mb-4';
                statusDesc.innerText = 'No approaching train or obstacle detected.';
                statusBox.className = 'w-28 h-28 bg-emerald-50 text-emerald-500 rounded-3xl flex items-center justify-center mb-6 shadow-inner border border-emerald-100 transition-all duration-500';
            }

            if (pred && pred.confidence) {
                statusConf.innerText = parseFloat(pred.confidence).toFixed(1) + '%';
            }

            // 3. Barrier & Signal Labels -- also trust the reported device state directly.
            const bLabel = document.getElementById('barrier-label');
            const barrierState = bGate ? bGate.state : 'open';
            if (barrierState === 'closed') {
                bLabel.innerText = 'LOWERED (CLOSED)';
                bLabel.className = 'font-bold text-red-600 text-sm tracking-wide';
            } else {
                bLabel.innerText = 'RAISED (OPEN)';
                bLabel.className = 'font-bold text-emerald-600 text-sm tracking-wide';
            }

            const sLabel = document.getElementById('signal-label');
            const signalState = tSig ? tSig.state : 'green';
            if (signalState === 'red') {
                sLabel.innerText = 'RED / STOP';
                sLabel.className = 'font-bold text-red-600 text-sm tracking-wide';
            } else if (signalState === 'yellow') {
                sLabel.innerText = 'YELLOW / CAUTION';
                sLabel.className = 'font-bold text-amber-500 text-sm tracking-wide';
            } else {
                sLabel.innerText = 'GREEN / PROCEED';
                sLabel.className = 'font-bold text-emerald-600 text-sm tracking-wide';
            }

            // Connected -- restore the "System Active" / "STATUS: ONLINE" indicators.
            document.getElementById('live-online-status').className = 'flex items-center gap-2 text-emerald-600 font-bold';
            document.getElementById('live-online-dot').className = 'w-2.5 h-2.5 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.5)] animate-pulse';
            document.getElementById('live-online-text').innerText = 'System Active';

            const badge = document.getElementById('global-status-badge');
            const dot   = document.getElementById('global-status-dot');
            const text  = document.getElementById('global-status-text');
            if (badge) badge.className = 'bg-emerald-50 text-emerald-600 border border-emerald-200 px-3 py-1.5 rounded-lg flex items-center gap-2 text-xs font-bold tracking-wide shadow-sm font-mono';
            if (dot) dot.className = 'w-2 h-2 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_5px_#10b981]';
            if (text) text.innerText = 'STATUS: ONLINE';

            // Timestamp
            if (data.server_time) {
                const timeStr = new Date(data.server_time).toLocaleTimeString();
                document.getElementById('live-updated-at').innerText = 'Live: ' + timeStr;
            }
        })
        .catch(err => {
            console.error("AJAX Error:", err);
            setDisconnected();
        });
}

// Poll every 800ms for smooth real-time dynamic response
document.addEventListener('DOMContentLoaded', () => {
    fetchRealtimeData();
    setInterval(fetchRealtimeData, 800);
});
</script>
@endsection

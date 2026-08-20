@extends('layouts.dashboard')

@section('content')
@php
    $latestEventAt    = $latestSensor?->recorded_at ?? null;
    $isAiDisconnected = !$latestEventAt || $latestEventAt->diffInSeconds(now()) > 6;
    $initialAiResult  = $isAiDisconnected
        ? 'DISCONNECTED'
        : strtoupper($latestSensor?->event_type ?? 'MONITORING');

    $initialResultTheme = match ($initialAiResult) {
        'NORMAL_PASS'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'BLOCKED'      => 'bg-red-50 text-red-700 border-red-200',
        'NOISE'        => 'bg-amber-50 text-amber-700 border-amber-200',
        'UNKNOWN'      => 'bg-violet-50 text-violet-700 border-violet-200',
        'SENSOR_FAULT' => 'bg-rose-50 text-rose-700 border-rose-200',
        'MONITORING'   => 'bg-blue-50 text-blue-700 border-blue-200',
        default        => 'bg-zinc-100 text-zinc-500 border-zinc-300',
    };

    $initialDotTheme = match ($initialAiResult) {
        'NORMAL_PASS'  => 'bg-emerald-500',
        'BLOCKED'      => 'bg-red-500',
        'NOISE'        => 'bg-amber-500',
        'UNKNOWN'      => 'bg-violet-500',
        'SENSOR_FAULT' => 'bg-rose-500',
        'MONITORING'   => 'bg-blue-500',
        default        => 'bg-zinc-400',
    };
@endphp
<div class="max-w-6xl mx-auto">

    <!-- Header -->
    <div class="mb-8 relative z-10">
        <h1 class="text-4xl font-extrabold text-zinc-900 mb-2 tracking-tight">AI Analytics</h1>
        <p class="text-zinc-500 font-medium">Real-time AI prediction history & crossing event analysis for Station 042.</p>
    </div>

    <!-- Main AI Result -->
    <div id="ai-result-card" class="{{ $initialResultTheme }} border-2 rounded-3xl shadow-sm min-h-[260px] px-6 py-10 md:px-12 md:py-12 mb-5 relative z-10 flex flex-col items-center justify-center text-center transition-colors duration-500">
        <div class="flex items-center gap-3 mb-5">
            <span id="ai-result-dot" class="w-4 h-4 {{ $initialDotTheme }} rounded-full {{ $isAiDisconnected ? '' : 'animate-pulse' }}"></span>
            <h3 class="text-base md:text-lg font-black tracking-[0.2em] uppercase">AI 분석 결과</h3>
        </div>
        <div id="ai-result-text" class="text-5xl sm:text-6xl md:text-8xl font-black leading-none tracking-tight uppercase break-words max-w-full">
            {{ $initialAiResult }}
        </div>
        <p id="ai-result-description" class="mt-6 text-sm md:text-base font-bold opacity-70">
            {{ $isAiDisconnected ? 'Node-RED에서 새로운 AI 결과를 기다리는 중입니다.' : '센서 시간 패턴을 분석한 현재 AI 판단입니다.' }}
        </p>
    </div>

    <!-- Secondary Stats -->
    <div class="grid grid-cols-2 gap-4 mb-8 relative z-10">
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-4 md:p-5">
            <h3 class="text-[10px] font-bold text-zinc-400 tracking-widest uppercase mb-2">AI 신뢰도</h3>
            <div class="flex items-baseline gap-1">
                <span id="ai-confidence" class="text-2xl md:text-3xl font-extrabold {{ $isAiDisconnected ? 'text-zinc-300' : 'text-zinc-800' }}">{{ $isAiDisconnected ? '—' : number_format($latestPrediction?->confidence ?? 0, 1) }}</span>
                <span class="text-sm text-zinc-500 font-medium">%</span>
            </div>
            <div class="w-full h-1.5 bg-zinc-100 rounded-full mt-2 overflow-hidden">
                <div id="ai-conf-bar" class="h-full {{ $isAiDisconnected ? 'bg-zinc-300' : 'bg-emerald-400' }} rounded-full transition-all duration-500" style="width: {{ $isAiDisconnected ? '0' : ($latestPrediction?->confidence ?? 0) }}%"></div>
            </div>
        </div>

        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-4 md:p-5">
            <h3 class="text-[10px] font-bold text-zinc-400 tracking-widest uppercase mb-2">AI 분석 기록</h3>
            <span class="text-2xl md:text-3xl font-extrabold text-zinc-800">{{ $predictionHistory?->count() ?? 0 }}</span>
            <p class="text-[10px] md:text-xs text-zinc-400 mt-1">최근 최대 20개 기록</p>
        </div>
    </div>

    <!-- Danger Events Summary -->
    @php
        $dangerCount  = $predictionHistory?->where('crossing_status', 'danger')->count() ?? 0;
        $warningCount = $predictionHistory?->where('crossing_status', 'warning')->count() ?? 0;
        $safeCount    = $predictionHistory?->where('crossing_status', 'safe')->count() ?? 0;
        $total        = $predictionHistory?->count() ?? 1;
    @endphp
    <div class="grid grid-cols-3 gap-4 mb-8 relative z-10">
        <div class="bg-red-50 border border-red-100 rounded-2xl p-5 text-center">
            <div class="text-3xl font-black text-red-600">{{ $dangerCount }}</div>
            <div class="text-xs text-red-400 font-bold uppercase tracking-widest mt-1">DANGER Events</div>
            <div class="text-xs text-zinc-400 mt-1">{{ $total > 0 ? round($dangerCount/$total*100) : 0 }}% of total</div>
        </div>
        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-5 text-center">
            <div class="text-3xl font-black text-amber-600">{{ $warningCount }}</div>
            <div class="text-xs text-amber-400 font-bold uppercase tracking-widest mt-1">WARNING Events</div>
            <div class="text-xs text-zinc-400 mt-1">{{ $total > 0 ? round($warningCount/$total*100) : 0 }}% of total</div>
        </div>
        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-5 text-center">
            <div class="text-3xl font-black text-emerald-600">{{ $safeCount }}</div>
            <div class="text-xs text-emerald-400 font-bold uppercase tracking-widest mt-1">SAFE Readings</div>
            <div class="text-xs text-zinc-400 mt-1">{{ $total > 0 ? round($safeCount/$total*100) : 0 }}% of total</div>
        </div>
    </div>

    <!-- Prediction History Table -->
    <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm overflow-hidden relative z-10">
        <div class="p-6 border-b border-zinc-100 flex items-center justify-between">
            <h3 class="font-bold text-zinc-800">Prediction History</h3>
            <span class="text-xs text-zinc-400 font-mono">Latest {{ $predictionHistory?->count() ?? 0 }} records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Timestamp</th>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Status</th>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Confidence</th>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Train Detected</th>
                        <th class="text-left px-6 py-3 text-[10px] font-bold text-zinc-400 tracking-widest uppercase">Distance (km)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($predictionHistory ?? [] as $p)
                    @php $c = match($p->crossing_status) { 'danger'=>'red','warning'=>'amber',default=>'emerald' }; @endphp
                    <tr class="hover:bg-zinc-50 transition-colors">
                        <td class="px-6 py-3 font-mono text-zinc-500 text-xs">
                            {{ ($p->predicted_at ?? $p->created_at)?->format('d/m H:i:s') }}
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center gap-1 bg-{{ $c }}-50 text-{{ $c }}-700 border border-{{ $c }}-200 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wide">
                                <div class="w-1.5 h-1.5 bg-{{ $c }}-500 rounded-full"></div>
                                {{ strtoupper($p->crossing_status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-1.5 bg-zinc-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-{{ $c }}-400 rounded-full" style="width: {{ $p->confidence }}%"></div>
                                </div>
                                <span class="font-bold text-zinc-700">{{ number_format($p->confidence, 1) }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-3">
                            <span class="font-bold {{ $p->is_train_detected ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ $p->is_train_detected ? '✓ YES' : '— NO' }}
                            </span>
                        </td>
                        <td class="px-6 py-3 font-mono text-zinc-500">{{ number_format($p->distance_km ?? 0, 4) }} km</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-zinc-400 text-sm">No predictions yet. Connect Arduino & Node-RED to start.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const AI_STALE_MS = 6000;

function setAiDisconnected() {
    const card = document.getElementById('ai-result-card');
    const resultText = document.getElementById('ai-result-text');
    const resultDot = document.getElementById('ai-result-dot');
    const description = document.getElementById('ai-result-description');
    const confEl = document.getElementById('ai-confidence');
    const confBar = document.getElementById('ai-conf-bar');

    card.className = 'bg-zinc-100 text-zinc-500 border-zinc-300 border-2 rounded-3xl shadow-sm min-h-[260px] px-6 py-10 md:px-12 md:py-12 mb-5 relative z-10 flex flex-col items-center justify-center text-center transition-colors duration-500';
    resultText.textContent = 'DISCONNECTED';
    resultDot.className = 'w-4 h-4 bg-zinc-400 rounded-full';
    description.textContent = 'Node-RED에서 새로운 AI 결과를 기다리는 중입니다.';
    confEl.innerText = '—';
    confBar.style.width = '0%';
    confBar.className = 'h-full bg-zinc-300 rounded-full transition-all duration-500';
}

function updateAiResult(eventType) {
    const card = document.getElementById('ai-result-card');
    const resultText = document.getElementById('ai-result-text');
    const resultDot = document.getElementById('ai-result-dot');
    const description = document.getElementById('ai-result-description');

    const themes = {
        NORMAL_PASS: {
            card: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            dot: 'bg-emerald-500',
            description: '정상적인 기차 통과 패턴입니다.'
        },
        BLOCKED: {
            card: 'bg-red-50 text-red-700 border-red-200',
            dot: 'bg-red-500',
            description: '기차 정지 또는 장애물이 감지되었습니다.'
        },
        NOISE: {
            card: 'bg-amber-50 text-amber-700 border-amber-200',
            dot: 'bg-amber-500',
            description: '순간적인 잘못된 센서 감지가 발생했습니다.'
        },
        UNKNOWN: {
            card: 'bg-violet-50 text-violet-700 border-violet-200',
            dot: 'bg-violet-500',
            description: '학습하지 않은 패턴입니다. 안전 상태를 유지합니다.'
        },
        SENSOR_FAULT: {
            card: 'bg-rose-50 text-rose-700 border-rose-200',
            dot: 'bg-rose-500',
            description: '센서 측정 이상이 감지되었습니다.'
        },
        MONITORING: {
            card: 'bg-blue-50 text-blue-700 border-blue-200',
            dot: 'bg-blue-500',
            description: '센서 패턴을 수집하고 분석하는 중입니다.'
        }
    };

    const theme = themes[eventType] || themes.UNKNOWN;
    card.className = `${theme.card} border-2 rounded-3xl shadow-sm min-h-[260px] px-6 py-10 md:px-12 md:py-12 mb-5 relative z-10 flex flex-col items-center justify-center text-center transition-colors duration-500`;
    resultText.textContent = eventType;
    resultDot.className = `w-4 h-4 ${theme.dot} rounded-full animate-pulse`;
    description.textContent = theme.description;
}

function fetchAIData() {
    fetch('/api/v1/dashboard/latest')
        .then(r => r.json())
        .then(data => {
            if (!data.success) { setAiDisconnected(); return; }
            const pred = data.latestPrediction;
            const sensor = data.latestSensor;

            const ageMs = sensor?.recorded_at ? (new Date(data.server_time) - new Date(sensor.recorded_at)) : Infinity;
            if (!sensor || ageMs > AI_STALE_MS) { setAiDisconnected(); return; }

            const status = pred?.crossing_status || 'safe';
            const eventType = String(sensor.event_type || 'UNKNOWN').toUpperCase();
            const confidenceValue = Math.max(0, Math.min(100, parseFloat(pred?.confidence ?? 0) || 0));
            const conf = confidenceValue.toFixed(1);

            const confEl = document.getElementById('ai-confidence');
            const confBar = document.getElementById('ai-conf-bar');

            updateAiResult(eventType);
            confEl.innerText = conf;
            confBar.style.width = conf + '%';

            if (status === 'danger') {
                confBar.className = 'h-full bg-red-400 rounded-full transition-all duration-500';
            } else if (status === 'warning') {
                confBar.className = 'h-full bg-amber-400 rounded-full transition-all duration-500';
            } else {
                confBar.className = 'h-full bg-emerald-400 rounded-full transition-all duration-500';
            }
        })
        .catch(() => setAiDisconnected());
}
document.addEventListener('DOMContentLoaded', () => {
    fetchAIData();
    setInterval(fetchAIData, 1500);
});
</script>
@endsection

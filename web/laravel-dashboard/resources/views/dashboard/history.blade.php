@extends('layouts.dashboard')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8 relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-zinc-900 mb-1">Telemetry History Log</h1>
            <p class="text-zinc-500 text-sm font-medium">Historical logs and state changes across all station infrastructure</p>
        </div>
        <button onclick="window.location.reload()" class="bg-white border border-zinc-200 hover:border-zinc-300 text-zinc-700 font-bold px-4 py-2 rounded-xl text-sm shadow-sm flex items-center gap-2 transition-all">
            <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.213 6H16"></path></svg>
            Refresh Log
        </button>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 relative z-10">
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-6">
            <h3 class="text-[10px] font-bold text-zinc-400 tracking-widest uppercase mb-1">Sensor Log Count</h3>
            <span class="text-3xl font-extrabold text-zinc-800 tracking-tight">{{ $sensorHistory->count() }}</span>
            <p class="text-xs text-zinc-400 mt-1">Latest records retrieved</p>
        </div>
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-6">
            <h3 class="text-[10px] font-bold text-zinc-400 tracking-widest uppercase mb-1">Device Events Count</h3>
            <span class="text-3xl font-extrabold text-zinc-800 tracking-tight">{{ $deviceHistory->count() }}</span>
            <p class="text-xs text-zinc-400 mt-1">Latest mechanical adjustments</p>
        </div>
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white shadow-sm p-6">
            <h3 class="text-[10px] font-bold text-zinc-400 tracking-widest uppercase mb-1">AI Prediction Count</h3>
            <span class="text-3xl font-extrabold text-zinc-800 tracking-tight">{{ $predictionHistory->count() }}</span>
            <p class="text-xs text-zinc-400 mt-1">Latest model evaluations</p>
        </div>
    </div>

    <!-- Log Grid Tabs / Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8 relative z-10">
        
        <!-- Sensor Log Column -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-zinc-150 shadow-sm p-6 flex flex-col h-[600px]">
            <div class="flex items-center justify-between pb-4 border-b border-zinc-100 mb-4">
                <h2 class="font-extrabold text-zinc-800 text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Sensor Stream
                </h2>
                <span class="text-[10px] font-bold text-zinc-400 font-mono">LIVE FEED</span>
            </div>
            
            <div class="flex-1 overflow-y-auto space-y-3 pr-2 scrollbar">
                @forelse($sensorHistory as $s)
                <div class="p-3 bg-zinc-50 border border-zinc-100 rounded-xl hover:border-emerald-200 transition-colors">
                    <div class="flex justify-between text-[10px] text-zinc-400 font-mono mb-2">
                        <span>ID #{{ $s->id }}</span>
                        <span>{{ ($s->recorded_at ?? $s->created_at)->format('H:i:s') }}</span>
                    </div>
                    <div class="flex items-baseline justify-between mb-1.5">
                        <div class="text-xs font-semibold text-zinc-600">Approach: <span class="font-bold text-zinc-800">{{ $s->distance_cm }} cm</span></div>
                        <div class="text-xs font-semibold text-zinc-600">Exit: <span class="font-bold text-zinc-800">{{ $s->distance_exit_cm }} cm</span></div>
                    </div>
                    <div class="flex justify-between items-center text-[10px]">
                        <span class="text-zinc-500 font-mono uppercase">{{ $s->event_type }}</span>
                        @php $sc = $s->status === 'danger' ? 'red' : ($s->status === 'warning' ? 'amber' : 'emerald'); @endphp
                        <span class="text-{{ $sc }}-600 font-extrabold uppercase">{{ $s->status }}</span>
                    </div>
                </div>
                @empty
                <div class="h-full flex items-center justify-center text-zinc-400 text-sm">No sensor logs yet.</div>
                @endforelse
            </div>
        </div>

        <!-- Devices Action Column -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-zinc-150 shadow-sm p-6 flex flex-col h-[600px]">
            <div class="flex items-center justify-between pb-4 border-b border-zinc-100 mb-4">
                <h2 class="font-extrabold text-zinc-800 text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Device Log
                </h2>
                <span class="text-[10px] font-bold text-zinc-400 font-mono">ACTIONS</span>
            </div>
            
            <div class="flex-1 overflow-y-auto space-y-3 pr-2 scrollbar">
                @forelse($deviceHistory as $d)
                <div class="p-3 bg-zinc-50 border border-zinc-100 rounded-xl hover:border-blue-200 transition-colors">
                    <div class="flex justify-between text-[10px] text-zinc-400 font-mono mb-2">
                        <span>ID #{{ $d->id }}</span>
                        <span>{{ ($d->changed_at ?? $d->created_at)->format('H:i:s') }}</span>
                    </div>
                    <div class="text-xs font-bold text-zinc-800 mb-1">
                        {{ str_replace('_', ' ', ucfirst($d->device_name)) }}
                    </div>
                    <div class="flex justify-between items-center text-[10px]">
                        @php $dc = in_array($d->state, ['closed','red']) ? 'red' : (in_array($d->state, ['yellow']) ? 'amber' : 'emerald'); @endphp
                        <span class="text-{{ $dc }}-600 font-extrabold uppercase">STATE: {{ $d->state }}</span>
                        <span class="text-zinc-400 font-mono uppercase">MODE: {{ $d->mode ?? 'AUTO' }}</span>
                    </div>
                </div>
                @empty
                <div class="h-full flex items-center justify-center text-zinc-400 text-sm">No device adjustments logged.</div>
                @endforelse
            </div>
        </div>

        <!-- AI Inference Column -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-zinc-150 shadow-sm p-6 flex flex-col h-[600px]">
            <div class="flex items-center justify-between pb-4 border-b border-zinc-100 mb-4">
                <h2 class="font-extrabold text-zinc-800 text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    AI Predictions
                </h2>
                <span class="text-[10px] font-bold text-zinc-400 font-mono">INFERENCES</span>
            </div>
            
            <div class="flex-1 overflow-y-auto space-y-3 pr-2 scrollbar">
                @forelse($predictionHistory as $p)
                <div class="p-3 bg-zinc-50 border border-zinc-100 rounded-xl hover:border-indigo-200 transition-colors">
                    <div class="flex justify-between text-[10px] text-zinc-400 font-mono mb-2">
                        <span>ID #{{ $p->id }}</span>
                        <span>{{ ($p->predicted_at ?? $p->created_at)->format('H:i:s') }}</span>
                    </div>
                    <div class="flex items-baseline justify-between mb-1.5">
                        <span class="text-xs font-bold text-zinc-800">Conf: {{ number_format($p->confidence, 1) }}%</span>
                        <span class="text-[10px] font-mono text-zinc-500">{{ number_format($p->distance_km, 4) }} km</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px]">
                        @php $pc = $p->crossing_status === 'danger' ? 'red' : ($p->crossing_status === 'warning' ? 'amber' : 'emerald'); @endphp
                        <span class="text-{{ $pc }}-600 font-extrabold uppercase">STATUS: {{ $p->crossing_status }}</span>
                        <span class="font-mono text-zinc-400">TRAIN: {{ $p->is_train_detected ? 'YES' : 'NO' }}</span>
                    </div>
                </div>
                @empty
                <div class="h-full flex items-center justify-center text-zinc-400 text-sm">No predictions logged.</div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection

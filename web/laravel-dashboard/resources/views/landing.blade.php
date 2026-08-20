<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smartcity Jember — Level Crossing Safety System</title>
    <meta name="description" content="A dual-sensor barrier and signal system for unguarded rail crossings, built by student engineers from Indonesia and Korea.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Expanded:wght@700;800;900&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --ink: #12181D;
            --blueprint: #0E3350;
            --blueprint-2: #164468;
            --paper: #EFE9DA;
            --paper-2: #E4DCC6;
            --steel: #90A0AC;
            --red: #D8382C;
            --amber: #E7A73C;
            --green: #3E8F62;
        }
        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background-color: var(--paper);
            color: var(--ink);
        }
        .f-display { font-family: 'Archivo Expanded', sans-serif; }
        .f-mono { font-family: 'IBM Plex Mono', monospace; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
            }
        }

        /* ---- blueprint grid backdrop ---- */
        .grid-blueprint {
            background-image:
                linear-gradient(rgba(144,160,172,0.14) 1px, transparent 1px),
                linear-gradient(90deg, rgba(144,160,172,0.14) 1px, transparent 1px);
            background-size: 32px 32px;
        }
        .grid-paper {
            background-image:
                linear-gradient(rgba(18,24,29,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(18,24,29,0.05) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        /* ---- eyebrow / dimension-line label ---- */
        .dim-label {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.7rem;
            letter-spacing: 0.18em;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
        }
        .dim-label::before { content: ''; width: 22px; height: 1px; background: currentColor; opacity: .6; }

        /* =========================================================
           HERO CROSSING SIMULATION
        ========================================================= */
        .scene {
            position: relative;
            aspect-ratio: 16 / 9;
            border-radius: 18px;
            background: linear-gradient(180deg, #0B2A42 0%, #0E3350 60%, #123C5C 100%);
            border: 1px solid rgba(239,233,218,0.12);
            overflow: hidden;
        }
        .scene-sky-line {
            position: absolute; inset: 0;
            background-image: linear-gradient(rgba(239,233,218,0.06) 1px, transparent 1px);
            background-size: 100% 26px;
        }
        .scene-road {
            position: absolute; left: 42%; top: 0; bottom: 0; width: 16%;
            background: rgba(239,233,218,0.06);
            border-left: 1px dashed rgba(239,233,218,0.25);
            border-right: 1px dashed rgba(239,233,218,0.25);
        }
        .scene-track {
            position: absolute; left: 0; right: 0; top: 62%; height: 34px; transform: translateY(-50%);
        }
        .scene-rail {
            position: absolute; left: 0; right: 0; height: 3px; background: rgba(239,233,218,0.55);
        }
        .scene-rail.top { top: 0; }
        .scene-rail.bot { bottom: 0; }
        .scene-ties {
            position: absolute; inset: 0;
            background-image: repeating-linear-gradient(90deg, rgba(239,233,218,0.35) 0 4px, transparent 4px 26px);
            top: 50%; height: 6px; transform: translateY(-50%);
        }

        /* Post is short and sits right at trackside -- it's the pivot's
           mounting stub, not a tall pole, so the arm's hinge lands at
           road height instead of floating above the crossing. */
        .scene-post {
            position: absolute; width: 5px; height: 18px;
            background: repeating-linear-gradient(180deg, var(--red) 0 6px, var(--paper) 6px 12px);
            border-radius: 2px;
        }
        .scene-barrier-a { left: calc(42% - 5px); top: 51%; }
        .scene-barrier-b { left: calc(58%); top: 51%; }

        /* Each arm is roughly half the road gap -- closed, they meet
           near the middle from opposite posts, instead of one arm
           overshooting all the way across the other's side. */
        .scene-arm {
            position: absolute; width: 52px; height: 8px; border-radius: 3px;
            background: repeating-linear-gradient(90deg, var(--red) 0 14px, var(--paper) 14px 28px);
            box-shadow: 0 1px 0 rgba(0,0,0,0.3);
            transform-origin: 4px 4px;
            transition: transform 900ms cubic-bezier(.4,0,.2,1);
        }
        .scene-arm-a { left: calc(42% - 1px); top: calc(51% + 2px); transform: rotate(-82deg); }
        .scene-arm-b { left: calc(58% - 1px); top: calc(51% + 2px); transform: rotate(82deg) scaleX(-1); transform-origin: 4px 4px; }

        .scene[data-state="danger"] .scene-arm-a { transform: rotate(0deg); }
        .scene[data-state="danger"] .scene-arm-b { transform: rotate(0deg) scaleX(-1); }

        .scene-signal {
            position: absolute; right: 8%; top: 12%;
            display: flex; flex-direction: column; gap: 6px;
            background: rgba(18,24,29,0.55);
            border: 1px solid rgba(239,233,218,0.18);
            padding: 8px; border-radius: 8px;
        }
        .lamp { width: 14px; height: 14px; border-radius: 50%; background: rgba(239,233,218,0.12); transition: all 300ms; }
        .lamp.red { background: rgba(216,56,44,0.18); }
        .lamp.amber { background: rgba(231,167,60,0.18); }
        .lamp.green { background: rgba(62,143,98,0.18); }
        .scene[data-state="danger"] .lamp.red { background: var(--red); box-shadow: 0 0 14px 3px rgba(216,56,44,0.8); }
        .scene[data-state="warning"] .lamp.amber { background: var(--amber); box-shadow: 0 0 14px 3px rgba(231,167,60,0.8); }
        .scene[data-state="safe"] .lamp.green { background: var(--green); box-shadow: 0 0 14px 3px rgba(62,143,98,0.8); }

        .scene-train {
            position: absolute; top: calc(62% - 17px); height: 34px; width: 96px;
            background: var(--paper);
            border-radius: 4px;
            opacity: 0;
        }
        .scene-train::before {
            content: '';
            position: absolute; inset: 6px 8px; border-radius: 2px;
            background-image: repeating-linear-gradient(90deg, rgba(18,24,29,0.35) 0 10px, transparent 10px 20px);
        }
        .scene[data-state="danger"].run-a .scene-train { left: -110px; opacity: 1; animation: trainA 3.4s linear forwards; }
        .scene[data-state="danger"].run-b .scene-train { right: -110px; opacity: 1; animation: trainB 3.4s linear forwards; }
        @keyframes trainA { from { left: -110px; } to { left: 110%; } }
        @keyframes trainB { from { right: -110px; } to { right: 110%; } }

        .ping { position: absolute; top: 62%; width: 46px; height: 46px; transform: translateY(-50%); border-radius: 50%; border: 1px solid rgba(239,233,218,0.35); opacity: 0; }
        .ping.left { left: 6%; }
        .ping.right { right: 6%; }
        .scene[data-state] .ping { animation: ping 2.6s ease-out infinite; }
        .ping.d2 { animation-delay: .5s !important; }
        @keyframes ping { 0% { transform: translateY(-50%) scale(.4); opacity: .7; } 100% { transform: translateY(-50%) scale(1.5); opacity: 0; } }

        /* ---- serial ticker ---- */
        .ticker {
            background: #0A2033; border: 1px solid rgba(239,233,218,0.14); border-radius: 10px;
            padding: 12px 14px; height: 148px; overflow: hidden; position: relative;
        }
        .ticker-line { font-family: 'IBM Plex Mono', monospace; font-size: 0.72rem; line-height: 1.5rem; white-space: pre; color: rgba(239,233,218,0.55); }
        .ticker-line.evt { color: var(--amber); }
        .ticker-line.evt-danger { color: var(--red); }
        .ticker-line.evt-safe { color: var(--green); }

        /* =========================================================
           ROSTER / MANIFEST
        ========================================================= */
        .roster-card {
            border: 1px dashed rgba(18,24,29,0.28);
            border-radius: 14px;
            background: rgba(255,255,255,0.4);
        }
        .roster-avatar {
            width: 100%; aspect-ratio: 1; border-radius: 10px;
            border: 1px dashed rgba(18,24,29,0.3);
            display: flex; align-items: center; justify-content: center;
            font-family: 'IBM Plex Mono', monospace; font-size: 0.65rem; color: rgba(18,24,29,0.4);
            text-align: center; letter-spacing: 0.05em;
        }
        .manifest-row { border-bottom: 1px solid rgba(18,24,29,0.12); }
        .manifest-row:last-child { border-bottom: none; }

        /* =========================================================
           ICONS (bespoke, schematic style — no icon library)
        ========================================================= */
        .icon-frame { width: 3rem; height: 3rem; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(239,233,218,0.18); }
    </style>
</head>
<body class="antialiased">

    <!-- ============ NAV ============ -->
    <nav class="fixed w-full z-50 backdrop-blur-md border-b" style="background: rgba(239,233,218,0.85); border-color: rgba(18,24,29,0.08);">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="/" class="flex items-center gap-2.5">
                    <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
                        <rect x="1" y="1" width="24" height="24" rx="4" stroke="var(--ink)" stroke-width="1.4"/>
                        <line x1="4" y1="18" x2="22" y2="18" stroke="var(--ink)" stroke-width="1.4"/>
                        <line x1="6.5" y1="18" x2="6.5" y2="15" stroke="var(--ink)" stroke-width="1.4"/>
                        <line x1="10" y1="18" x2="10" y2="15" stroke="var(--ink)" stroke-width="1.4"/>
                        <line x1="13.5" y1="18" x2="13.5" y2="15" stroke="var(--ink)" stroke-width="1.4"/>
                        <rect x="16.5" y="6" width="6" height="9" rx="1" stroke="var(--ink)" stroke-width="1.4"/>
                        <circle cx="19.5" cy="9.4" r="1.1" fill="var(--red)"/>
                    </svg>
                    <span class="f-mono text-[13px] font-semibold tracking-widest uppercase">Smartcity Jember</span>
                </a>
                <div class="hidden md:flex items-center gap-8 f-mono text-[12px] tracking-wide uppercase" style="color: rgba(18,24,29,0.65);">
                    <a href="#team" class="hover:text-[var(--red)] transition-colors">Team</a>
                    <a href="#institutions" class="hover:text-[var(--red)] transition-colors">Institutions</a>
                    <a href="#how-it-works" class="hover:text-[var(--red)] transition-colors">How it works</a>
                    <a href="#why" class="hover:text-[var(--red)] transition-colors">Why</a>
                </div>
                <a href="{{ url('/dashboard') }}" class="f-mono text-[12px] uppercase tracking-wide px-4 py-2 rounded-md text-white" style="background: var(--ink);">
                    Dashboard →
                </a>
            </div>
        </div>
    </nav>

    <!-- ============ HERO ============ -->
    <section class="relative pt-28 pb-20 grid-blueprint" style="background: linear-gradient(180deg, var(--ink) 0%, var(--blueprint) 55%, var(--blueprint-2) 100%);">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="grid lg:grid-cols-12 gap-12 items-start">
                <div class="lg:col-span-6">
                    <div class="dim-label text-[color:var(--steel)] mb-6">SMARTCITY JEMBER — LEVEL CROSSING SAFETY</div>
                    <h1 class="f-display text-[2.4rem] leading-[1.08] sm:text-5xl sm:leading-[1.08] font-extrabold text-white uppercase">
                        Most crossings here have no gate.
                        <span style="color: var(--amber);">This one closes itself.</span>
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed max-w-lg" style="color: rgba(239,233,218,0.75);">
                        A dual-sensor barrier and signal system for unguarded rail crossings —
                        designed and built by student engineers from Indonesia and Korea, running on real hardware.
                    </p>
                    <div class="mt-9 flex flex-wrap gap-4">
                        <a href="{{ url('/dashboard') }}" class="px-6 py-3 rounded-md font-semibold text-sm" style="background: var(--amber); color: var(--ink);">
                            Open live dashboard
                        </a>
                        <a href="#how-it-works" class="px-6 py-3 rounded-md font-semibold text-sm border" style="border-color: rgba(239,233,218,0.3); color: white;">
                            See how it works
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-6">
                    <div class="dim-label text-[color:var(--steel)] mb-3">LIVE SIMULATION — NOT A RECORDING</div>
                    <div class="scene run-a" id="scene" data-state="safe">
                        <div class="scene-sky-line"></div>
                        <div class="scene-road"></div>
                        <div class="scene-track">
                            <div class="scene-rail top"></div>
                            <div class="scene-ties"></div>
                            <div class="scene-rail bot"></div>
                        </div>
                        <div class="ping left"></div>
                        <div class="ping left d2"></div>
                        <div class="ping right"></div>
                        <div class="ping right d2"></div>
                        <div class="scene-train"></div>
                        <div class="scene-post scene-barrier-a"></div>
                        <div class="scene-post scene-barrier-b"></div>
                        <div class="scene-arm scene-arm-a"></div>
                        <div class="scene-arm scene-arm-b"></div>
                        <div class="scene-signal">
                            <div class="lamp red"></div>
                            <div class="lamp amber"></div>
                            <div class="lamp green"></div>
                        </div>
                    </div>

                    <div class="dim-label text-[color:var(--steel)] mt-5 mb-2">SERIAL MONITOR — TRAIN_GATE_FINAL.INO</div>
                    <div class="ticker" id="ticker"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ TEAM ============ -->
    <!-- TODO: swap the placeholder names/photos below for the real team roster before publishing. -->
    <section id="team" class="py-24 grid-paper">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="max-w-2xl mb-14">
                <div class="dim-label mb-4" style="color: rgba(18,24,29,0.5);">THE TEAM</div>
                <h2 class="f-display text-3xl sm:text-4xl font-extrabold uppercase">Two countries, one crossing.</h2>
                <p class="mt-4 text-base" style="color: rgba(18,24,29,0.65);">
                    Built during a joint capstone between Indonesian polytechnics and Kyungpook National University, Daegu.
                    Roster below is a placeholder — swap in real names and photos.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-14">
                <div>
                    <div class="f-mono text-xs uppercase tracking-widest mb-5 pb-2 border-b-2" style="border-color: var(--red); color: var(--red);">Indonesia</div>
                    <div class="grid grid-cols-2 gap-4">
                        @foreach ([
                            ['role' => 'Firmware & Sensors'],
                            ['role' => 'Barrier Mechanics'],
                            ['role' => 'Node-RED Integration'],
                            ['role' => 'Dashboard (Laravel)'],
                        ] as $m)
                        <div class="roster-card p-3">
                            <div class="roster-avatar mb-3">ADD<br>PHOTO</div>
                            <div class="text-sm font-semibold">Team member name</div>
                            <div class="f-mono text-[11px] mt-0.5" style="color: rgba(18,24,29,0.55);">{{ $m['role'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="f-mono text-xs uppercase tracking-widest mb-5 pb-2 border-b-2" style="border-color: var(--blueprint); color: var(--blueprint);">Korea</div>
                    <div class="grid grid-cols-2 gap-4">
                        @foreach ([
                            ['role' => 'Systems Design'],
                            ['role' => 'Field Testing'],
                            ['role' => 'Program Mentor'],
                        ] as $m)
                        <div class="roster-card p-3">
                            <div class="roster-avatar mb-3">ADD<br>PHOTO</div>
                            <div class="text-sm font-semibold">Team member name</div>
                            <div class="f-mono text-[11px] mt-0.5" style="color: rgba(18,24,29,0.55);">{{ $m['role'] }}</div>
                        </div>
                        @endforeach
                        <div class="roster-card p-3 flex flex-col items-center justify-center text-center" style="border-style: dashed;">
                            <div class="f-mono text-[11px]" style="color: rgba(18,24,29,0.4);">+ ADD<br>MEMBER</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ INSTITUTIONS + PARTNERS ============ -->
    <section id="institutions" class="pb-24 grid-paper">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="max-w-2xl mb-12">
                <div class="dim-label mb-4" style="color: rgba(18,24,29,0.5);">INSTITUTIONS</div>
                <h2 class="f-display text-3xl sm:text-4xl font-extrabold uppercase">Three campuses in Indonesia. One in Korea.</h2>
            </div>

            <div class="rounded-2xl overflow-hidden border" style="border-color: rgba(18,24,29,0.14); background: rgba(255,255,255,0.55);">
                @foreach ([
                    ['name' => 'Politeknik Negeri Jember', 'note' => 'Pusat / PSDKU', 'place' => 'Jember, East Java · Indonesia'],
                    ['name' => 'Politeknik Negeri Bandung', 'note' => 'Partner campus', 'place' => 'Bandung, West Java · Indonesia'],
                    ['name' => 'Politeknik Negeri Sambas', 'note' => 'Partner campus', 'place' => 'Sambas, West Kalimantan · Indonesia'],
                    ['name' => 'Kyungpook National University', 'note' => 'KNU', 'place' => 'Daegu · South Korea'],
                ] as $i => $c)
                <div class="manifest-row flex flex-col sm:flex-row sm:items-center justify-between px-6 py-5 gap-1">
                    <div class="flex items-center gap-4">
                        <span class="f-mono text-xs" style="color: rgba(18,24,29,0.35);">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <div>
                            <div class="font-semibold">{{ $c['name'] }}</div>
                            <div class="f-mono text-[11px]" style="color: rgba(18,24,29,0.5);">{{ $c['note'] }}</div>
                        </div>
                    </div>
                    <div class="f-mono text-xs uppercase tracking-wide" style="color: rgba(18,24,29,0.55);">{{ $c['place'] }}</div>
                </div>
                @endforeach
            </div>

            <div class="mt-10 flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-8">
                <div class="f-mono text-[11px] uppercase tracking-widest" style="color: rgba(18,24,29,0.45);">Supported by</div>
                <div class="flex flex-wrap items-center gap-x-8 gap-y-2">
                    <span class="font-semibold text-sm">NIA <span class="font-normal" style="color: rgba(18,24,29,0.55);">— National Information Society Agency, Korea</span></span>
                    <span class="font-semibold text-sm">World Friends Korea</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ HOW IT WORKS ============ -->
    <section id="how-it-works" class="py-24 grid-blueprint" style="background: var(--blueprint);">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="max-w-2xl mb-14">
                <div class="dim-label mb-4" style="color: var(--steel);">HOW IT WORKS</div>
                <h2 class="f-display text-3xl sm:text-4xl font-extrabold uppercase text-white">Three states. No guessing.</h2>
                <p class="mt-4 text-base" style="color: rgba(239,233,218,0.7);">
                    Two ultrasonic sensors, one on each side of the crossing, feed a debounced state machine —
                    the same one running the simulation above.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <!-- SAFE -->
                <div class="rounded-2xl p-7 border" style="background: rgba(239,233,218,0.04); border-color: rgba(62,143,98,0.35);">
                    <div class="icon-frame mb-6" style="background: rgba(62,143,98,0.12); border-color: rgba(62,143,98,0.4);">
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
                            <line x1="3" y1="21" x2="23" y2="21" stroke="var(--green)" stroke-width="1.6"/>
                            <line x1="6" y1="21" x2="6" y2="18" stroke="var(--green)" stroke-width="1.6"/>
                            <line x1="10" y1="21" x2="10" y2="18" stroke="var(--green)" stroke-width="1.6"/>
                            <line x1="14" y1="21" x2="14" y2="18" stroke="var(--green)" stroke-width="1.6"/>
                            <rect x="6.5" y="4" width="3" height="13" rx="1" stroke="var(--green)" stroke-width="1.6"/>
                        </svg>
                    </div>
                    <div class="f-mono text-xs tracking-widest mb-2" style="color: var(--green);">STATE 01 — SAFE</div>
                    <p class="text-sm leading-relaxed" style="color: rgba(239,233,218,0.75);">
                        Gate up, lights green. Both sensors read a real, valid distance above the danger threshold —
                        confirmed over several readings in a row, not just one lucky ping.
                    </p>
                </div>

                <!-- WARNING -->
                <div class="rounded-2xl p-7 border" style="background: rgba(239,233,218,0.04); border-color: rgba(231,167,60,0.35);">
                    <div class="icon-frame mb-6" style="background: rgba(231,167,60,0.12); border-color: rgba(231,167,60,0.4);">
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
                            <circle cx="13" cy="13" r="2" fill="var(--amber)"/>
                            <circle cx="13" cy="13" r="6.5" stroke="var(--amber)" stroke-width="1.4" opacity="0.7"/>
                            <circle cx="13" cy="13" r="11" stroke="var(--amber)" stroke-width="1.2" opacity="0.35"/>
                        </svg>
                    </div>
                    <div class="f-mono text-xs tracking-widest mb-2" style="color: var(--amber);">STATE 02 — WARNING</div>
                    <p class="text-sm leading-relaxed" style="color: rgba(239,233,218,0.75);">
                        Something's close. A sensor holds a near reading — or drops out entirely, which counts as
                        danger too, never as safe — for enough consecutive checks to rule out noise.
                    </p>
                </div>

                <!-- DANGER -->
                <div class="rounded-2xl p-7 border" style="background: rgba(239,233,218,0.04); border-color: rgba(216,56,44,0.35);">
                    <div class="icon-frame mb-6" style="background: rgba(216,56,44,0.12); border-color: rgba(216,56,44,0.4);">
                        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
                            <line x1="3" y1="21" x2="23" y2="21" stroke="var(--red)" stroke-width="1.6"/>
                            <rect x="5.5" y="16.5" width="15" height="3" rx="1" stroke="var(--red)" stroke-width="1.6"/>
                            <path d="M11 21.5v2.2M15 21.5v2.2" stroke="var(--red)" stroke-width="1.6"/>
                            <path d="M9.5 12.5h7v2.5a3.5 3.5 0 01-7 0v-2.5z" stroke="var(--red)" stroke-width="1.6"/>
                            <path d="M11 12.5V9.8a2 2 0 014 0v2.7" stroke="var(--red)" stroke-width="1.6"/>
                        </svg>
                    </div>
                    <div class="f-mono text-xs tracking-widest mb-2" style="color: var(--red);">STATE 03 — DANGER</div>
                    <p class="text-sm leading-relaxed" style="color: rgba(239,233,218,0.75);">
                        Gate down, lights red. The barrier won't lift again until the far sensor first sees the
                        train arrive, then genuinely reports it's gone — not one stray reading.
                    </p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl p-6 border flex items-start gap-4" style="background: rgba(239,233,218,0.03); border-color: rgba(239,233,218,0.14);">
                <svg width="30" height="30" viewBox="0 0 30 30" fill="none" class="shrink-0 mt-0.5">
                    <path d="M6 15 L13 15" stroke="var(--steel)" stroke-width="1.6" marker-end="url(#arrow)"/>
                    <path d="M24 15 L17 15" stroke="var(--steel)" stroke-width="1.6" marker-end="url(#arrow)"/>
                    <rect x="13" y="7" width="4" height="16" rx="1" stroke="var(--steel)" stroke-width="1.4"/>
                    <defs><marker id="arrow" markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto"><path d="M0,0 L6,3 L0,6 z" fill="var(--steel)"/></marker></defs>
                </svg>
                <p class="text-sm leading-relaxed" style="color: rgba(239,233,218,0.7);">
                    <span class="font-semibold text-white">Either direction.</span>
                    Whichever sensor sees a train first becomes the entry side for that crossing — the other side
                    is what the system watches for the train to clear. No sensor is permanently "approach" or "exit."
                </p>
            </div>
        </div>
    </section>

    <!-- ============ WHY ============ -->
    <section id="why" class="py-24 grid-paper">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="grid lg:grid-cols-2 gap-14 items-center">
                <div>
                    <div class="dim-label mb-4" style="color: rgba(18,24,29,0.5);">WHY THIS EXISTS</div>
                    <h2 class="f-display text-3xl sm:text-4xl font-extrabold uppercase leading-tight">
                        Most level crossings in Indonesia have no barrier at all.
                    </h2>
                    <p class="mt-6 text-base leading-relaxed" style="color: rgba(18,24,29,0.7);">
                        Across Java and beyond, a huge share of level crossings are marked with nothing more than a
                        sign — no arm, no light, no warning bell. Drivers judge the gap between themselves and an
                        oncoming train on sight alone. When they judge wrong, there's no second warning.
                    </p>
                    <p class="mt-4 text-base leading-relaxed" style="color: rgba(18,24,29,0.7);">
                        We built one crossing that watches for itself. Every campus on this page is trying to build more.
                    </p>
                    <a href="{{ url('/dashboard') }}" class="mt-8 inline-flex items-center gap-2 px-6 py-3 rounded-md font-semibold text-sm text-white" style="background: var(--red);">
                        See it running live
                    </a>
                </div>

                <!-- Original site-survey style illustration — not a photograph. -->
                <div class="rounded-2xl border p-6" style="border-color: rgba(18,24,29,0.14); background: rgba(255,255,255,0.5);">
                    <div class="f-mono text-[11px] uppercase tracking-widest mb-4" style="color: rgba(18,24,29,0.45);">Site survey — unguarded crossing</div>
                    <svg viewBox="0 0 480 300" class="w-full h-auto">
                        <rect x="0" y="0" width="480" height="300" fill="none"/>
                        <!-- road -->
                        <rect x="170" y="0" width="70" height="300" fill="rgba(18,24,29,0.05)"/>
                        <line x1="205" y1="0" x2="205" y2="300" stroke="rgba(18,24,29,0.25)" stroke-width="1.5" stroke-dasharray="8 8"/>
                        <!-- tracks -->
                        <line x1="0" y1="150" x2="480" y2="150" stroke="rgba(18,24,29,0.55)" stroke-width="2.5"/>
                        <line x1="0" y1="164" x2="480" y2="164" stroke="rgba(18,24,29,0.55)" stroke-width="2.5"/>
                        <g stroke="rgba(18,24,29,0.4)" stroke-width="2">
                            <line x1="20" y1="146" x2="20" y2="168"/><line x1="60" y1="146" x2="60" y2="168"/>
                            <line x1="100" y1="146" x2="100" y2="168"/><line x1="140" y1="146" x2="140" y2="168"/>
                            <line x1="270" y1="146" x2="270" y2="168"/><line x1="310" y1="146" x2="310" y2="168"/>
                            <line x1="350" y1="146" x2="350" y2="168"/><line x1="390" y1="146" x2="390" y2="168"/>
                            <line x1="430" y1="146" x2="430" y2="168"/><line x1="460" y1="146" x2="460" y2="168"/>
                        </g>
                        <!-- missing barrier ghost -->
                        <g opacity="0.35" stroke="var(--red)" stroke-dasharray="3 4">
                            <line x1="150" y1="60" x2="150" y2="140" stroke-width="2"/>
                            <line x1="150" y1="70" x2="230" y2="70" stroke-width="4"/>
                        </g>
                        <text x="242" y="66" class="f-mono" font-size="10" fill="var(--red)">NO BARRIER INSTALLED</text>
                        <!-- car -->
                        <rect x="180" y="200" width="30" height="18" rx="3" fill="rgba(18,24,29,0.55)"/>
                        <circle cx="188" cy="220" r="3.5" fill="rgba(18,24,29,0.7)"/>
                        <circle cx="202" cy="220" r="3.5" fill="rgba(18,24,29,0.7)"/>
                        <!-- dimension callouts -->
                        <g class="f-mono" font-size="9" fill="rgba(18,24,29,0.55)">
                            <line x1="0" y1="255" x2="470" y2="255" stroke="rgba(18,24,29,0.3)" stroke-width="1"/>
                            <text x="10" y="270">SITE: AT-GRADE CROSSING</text>
                            <text x="330" y="270">GATES: 0 ACTIVE</text>
                        </g>
                        <g class="f-mono" font-size="9" fill="rgba(18,24,29,0.55)">
                            <text x="10" y="30">JEMBER, EAST JAVA</text>
                        </g>
                    </svg>
                    <p class="mt-4 text-xs" style="color: rgba(18,24,29,0.5);">
                        Illustrative diagram, not a photograph of a real incident.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ FOOTER ============ -->
    <footer class="py-10" style="background: var(--ink);">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="f-mono text-[11px] uppercase tracking-widest" style="color: rgba(239,233,218,0.5);">
                Smartcity Jember · Level Crossing Safety System
            </div>
            <div class="f-mono text-[11px] uppercase tracking-widest flex gap-6" style="color: rgba(239,233,218,0.5);">
                <a href="{{ url('/dashboard') }}" class="hover:text-white transition-colors">Dashboard</a>
                <a href="#institutions" class="hover:text-white transition-colors">Institutions</a>
            </div>
        </div>
    </footer>

    <script>
    (function () {
        const scene = document.getElementById('scene');
        const ticker = document.getElementById('ticker');
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function rnd(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

        function addLine(text, cls) {
            const line = document.createElement('div');
            line.className = 'ticker-line' + (cls ? ' ' + cls : '');
            line.textContent = text;
            ticker.appendChild(line);
            while (ticker.children.length > 6) ticker.removeChild(ticker.firstChild);
            ticker.scrollTop = ticker.scrollHeight;
        }

        function readingLine(state, entrySide) {
            const far = () => rnd(260, 320);
            const near = () => rnd(4, 14);
            let a, b, trigA, trigB;
            if (state === 'safe') { a = far(); b = far(); trigA = 'no'; trigB = 'no'; }
            if (state === 'warning') {
                if (entrySide === 'A') { a = near(); b = far(); trigA = 'YES'; trigB = 'no'; }
                else { a = far(); b = near(); trigA = 'no'; trigB = 'YES'; }
            }
            if (state === 'danger') {
                if (entrySide === 'A') { a = -1; b = far(); trigA = 'YES'; trigB = 'no'; }
                else { a = far(); b = -1; trigA = 'no'; trigB = 'YES'; }
            }
            return `[state=${state.toUpperCase()}] A=${a}cm (trig=${trigA})  B=${b}cm (trig=${trigB})`;
        }

        if (reduced) {
            scene.setAttribute('data-state', 'safe');
            addLine(readingLine('safe'));
            addLine('SIMULATION PAUSED — REDUCED MOTION ENABLED', 'evt');
            return;
        }

        let entrySide = 'A';
        let tickTimer = null;
        const timers = [];
        const later = (fn, ms) => { const id = setTimeout(fn, ms); timers.push(id); return id; };

        function startTicking(state) {
            clearInterval(tickTimer);
            tickTimer = setInterval(() => addLine(readingLine(state, entrySide)), 550);
        }

        function cycle() {
            entrySide = Math.random() < 0.5 ? 'A' : 'B';
            scene.setAttribute('data-state', 'safe');
            scene.classList.remove('run-a', 'run-b');
            startTicking('safe');

            later(() => {
                scene.setAttribute('data-state', 'warning');
                addLine(`==> SAFE -> WARNING (entry=${entrySide})`, 'evt');
                startTicking('warning');
            }, 3200);

            later(() => {
                scene.setAttribute('data-state', 'danger');
                scene.classList.add(entrySide === 'A' ? 'run-a' : 'run-b');
                addLine('==> WARNING -> DANGER (closing gate now)', 'evt-danger');
                startTicking('danger');
            }, 3200 + 2000);

            later(() => {
                addLine(`==> DANGER -> SAFE (exit cleared, opening gate)`, 'evt-safe');
                clearInterval(tickTimer);
            }, 3200 + 2000 + 3400);

            later(cycle, 3200 + 2000 + 3400 + 700);
        }

        // Dev/QA hook: window.__stopScene() halts the auto-cycle cleanly
        // so the state machine can be inspected frame-by-frame.
        window.__stopScene = () => { timers.forEach(clearTimeout); clearInterval(tickTimer); };

        cycle();
    })();
    </script>
</body>
</html>

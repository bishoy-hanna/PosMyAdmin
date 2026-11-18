<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PosMyAdmin</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            500: '#336791',
                            600: '#2b567a',
                            700: '#21425e',
                            800: '#1b364d',
                            900: '#0f2030',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Smooth glowing background following mouse */
        #interactiveGlow {
            pointer-events: none;
            transition: background 0.15s ease-out;
        }
        /* Tilt card transition */
        .parallax-layer {
            transition: transform 0.1s cubic-bezier(0.2, 0, 0.2, 1);
            will-change: transform;
        }
    </style>
</head>
<body id="pageBody" class="h-full bg-slate-950 text-slate-100 flex flex-col font-sans overflow-x-hidden relative">

    <!-- Interactive Mouse Follow Glow Layer -->
    <div id="interactiveGlow" class="fixed inset-0 z-0"></div>

    <!-- Main Desktop Grid: Split Left & Right -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 min-h-screen relative z-10">

        <!-- ================= LEFT PART: Interactive Showcase & Features ================= -->
        <div class="lg:col-span-6 xl:col-span-7 bg-gradient-to-br from-brand-900/90 via-slate-900/90 to-slate-950/95 p-8 lg:p-14 flex flex-col justify-between border-b lg:border-b-0 lg:border-r border-slate-800/80 relative overflow-hidden">
            
            <!-- Background Decorative Ambient Elements -->
            <div class="absolute -top-32 -left-32 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Top Left: Logo & Badges -->
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-brand-600 via-sky-600 to-sky-400 flex items-center justify-center text-white shadow-lg shadow-sky-500/20">
                        <i data-lucide="database" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <span class="text-2xl font-black tracking-tight text-white">Pos<span class="text-sky-400">MyAdmin</span></span>
                        <span class="block text-[11px] font-mono text-slate-400 tracking-wider uppercase">PostgreSQL Web Manager</span>
                    </div>
                </div>

                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800/80 border border-slate-700/80 text-xs text-slate-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Ready for PostgreSQL 14, 15, 16, 17, 18+</span>
                </div>
            </div>

            <!-- Middle: Interactive Tilt Card (responds smoothly to mouse) -->
            <div class="my-8 lg:my-auto">
                <div id="tiltCard" class="parallax-layer bg-slate-900/80 backdrop-blur-xl border border-slate-700/80 rounded-2xl p-6 shadow-2xl max-w-lg mx-auto lg:mx-0 space-y-5">
                    
                    <!-- Card Top Header -->
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500/80 inline-block"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                            <span class="text-xs font-mono text-slate-400 ml-1">cluster_telemetry</span>
                        </div>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-emerald-950/70 text-emerald-400 border border-emerald-800/60 font-semibold">
                            ACTIVE
                        </span>
                    </div>

                    <!-- Live Metrics Mockup Grid -->
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800/80">
                            <span class="text-[10px] uppercase text-slate-400 font-semibold block">Cache Hit</span>
                            <span class="text-lg font-bold text-sky-400 font-mono">99.8%</span>
                        </div>
                        <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800/80">
                            <span class="text-[10px] uppercase text-slate-400 font-semibold block">Connections</span>
                            <span class="text-lg font-bold text-emerald-400 font-mono">100 Max</span>
                        </div>
                        <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800/80">
                            <span class="text-[10px] uppercase text-slate-400 font-semibold block">Encoding</span>
                            <span class="text-lg font-bold text-purple-400 font-mono">UTF8</span>
                        </div>
                    </div>

                    <!-- Highlight Features List -->
                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center gap-2.5 text-slate-300">
                            <div class="w-5 h-5 rounded-md bg-sky-500/20 text-sky-400 flex items-center justify-center shrink-0">
                                <i data-lucide="gauge" class="w-3.5 h-3.5"></i>
                            </div>
                            <span>Real-Time Performance Dashboard & Telemetry</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-slate-300">
                            <div class="w-5 h-5 rounded-md bg-purple-500/20 text-purple-400 flex items-center justify-center shrink-0">
                                <i data-lucide="link" class="w-3.5 h-3.5"></i>
                            </div>
                            <span>Relational Foreign Key Quick-Jump Navigation</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-slate-300">
                            <div class="w-5 h-5 rounded-md bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                                <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                            </div>
                            <span>1-Click Full Database Backup & SQL Import</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Left: Security Guarantees -->
            <div class="pt-4 border-t border-slate-800/70 flex flex-wrap items-center gap-6 text-xs text-slate-400">
                <div class="flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                    <span>AES-256 Encrypted Session</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <i data-lucide="lock" class="w-4 h-4 text-sky-400"></i>
                    <span>CSRF & XSS Hardened</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <i data-lucide="zap" class="w-4 h-4 text-amber-400"></i>
                    <span>Brute-Force Rate Limiting</span>
                </div>
            </div>

        </div>

        <!-- ================= RIGHT PART: Connection & Login Form ================= -->
        <div class="lg:col-span-6 xl:col-span-5 bg-slate-950 p-6 sm:p-10 lg:p-12 flex flex-col justify-center relative">
            
            <div class="w-full max-w-md mx-auto space-y-6">

                <!-- Header Title -->
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">Connect to Database</h2>
                    <p class="text-xs text-slate-400 mt-1">Enter your PostgreSQL server credentials to establish a secure session.</p>
                </div>

                <!-- Flash Notifications -->
                <?php if (!empty($flashes)): ?>
                    <div class="space-y-2">
                        <?php foreach ($flashes as $type => $messages): ?>
                            <?php foreach ($messages as $msg): 
                                $alertClass = ($type === 'success') ? 'bg-emerald-950/80 border-emerald-700 text-emerald-200' : 'bg-red-950/80 border-red-700 text-red-200';
                            ?>
                                <div class="p-3 border rounded-xl text-xs flex items-center gap-2 <?= $alertClass ?>">
                                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                                    <span><?= htmlspecialchars($msg) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Lockout Warning -->
                <?php if ($isLocked): ?>
                    <div class="p-4 border border-amber-600/50 bg-amber-950/50 rounded-xl text-xs text-amber-200 flex items-start gap-3">
                        <i data-lucide="shield-alert" class="w-5 h-5 text-amber-400 shrink-0 mt-0.5"></i>
                        <div>
                            <strong class="font-semibold block text-amber-300">Login Temporarily Locked</strong>
                            Too many failed attempts. Please wait <?= ceil($lockoutRemaining / 60) ?> minute(s) before trying again.
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Connection Form Card -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
                    <form action="/login" method="POST" class="space-y-4">
                        <?= PosMyAdmin\Auth\CSRF::field() ?>

                        <!-- Host & Port Grid -->
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-slate-300 mb-1" for="host">Server Host</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="server" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" id="host" name="host" required value="<?= htmlspecialchars((string)($defaults['host'] ?? '127.0.0.1')) ?>" 
                                        class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-8 pr-3 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent font-mono">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1" for="port">Port</label>
                                <input type="number" id="port" name="port" required value="<?= (int)($defaults['port'] ?? 5432) ?>" 
                                    class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent font-mono">
                            </div>
                        </div>

                        <!-- Database -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1" for="database">Database Name</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="hard-drive" class="w-4 h-4"></i>
                                </span>
                                <input type="text" id="database" name="database" required value="<?= htmlspecialchars((string)($defaults['database'] ?? 'postgres')) ?>" 
                                    class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-8 pr-3 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent font-mono">
                            </div>
                        </div>

                        <!-- Username -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1" for="username">Username</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="user" class="w-4 h-4"></i>
                                </span>
                                <input type="text" id="username" name="username" required value="<?= htmlspecialchars((string)($defaults['username'] ?? 'postgres')) ?>" 
                                    class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-8 pr-3 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent font-mono">
                            </div>
                        </div>

                        <!-- Password with eye toggle -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1" for="password">Password</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="lock" class="w-4 h-4"></i>
                                </span>
                                <input type="password" id="password" name="password" placeholder="Enter database password" 
                                    class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-8 pr-10 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                                <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white">
                                    <i data-lucide="eye" id="eyeIcon" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <!-- SSL Mode -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1" for="sslmode">SSL Mode</label>
                            <select id="sslmode" name="sslmode" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2.5 text-xs text-white focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent font-mono">
                                <option value="prefer" selected>prefer (Recommended)</option>
                                <option value="disable">disable</option>
                                <option value="require">require</option>
                                <option value="verify-ca">verify-ca</option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" <?= $isLocked ? 'disabled' : '' ?> class="w-full mt-2 bg-gradient-to-r from-sky-600 via-brand-600 to-sky-600 hover:from-sky-500 hover:to-brand-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold py-3 px-4 rounded-xl text-sm shadow-lg shadow-sky-600/20 transition flex items-center justify-center gap-2 group">
                            <span>Connect & Launch Session</span>
                            <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-1"></i>
                        </button>
                    </form>
                </div>

                <!-- Footer Quick Notes -->
                <div class="text-center text-xs text-slate-500">
                    <span>PosMyAdmin v1.0.0 &bull; Open-Source PostgreSQL Manager</span>
                </div>

            </div>

        </div>

    </div>

    <!-- Interactive Mouse Movement Script (Parallax & Radial Glow) -->
    <script>
        lucide.createIcons();

        function togglePasswordVisibility() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                pwd.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        // Mouse Move Glow & 3D Tilt Parallax Effect
        const glow = document.getElementById('interactiveGlow');
        const tiltCard = document.getElementById('tiltCard');

        window.addEventListener('mousemove', (e) => {
            const x = e.clientX;
            const y = e.clientY;

            // 1. Radial glow follows mouse cursor
            glow.style.background = `radial-gradient(750px at ${x}px ${y}px, rgba(56, 189, 248, 0.08), transparent 80%)`;

            // 2. Subtle 3D tilt for the Left Card
            if (tiltCard && window.innerWidth >= 1024) {
                const rect = tiltCard.getBoundingClientRect();
                const cardX = rect.left + rect.width / 2;
                const cardY = rect.top + rect.height / 2;

                // Calculate tilt angles (-8deg to +8deg)
                const rotateX = ((y - cardY) / window.innerHeight) * -10;
                const rotateY = ((x - cardX) / window.innerWidth) * 10;

                tiltCard.style.transform = `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translateY(${rotateX * 0.5}px)`;
            }
        });

        // Reset tilt on mouse leave
        window.addEventListener('mouseleave', () => {
            if (tiltCard) {
                tiltCard.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg)';
            }
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'PosMyAdmin - PostgreSQL Web Manager') ?></title>
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
                            200: '#bae0fd',
                            500: '#336791', /* PostgreSQL Classic Blue */
                            600: '#2b567a',
                            700: '#21425e',
                            800: '#1b364d',
                            900: '#152b3c',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .font-mono-code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 flex flex-col font-sans transition-colors duration-150">

    <!-- Top Navigation Bar -->
    <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-14 flex items-center justify-between px-4 sticky top-0 z-30 shrink-0">
        <!-- Left: Logo & Database Switcher -->
        <div class="flex items-center space-x-3">
            <button id="sidebarToggleBtn" class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none">
                <i data-lucide="menu" class="w-5 h-5"></i>
            </button>
            <a href="/" class="flex items-center space-x-2 text-brand-600 dark:text-brand-400 font-bold text-lg tracking-tight">
                <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white shadow-sm">
                    <i data-lucide="database" class="w-5 h-5"></i>
                </div>
                <span>Pos<span class="text-slate-900 dark:text-white">MyAdmin</span></span>
            </a>

            <!-- Quick Database Switcher Form -->
            <form action="/database/switch" method="POST" class="hidden md:flex items-center ml-4 pl-4 border-l border-slate-200 dark:border-slate-700">
                <?= PosMyAdmin\Auth\CSRF::field() ?>
                <label for="quickDbSelect" class="text-xs font-medium text-slate-500 dark:text-slate-400 mr-2 flex items-center gap-1">
                    <i data-lucide="hard-drive" class="w-3.5 h-3.5"></i> DB:
                </label>
                <select id="quickDbSelect" name="database" onchange="this.form.submit()" class="text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-md border border-slate-300 dark:border-slate-600 px-2.5 py-1 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <?php foreach ($databases as $db): 
                        $dbName = (string)($db['datname'] ?? $db['name'] ?? '');
                        $dbSize = (string)($db['size'] ?? $db['size_pretty'] ?? '');
                        if ($dbName === '') continue;
                    ?>
                        <option value="<?= htmlspecialchars($dbName) ?>" <?= ($dbName === $currentDb) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dbName) ?><?= $dbSize !== '' ? ' (' . htmlspecialchars($dbSize) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Right: Server Badge, Quick Actions, User & Logout -->
        <div class="flex items-center space-x-2">
            <!-- Connection Info Pill -->
            <div class="hidden sm:flex items-center space-x-1.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60 rounded-full px-3 py-0.5 text-xs font-medium">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span><?= htmlspecialchars($user['username']) ?>@<?= htmlspecialchars($user['host']) ?>:<?= htmlspecialchars($user['port']) ?></span>
            </div>

            <!-- SQL Console button -->
            <a href="/sql" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-md bg-brand-50 hover:bg-brand-100 dark:bg-brand-900/40 dark:hover:bg-brand-900/60 text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-800 transition">
                <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                <span>SQL</span>
            </a>

            <!-- Dark Mode Toggle -->
            <button id="themeToggleBtn" title="Toggle dark mode" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 dark:text-slate-400 transition">
                <i data-lucide="moon" class="w-4 h-4 hidden dark:block"></i>
                <i data-lucide="sun" class="w-4 h-4 block dark:hidden"></i>
            </button>

            <!-- Logout Button -->
            <form action="/logout" method="POST" class="inline">
                <?= PosMyAdmin\Auth\CSRF::field() ?>
                <button type="submit" title="Disconnect and Logout" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 transition">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    <span class="hidden md:inline">Logout</span>
                </button>
            </form>
        </div>
    </header>

    <!-- Main Container: Sidebar + Content -->
    <div class="flex-1 flex overflow-hidden">
        
        <!-- Sidebar Navigation Tree -->
        <aside id="sidebar" class="w-64 lg:w-72 bg-white dark:bg-slate-800/95 border-r border-slate-200 dark:border-slate-700 flex flex-col shrink-0 fixed lg:static inset-y-14 left-0 z-20 -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out">
            <!-- Sidebar Header & Quick Links -->
            <div class="p-3 border-b border-slate-200 dark:border-slate-700 space-y-1">
                <?php $reqUri = $_SERVER['REQUEST_URI'] ?? '/'; ?>
                <a href="/" class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-md <?= empty($_GET['table']) && ($reqUri === '/' || $reqUri === '/dashboard') ? 'bg-brand-500 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
                    <i data-lucide="gauge" class="w-4 h-4"></i>
                    <span>Server Dashboard</span>
                </a>
                <a href="/databases" class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-md <?= str_starts_with($reqUri, '/databases') ? 'bg-brand-500 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                    <span>Databases & Schemas</span>
                </a>
                <a href="/sql" class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-md <?= str_starts_with($reqUri, '/sql') ? 'bg-brand-500 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
                    <i data-lucide="terminal" class="w-4 h-4"></i>
                    <span>SQL Query Runner</span>
                </a>
                <a href="/processes" class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-md <?= str_starts_with($reqUri, '/processes') ? 'bg-brand-500 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
                    <i data-lucide="activity" class="w-4 h-4"></i>
                    <span>Server Activity (PID)</span>
                </a>
                <a href="/database/backup" class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-md <?= str_starts_with($reqUri, '/database/backup') ? 'bg-brand-500 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
                    <i data-lucide="archive" class="w-4 h-4"></i>
                    <span>Backup & Import</span>
                </a>
                <a href="/logs" class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-md <?= str_starts_with($reqUri, '/logs') ? 'bg-brand-500 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span>Error Logs</span>
                </a>
            </div>

            <!-- Database & Tables Tree Navigation -->
            <div class="flex-1 overflow-y-auto p-3 space-y-3">
                <div class="flex items-center justify-between text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">
                    <span>Current Database: <?= htmlspecialchars($currentDb) ?></span>
                    <a href="/table/create?schema=<?= urlencode($currentSchema) ?>" title="Create Table" class="text-brand-600 hover:text-brand-700 dark:text-brand-400">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    </a>
                </div>

                <?php if (empty($navTree)): ?>
                    <div class="text-xs text-slate-400 px-2 py-4 italic">No tables or schemas found.</div>
                <?php else: ?>
                    <?php foreach ($navTree as $schemaName => $tables): ?>
                        <div class="space-y-1">
                            <!-- Schema Accordion Header -->
                            <div class="flex items-center justify-between px-2 py-1 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded hover:bg-slate-100 dark:hover:bg-slate-700/60 cursor-pointer select-none" onclick="toggleSchema('schema-<?= htmlspecialchars($schemaName) ?>')">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="folder" class="w-3.5 h-3.5 text-amber-500"></i>
                                    <span><?= htmlspecialchars($schemaName) ?></span>
                                </div>
                                <span class="text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 px-1.5 py-0.2 rounded-full">
                                    <?= count($tables) ?>
                                </span>
                            </div>

                            <!-- Tables List inside Schema -->
                            <div id="schema-<?= htmlspecialchars($schemaName) ?>" class="pl-3 space-y-0.5">
                                <?php if (empty($tables)): ?>
                                    <div class="text-[11px] text-slate-400 italic py-1 pl-3">No tables</div>
                                <?php else: ?>
                                    <?php foreach ($tables as $t): 
                                        $isCurrent = ($currentSchema === $schemaName && $currentTable === $t['table_name']);
                                        $isView = ($t['table_type'] === 'VIEW');
                                    ?>
                                        <a href="/table/browse?schema=<?= urlencode($schemaName) ?>&table=<?= urlencode($t['table_name']) ?>" 
                                           class="flex items-center justify-between px-2 py-1 text-xs rounded-md transition <?= $isCurrent ? 'bg-brand-500 text-white font-medium shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
                                            <div class="flex items-center gap-1.5 truncate">
                                                <i data-lucide="<?= $isView ? 'eye' : 'table-2' ?>" class="w-3.5 h-3.5 <?= $isCurrent ? 'text-white' : ($isView ? 'text-cyan-500' : 'text-slate-400') ?>"></i>
                                                <span class="truncate" title="<?= htmlspecialchars($t['table_name']) ?>"><?= htmlspecialchars($t['table_name']) ?></span>
                                            </div>
                                            <span class="text-[10px] <?= $isCurrent ? 'text-brand-100' : 'text-slate-400' ?> ml-1 shrink-0">
                                                <?= number_format((int)$t['estimated_rows']) ?>
                                            </span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Sidebar overlay on mobile -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-10 hidden lg:hidden" onclick="toggleSidebar()"></div>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto flex flex-col min-w-0 bg-slate-50 dark:bg-slate-900">
            <!-- Flash Notifications -->
            <?php if (!empty($flashes)): ?>
                <div class="p-4 pb-0 space-y-2">
                    <?php foreach ($flashes as $type => $messages): ?>
                        <?php foreach ($messages as $msg): 
                            $colors = match($type) {
                                'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300',
                                'error' => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/40 dark:border-red-800 dark:text-red-300',
                                'warning' => 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-300',
                                default => 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-950/40 dark:border-blue-800 dark:text-blue-300',
                            };
                            $icon = match($type) {
                                'success' => 'check-circle-2',
                                'error' => 'alert-triangle',
                                'warning' => 'alert-circle',
                                default => 'info',
                            };
                        ?>
                            <div class="flex items-center justify-between p-3.5 border rounded-lg text-sm <?= $colors ?> shadow-sm">
                                <div class="flex items-center gap-2.5">
                                    <i data-lucide="<?= $icon ?>" class="w-4 h-4 shrink-0"></i>
                                    <span><?= htmlspecialchars($msg) ?></span>
                                </div>
                                <button onclick="this.parentElement.remove()" class="text-current opacity-70 hover:opacity-100">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- View Specific Body Slot -->
            <div class="flex-1 p-4 lg:p-6">
                <?= $content ?? '' ?>
            </div>

            <!-- Footer -->
            <footer class="h-10 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/60 px-4 flex items-center justify-between text-xs text-slate-400 shrink-0">
                <div class="flex items-center gap-2">
                    <span>PosMyAdmin v1.0.0</span>
                    <span>•</span>
                    <span>PostgreSQL Web Management | Made By : Bishoy Hanna</span>
                </div>
                <div class="flex items-center gap-3">
                    <span>Client: <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?></span>
                </div>
            </footer>
        </main>
    </div>

    <!-- UI Scripts -->
    <script>
        // Init Lucide Icons
        lucide.createIcons();

        // Theme Toggle (Light / Dark)
        const themeBtn = document.getElementById('themeToggleBtn');
        if (localStorage.getItem('posmyadmin_theme') === 'dark' || (!localStorage.getItem('posmyadmin_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        themeBtn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('posmyadmin_theme', isDark ? 'dark' : 'light');
            lucide.createIcons();
        });

        // Mobile Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggleBtn');

        function toggleSidebar() {
            const isClosed = sidebar.classList.contains('-translate-x-full');
            if (isClosed) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
        if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);

        // Schema Accordion toggle
        function toggleSchema(id) {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('hidden');
        }
    </script>
</body>
</html>

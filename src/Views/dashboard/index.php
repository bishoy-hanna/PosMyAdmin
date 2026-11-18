<?php
$core = $overview['core'] ?? [];
$uptime = $overview['uptime'] ?? [];
$conn = $overview['connections'] ?? [];
$perf = $overview['performance'] ?? [];
$config = $overview['configuration'] ?? [];
$databases = $overview['databases'] ?? [];
$summary = $overview['database_summary'] ?? [];

$totalClusterBytes = 0;
foreach ($databases as $d) {
    $totalClusterBytes += (float)($d['size_bytes'] ?? 0);
}
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
$clusterSizeFormatted = formatBytes($totalClusterBytes);
?>

<div class="space-y-6">

    <!-- Top Server Banner -->
    <div class="bg-gradient-to-r from-brand-700 via-brand-800 to-slate-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
            <i data-lucide="database" class="w-64 h-64"></i>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Server Online
                    </span>
                    <span class="text-xs text-brand-200">Port <?= htmlspecialchars($core['port'] ?? '5432') ?></span>
                    <span class="text-xs text-brand-200">•</span>
                    <span class="text-xs text-brand-200"><?= htmlspecialchars($core['timezone'] ?? 'UTC') ?></span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                    <?= htmlspecialchars($core['short_version'] ? 'PostgreSQL ' . $core['short_version'] : 'PostgreSQL Server') ?>
                </h1>
                <p class="text-xs text-brand-200 mt-1 max-w-2xl font-mono truncate" title="<?= htmlspecialchars($core['full_version'] ?? '') ?>">
                    <?= htmlspecialchars($core['full_version'] ?? '') ?>
                </p>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="/sql" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg bg-sky-500 hover:bg-sky-400 text-white shadow-sm transition">
                    <i data-lucide="terminal" class="w-4 h-4"></i>
                    <span>Execute SQL</span>
                </a>
                <a href="/processes" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white/10 hover:bg-white/20 text-white backdrop-blur border border-white/20 transition">
                    <i data-lucide="activity" class="w-4 h-4"></i>
                    <span>Activity (PID)</span>
                </a>
                <a href="/databases" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white/10 hover:bg-white/20 text-white backdrop-blur border border-white/20 transition">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                    <span>Databases (<?= count($databases) ?>)</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 Key Server Health Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Server Uptime -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Server Uptime</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-2">
                <div class="text-xl font-bold text-slate-900 dark:text-white">
                    <?= htmlspecialchars($uptime['uptime_formatted'] ?? 'N/A') ?>
                </div>
                <div class="text-[11px] text-slate-400 mt-1 truncate" title="Started at <?= htmlspecialchars($uptime['start_time'] ?? '') ?>">
                    Started: <?= htmlspecialchars(substr($uptime['start_time'] ?? '', 0, 19)) ?>
                </div>
            </div>
        </div>

        <!-- Card 2: Active Connection Pool -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Connection Pool</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <i data-lucide="users" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-2">
                <div class="flex items-baseline justify-between">
                    <div class="text-xl font-bold text-slate-900 dark:text-white">
                        <?= (int)($conn['current'] ?? 0) ?> <span class="text-xs font-normal text-slate-400">/ <?= (int)($conn['max'] ?? 100) ?> max</span>
                    </div>
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        <?= $conn['usage_percent'] ?>%
                    </span>
                </div>
                <!-- Progress bar -->
                <div class="w-full bg-slate-100 dark:bg-slate-700 h-1.5 rounded-full mt-2 overflow-hidden">
                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: <?= min(100, (float)$conn['usage_percent']) ?>%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1">
                    <span>Active: <?= (int)$conn['active'] ?></span>
                    <span>Idle: <?= (int)$conn['idle'] ?></span>
                </div>
            </div>
        </div>

        <!-- Card 3: Cache Hit Ratio -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Buffer Cache Hit Ratio</span>
                <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                    <i data-lucide="zap" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-2">
                <div class="flex items-baseline justify-between">
                    <div class="text-xl font-bold text-slate-900 dark:text-white">
                        <?= $perf['cache_hit_ratio'] ?>%
                    </div>
                    <span class="text-[11px] font-medium text-slate-400">
                        <?= ($perf['cache_hit_ratio'] >= 99) ? 'Excellent' : 'Good' ?>
                    </span>
                </div>
                <!-- Progress bar -->
                <div class="w-full bg-slate-100 dark:bg-slate-700 h-1.5 rounded-full mt-2 overflow-hidden">
                    <div class="bg-sky-500 h-1.5 rounded-full" style="width: <?= min(100, (float)$perf['cache_hit_ratio']) ?>%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1">
                    <span>Blocks Hit: <?= number_format($perf['blks_hit'] ?? 0) ?></span>
                </div>
            </div>
        </div>

        <!-- Card 4: Cluster Storage & Databases -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Cluster Storage</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                    <i data-lucide="hard-drive" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-2">
                <div class="text-xl font-bold text-slate-900 dark:text-white">
                    <?= htmlspecialchars($clusterSizeFormatted) ?>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1">
                    <span><?= count($databases) ?> Databases</span>
                    <span>Commit: <?= $perf['commit_ratio'] ?>%</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Active Connected Database Quick Overview Strip -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-700/60">
            <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full bg-brand-500"></div>
                <span class="text-xs font-medium text-slate-400">Active Database:</span>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-100 font-mono"><?= htmlspecialchars($core['current_database'] ?? 'postgres') ?></span>
                <span class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded font-medium">
                    <?= htmlspecialchars($summary['current_db_size'] ?? '0 MB') ?>
                </span>
            </div>
            <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1"><i data-lucide="table-2" class="w-3.5 h-3.5 text-brand-500"></i> <strong><?= (int)($summary['tables'] ?? 0) ?></strong> Tables</span>
                <span class="flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5 text-cyan-500"></i> <strong><?= (int)($summary['views'] ?? 0) ?></strong> Views</span>
                <span class="flex items-center gap-1"><i data-lucide="folder" class="w-3.5 h-3.5 text-amber-500"></i> <strong><?= (int)($summary['schemas'] ?? 0) ?></strong> Schemas</span>
            </div>
        </div>

        <!-- Quick System Settings Bar -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mt-3 pt-1 text-xs">
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">shared_buffers</span>
                <span class="font-mono text-slate-700 dark:text-slate-300"><?= htmlspecialchars($config['shared_buffers'] ?? 'N/A') ?></span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">work_mem</span>
                <span class="font-mono text-slate-700 dark:text-slate-300"><?= htmlspecialchars($config['work_mem'] ?? 'N/A') ?></span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">maintenance_work_mem</span>
                <span class="font-mono text-slate-700 dark:text-slate-300"><?= htmlspecialchars($config['maintenance_work_mem'] ?? 'N/A') ?></span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">effective_cache</span>
                <span class="font-mono text-slate-700 dark:text-slate-300"><?= htmlspecialchars($config['effective_cache_size'] ?? 'N/A') ?></span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">wal_level</span>
                <span class="font-mono text-slate-700 dark:text-slate-300"><?= htmlspecialchars($config['wal_level'] ?? 'N/A') ?></span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">encoding</span>
                <span class="font-mono text-slate-700 dark:text-slate-300"><?= htmlspecialchars($core['server_encoding'] ?? 'UTF8') ?></span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">fsync</span>
                <span class="font-mono text-slate-700 dark:text-slate-300"><?= htmlspecialchars($config['fsync'] ?? 'N/A') ?></span>
            </div>
        </div>
    </div>

    <!-- Databases Overview Table -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="hard-drive" class="w-4 h-4 text-brand-500"></i>
                    <span>Databases on this Server (<?= count($databases) ?>)</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Click any database to switch your active session to it.</p>
            </div>
            <a href="/databases" class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 flex items-center gap-1">
                <span>Manage Databases</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-750 text-slate-500 dark:text-slate-400 uppercase font-semibold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3">Database Name</th>
                        <th class="px-4 py-3">Owner</th>
                        <th class="px-4 py-3">Encoding</th>
                        <th class="px-4 py-3">Collation</th>
                        <th class="px-4 py-3">Disk Size</th>
                        <th class="px-4 py-3">Connections</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($databases as $d): 
                        $isCurrent = ($d['name'] === ($core['current_database'] ?? ''));
                    ?>
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/50 transition <?= $isCurrent ? 'bg-brand-50/40 dark:bg-brand-950/20' : '' ?>">
                            <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                <i data-lucide="database" class="w-4 h-4 <?= $isCurrent ? 'text-brand-500' : 'text-slate-400' ?>"></i>
                                <span><?= htmlspecialchars($d['name']) ?></span>
                                <?php if ($isCurrent): ?>
                                    <span class="text-[10px] bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300 font-bold px-1.5 py-0.5 rounded">ACTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300"><?= htmlspecialchars($d['owner']) ?></td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 font-mono"><?= htmlspecialchars($d['encoding']) ?></td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 font-mono"><?= htmlspecialchars($d['collation']) ?></td>
                            <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-200"><?= htmlspecialchars($d['size_pretty']) ?></td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full <?= ((int)$d['connections'] > 0) ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                                    <?= (int)$d['connections'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <?php if (!$isCurrent): ?>
                                    <form action="/database/switch" method="POST" class="inline">
                                        <?= PosMyAdmin\Auth\CSRF::field() ?>
                                        <input type="hidden" name="database" value="<?= htmlspecialchars($d['name']) ?>">
                                        <button type="submit" class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                            Switch to DB
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">Connected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

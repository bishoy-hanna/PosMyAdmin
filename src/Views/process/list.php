<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="activity" class="w-5 h-5 text-brand-500"></i>
                <span>Server Activity & Processes</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Live active connections and backend processes from <code>pg_stat_activity</code>.</p>
        </div>

        <button onclick="window.location.reload()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">
            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
            <span>Refresh</span>
        </button>
    </div>

    <!-- Processes Table -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-slate-50 dark:bg-slate-750 text-slate-500 dark:text-slate-400 uppercase font-semibold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3">PID</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Database</th>
                        <th class="px-4 py-3">Client IP</th>
                        <th class="px-4 py-3">State</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">Query</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($processes)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400 italic">No backend processes found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($processes as $proc): 
                            $state = $proc['state'] ?? 'idle';
                            $stateColor = match($state) {
                                'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 font-bold',
                                'idle' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                                'idle in transaction' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 font-bold',
                                default => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                            };
                            $duration = (float)($proc['duration_seconds'] ?? 0);
                        ?>
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-750/50 transition">
                                <td class="px-4 py-3 font-mono font-bold text-slate-800 dark:text-slate-200"><?= (int)$proc['pid'] ?></td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300"><?= htmlspecialchars($proc['usename'] ?? '') ?></td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400 font-mono"><?= htmlspecialchars($proc['datname'] ?? '') ?></td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 font-mono text-[11px]"><?= htmlspecialchars($proc['client_addr'] ?? 'local') ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] <?= $stateColor ?>">
                                        <?= htmlspecialchars($state) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px] <?= ($duration > 10) ? 'text-amber-600 font-bold' : 'text-slate-500 dark:text-slate-400' ?>">
                                    <?= ($duration > 0) ? ($duration . 's') : '-' ?>
                                </td>
                                <td class="px-4 py-3 max-w-md truncate font-mono text-[11px] text-slate-700 dark:text-slate-300" title="<?= htmlspecialchars($proc['query'] ?? '') ?>">
                                    <?= htmlspecialchars($proc['query'] ?? '-') ?>
                                </td>
                                <td class="px-4 py-3 text-right space-x-1">
                                    <form action="/processes/terminate" method="POST" class="inline" onsubmit="return confirm('Cancel active query for PID <?= (int)$proc['pid'] ?>?');">
                                        <?= PosMyAdmin\Auth\CSRF::field() ?>
                                        <input type="hidden" name="pid" value="<?= (int)$proc['pid'] ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="px-2 py-1 text-[11px] font-semibold text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded">
                                            Cancel
                                        </button>
                                    </form>
                                    <form action="/processes/terminate" method="POST" class="inline" onsubmit="return confirm('Terminate backend process PID <?= (int)$proc['pid'] ?>? This closes the client connection.');">
                                        <?= PosMyAdmin\Auth\CSRF::field() ?>
                                        <input type="hidden" name="pid" value="<?= (int)$proc['pid'] ?>">
                                        <input type="hidden" name="action" value="terminate">
                                        <button type="submit" class="px-2 py-1 text-[11px] font-semibold text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 rounded">
                                            Kill
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

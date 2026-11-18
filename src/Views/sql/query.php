<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="terminal" class="w-5 h-5 text-brand-500"></i>
                <span>SQL Query Console</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Execute PostgreSQL statements, inspect plans, and export results.</p>
        </div>
    </div>

    <!-- Query Editor Card -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm space-y-3">
        <!-- Quick Snippet Buttons -->
        <div class="flex items-center gap-2 flex-wrap text-xs">
            <span class="text-slate-400 font-medium">Snippets:</span>
            <button type="button" onclick="setQuery('SELECT * FROM information_schema.tables WHERE table_schema = \'public\';')" class="px-2 py-1 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded text-slate-700 dark:text-slate-300 font-mono text-[11px]">List Tables</button>
            <button type="button" onclick="setQuery('SELECT pg_size_pretty(pg_database_size(current_database()));')" class="px-2 py-1 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded text-slate-700 dark:text-slate-300 font-mono text-[11px]">Database Size</button>
            <button type="button" onclick="setQuery('SELECT pid, usename, state, query FROM pg_stat_activity WHERE state != \'idle\';')" class="px-2 py-1 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded text-slate-700 dark:text-slate-300 font-mono text-[11px]">Active Queries</button>
            <button type="button" onclick="setQuery('SHOW ALL;')" class="px-2 py-1 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded text-slate-700 dark:text-slate-300 font-mono text-[11px]">Show Config</button>
        </div>

        <form id="sqlForm" action="/sql/execute" method="POST" class="space-y-3">
            <?= PosMyAdmin\Auth\CSRF::field() ?>
            <input type="hidden" id="sqlAction" name="action" value="execute">

            <div class="relative rounded-lg overflow-hidden border border-slate-300 dark:border-slate-700">
                <textarea id="sqlTextarea" name="query" rows="7" placeholder="Write PostgreSQL query here... (Press Ctrl + Enter to run)" class="w-full bg-slate-900 text-sky-300 p-4 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 leading-relaxed"><?= htmlspecialchars($initialQuery ?? '') ?></textarea>
            </div>

            <div class="flex items-center justify-between flex-wrap gap-2 pt-1">
                <div class="text-[11px] text-slate-400 flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded font-mono">Ctrl</kbd> + 
                    <kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded font-mono">Enter</kbd> to execute
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="runExplain()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">
                        <i data-lucide="help-circle" class="w-3.5 h-3.5 text-sky-500"></i>
                        <span>EXPLAIN ANALYZE</span>
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-semibold rounded-lg bg-brand-600 hover:bg-brand-500 text-white shadow-sm transition">
                        <i data-lucide="play" class="w-3.5 h-3.5"></i>
                        <span>Execute Query</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Query Results Section -->
    <?php if ($result !== null): ?>
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden space-y-0">
            <!-- Results Header & Stats -->
            <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <?php if ($result['success']): ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> Success
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            Duration: <strong class="text-slate-700 dark:text-slate-200 font-mono"><?= $result['duration_ms'] ?> ms</strong>
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">•</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            <?= $result['is_select'] ? (count($result['rows']) . ' row(s) returned') : ($result['affected_rows'] . ' row(s) affected') ?>
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> Execution Error
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            Duration: <strong class="font-mono"><?= $result['duration_ms'] ?> ms</strong>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Export Results if SELECT -->
                <?php if ($result['success'] && $result['is_select'] && !empty($result['rows'])): ?>
                    <form action="/sql/export" method="POST" class="flex items-center gap-2">
                        <?= PosMyAdmin\Auth\CSRF::field() ?>
                        <input type="hidden" name="query" value="<?= htmlspecialchars($result['query']) ?>">
                        <span class="text-xs text-slate-400">Export:</span>
                        <button type="submit" name="format" value="csv" class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded text-slate-700 dark:text-slate-300">CSV</button>
                        <button type="submit" name="format" value="json" class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded text-slate-700 dark:text-slate-300">JSON</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Error message if failed -->
            <?php if (!$result['success']): ?>
                <div class="p-6 bg-red-50 dark:bg-red-950/40 text-red-800 dark:text-red-300 font-mono text-xs overflow-x-auto whitespace-pre-wrap">
                    <?= htmlspecialchars($result['error'] ?? 'Unknown SQL error.') ?>
                </div>
            <?php elseif ($result['is_select']): ?>
                <!-- Result Data Table -->
                <div class="overflow-x-auto max-h-[500px]">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 dark:bg-slate-750 text-slate-500 dark:text-slate-400 uppercase font-semibold border-b border-slate-200 dark:border-slate-700 sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-2 w-10 text-slate-400">#</th>
                                <?php foreach ($result['columns'] as $col): ?>
                                    <th class="px-4 py-2 font-mono"><?= htmlspecialchars($col) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <?php if (empty($result['rows'])): ?>
                                <tr>
                                    <td colspan="<?= count($result['columns']) + 1 ?>" class="px-6 py-8 text-center text-slate-400 italic">
                                        Query returned 0 rows.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($result['rows'] as $rIdx => $row): ?>
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-750/50 transition">
                                        <td class="px-3 py-2 text-slate-400 text-[10px]"><?= $rIdx + 1 ?></td>
                                        <?php foreach ($result['columns'] as $col): 
                                            $val = $row[$col] ?? null;
                                        ?>
                                            <td class="px-4 py-2 text-slate-700 dark:text-slate-300 font-mono text-[11px] max-w-sm truncate" title="<?= htmlspecialchars((string)$val) ?>">
                                                <?php if ($val === null): ?>
                                                    <span class="text-slate-400 italic">NULL</span>
                                                <?php elseif (is_bool($val)): ?>
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold <?= $val ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>">
                                                        <?= $val ? 'TRUE' : 'FALSE' ?>
                                                    </span>
                                                <?php else: ?>
                                                    <?= htmlspecialchars((string)$val) ?>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <!-- Non-select result summary -->
                <div class="p-6 text-center text-xs text-slate-600 dark:text-slate-300">
                    <i data-lucide="check-circle" class="w-8 h-8 text-emerald-500 mx-auto mb-2"></i>
                    <p class="font-semibold text-sm">Query executed successfully.</p>
                    <p class="text-slate-400 mt-1"><?= $result['affected_rows'] ?> row(s) affected.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<script>
    function setQuery(q) {
        document.getElementById('sqlTextarea').value = q;
        document.getElementById('sqlTextarea').focus();
    }

    function runExplain() {
        document.getElementById('sqlAction').value = 'explain';
        document.getElementById('sqlForm').submit();
    }

    // Ctrl + Enter shortcut to run query
    document.getElementById('sqlTextarea').addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('sqlAction').value = 'execute';
            document.getElementById('sqlForm').submit();
        }
    });
</script>

<div class="space-y-6">

    <!-- Table Header & Sub-navigation Tabs -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
            <div>
                <div class="flex items-center gap-2">
                    <i data-lucide="columns-3" class="w-5 h-5 text-brand-500"></i>
                    <h1 class="text-lg font-bold text-slate-900 dark:text-white font-mono">
                        <span class="text-slate-400 font-normal"><?= htmlspecialchars($schema) ?>.</span><?= htmlspecialchars($table) ?>
                    </h1>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Table Structure, Columns, Constraints & Indexes</p>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="flex items-center gap-2 mt-3 pt-1 text-xs font-medium">
            <a href="/table/browse?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                <i data-lucide="grid" class="w-3.5 h-3.5"></i>
                <span>Browse</span>
            </a>
            <a href="/table/structure?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-500 text-white font-semibold">
                <i data-lucide="columns-3" class="w-3.5 h-3.5"></i>
                <span>Structure</span>
            </a>
            <a href="/table/insert?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Insert Row</span>
            </a>
            <a href="/sql?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                <span>SQL</span>
            </a>
        </div>
    </div>

    <!-- Columns Definition Card -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-brand-500"></i>
                <span>Columns (<?= count($columns) ?>)</span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-750 text-slate-500 dark:text-slate-400 uppercase font-semibold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Column Name</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Nullable</th>
                        <th class="px-4 py-3">Default Value</th>
                        <th class="px-4 py-3">Key</th>
                        <th class="px-4 py-3">Comment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($columns as $idx => $c): ?>
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/50 transition">
                            <td class="px-4 py-3 text-slate-400"><?= $idx + 1 ?></td>
                            <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200 font-mono">
                                <?= htmlspecialchars($c['column_name']) ?>
                            </td>
                            <td class="px-4 py-3 text-brand-600 dark:text-brand-400 font-mono">
                                <?= htmlspecialchars($c['data_type']) ?>
                                <?php if (!empty($c['character_maximum_length'])): ?>
                                    (<?= (int)$c['character_maximum_length'] ?>)
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($c['is_nullable'] === 'YES'): ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">YES</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">NO</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-400">
                                <?= htmlspecialchars($c['column_default'] ?? 'NULL') ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($c['is_primary_key']): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                        <i data-lucide="key" class="w-3 h-3 text-amber-600"></i> PRIMARY
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-400 italic">
                                <?= htmlspecialchars($c['column_comment'] ?? '') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Indexes & Constraints Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Indexes -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="zap" class="w-4 h-4 text-sky-500"></i>
                    <span>Indexes (<?= count($indexes) ?>)</span>
                </h3>
            </div>
            <div class="p-4 divide-y divide-slate-100 dark:divide-slate-750">
                <?php if (empty($indexes)): ?>
                    <div class="text-xs text-slate-400 italic">No indexes defined.</div>
                <?php else: ?>
                    <?php foreach ($indexes as $idx): ?>
                        <div class="py-2.5 first:pt-0 last:pb-0">
                            <div class="text-xs font-semibold text-slate-800 dark:text-slate-200 font-mono">
                                <?= htmlspecialchars($idx['indexname']) ?>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-0.5 break-all">
                                <?= htmlspecialchars($idx['indexdef']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Foreign Keys -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="link" class="w-4 h-4 text-purple-500"></i>
                    <span>Foreign Keys (<?= count($foreignKeys) ?>)</span>
                </h3>
            </div>
            <div class="p-4 divide-y divide-slate-100 dark:divide-slate-750">
                <?php if (empty($foreignKeys)): ?>
                    <div class="text-xs text-slate-400 italic">No foreign key constraints.</div>
                <?php else: ?>
                    <?php foreach ($foreignKeys as $fk): ?>
                        <div class="py-2.5 first:pt-0 last:pb-0 text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300 font-mono"><?= htmlspecialchars($fk['constraint_name']) ?></span>
                            <div class="text-slate-600 dark:text-slate-400 mt-1 flex items-center gap-1.5 font-mono text-[11px]">
                                <span><?= htmlspecialchars($fk['column_name']) ?></span>
                                <i data-lucide="arrow-right" class="w-3 h-3 text-slate-400"></i>
                                <span><?= htmlspecialchars($fk['foreign_table_name']) ?>(<?= htmlspecialchars($fk['foreign_column_name']) ?>)</span>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1">
                                ON UPDATE <?= htmlspecialchars($fk['update_rule']) ?> / ON DELETE <?= htmlspecialchars($fk['delete_rule']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

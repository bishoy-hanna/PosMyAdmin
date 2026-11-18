<div class="max-w-4xl space-y-6">

    <!-- Table Header & Sub-navigation Tabs -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
            <div>
                <div class="flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-5 h-5 text-brand-500"></i>
                    <h1 class="text-lg font-bold text-slate-900 dark:text-white font-mono">
                        <span class="text-slate-400 font-normal"><?= htmlspecialchars($schema) ?>.</span><?= htmlspecialchars($table) ?>
                    </h1>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Insert a new record into table</p>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="flex items-center gap-2 mt-3 pt-1 text-xs font-medium">
            <a href="/table/browse?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                <i data-lucide="grid" class="w-3.5 h-3.5"></i>
                <span>Browse</span>
            </a>
            <a href="/table/structure?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                <i data-lucide="columns-3" class="w-3.5 h-3.5"></i>
                <span>Structure</span>
            </a>
            <a href="/table/insert?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-500 text-white font-semibold">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Insert Row</span>
            </a>
            <a href="/sql?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                <span>SQL</span>
            </a>
        </div>
    </div>

    <!-- Insert Form -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 shadow-sm">
        <form action="/table/insert" method="POST" class="space-y-4 text-xs">
            <?= PosMyAdmin\Auth\CSRF::field() ?>
            <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
            <input type="hidden" name="schema" value="<?= htmlspecialchars($schema) ?>">

            <div class="divide-y divide-slate-100 dark:divide-slate-700">
                <?php foreach ($columns as $c): 
                    $colName = $c['column_name'];
                    $udt = strtolower($c['udt_name']);
                    $isAuto = str_contains((string)$c['column_default'], 'nextval(');
                ?>
                    <div class="py-3.5 first:pt-0 last:pb-0 grid grid-cols-1 md:grid-cols-4 gap-2 items-start">
                        <div class="md:col-span-1">
                            <label class="font-semibold text-slate-800 dark:text-slate-200 font-mono block">
                                <?= htmlspecialchars($colName) ?>
                                <?php if ($c['is_primary_key']): ?>
                                    <span class="text-amber-500 font-bold">*</span>
                                <?php endif; ?>
                            </label>
                            <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($c['data_type']) ?></span>
                            <?php if ($isAuto): ?>
                                <span class="text-[9px] bg-slate-100 dark:bg-slate-700 text-slate-500 px-1.5 py-0.5 rounded font-mono">auto-increment</span>
                            <?php endif; ?>
                        </div>

                        <div class="md:col-span-3">
                            <?php if ($udt === 'bool'): ?>
                                <select name="data[<?= htmlspecialchars($colName) ?>]" class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                    <option value="">-- NULL --</option>
                                    <option value="true">TRUE</option>
                                    <option value="false">FALSE</option>
                                </select>
                            <?php elseif ($udt === 'json' || $udt === 'jsonb' || $udt === 'text'): ?>
                                <textarea name="data[<?= htmlspecialchars($colName) ?>]" rows="3" placeholder="<?= $isAuto ? 'Leave empty for auto sequence' : 'Enter ' . $c['data_type'] . '...' ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
                            <?php else: ?>
                                <input type="text" name="data[<?= htmlspecialchars($colName) ?>]" placeholder="<?= $isAuto ? 'Leave empty for auto sequence' : ($c['column_default'] ? 'Default: ' . $c['column_default'] : 'Value...') ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
                <a href="/table/browse?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>" class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</a>
                <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-lg shadow-sm">
                    Insert Record
                </button>
            </div>
        </form>
    </div>

</div>

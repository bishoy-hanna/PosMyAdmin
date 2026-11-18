<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="layers" class="w-5 h-5 text-brand-500"></i>
                <span>Databases & Schemas</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Manage database instances and schema namespaces on this server.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="/database/backup" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-sky-600 hover:bg-sky-500 text-white shadow-sm transition">
                <i data-lucide="archive" class="w-4 h-4"></i>
                <span>Backup & Import</span>
            </a>
            <button onclick="document.getElementById('createDbModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-brand-600 hover:bg-brand-500 text-white shadow-sm transition">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>New Database</span>
            </button>
            <button onclick="document.getElementById('createSchemaModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-700 hover:bg-slate-600 text-white shadow-sm transition">
                <i data-lucide="folder-plus" class="w-4 h-4"></i>
                <span>New Schema</span>
            </button>
        </div>
    </div>

    <!-- Databases List -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">All Databases (<?= count($databases) ?>)</h2>
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
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php 
                    $userDb = (string)($currentDb ?? $user['database'] ?? '');
                    foreach ($databases as $d): 
                        $dName = (string)($d['datname'] ?? $d['name'] ?? '');
                        $dSize = (string)($d['size'] ?? $d['size_pretty'] ?? '');
                        $dCollate = (string)($d['datcollate'] ?? $d['collation'] ?? '');
                        $isCurrent = ($dName === $userDb);
                    ?>
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/50 transition <?= $isCurrent ? 'bg-brand-50/30 dark:bg-brand-950/20' : '' ?>">
                            <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                <i data-lucide="database" class="w-4 h-4 <?= $isCurrent ? 'text-brand-500' : 'text-slate-400' ?>"></i>
                                <span><?= htmlspecialchars($dName) ?></span>
                                <?php if ($isCurrent): ?>
                                    <span class="text-[10px] bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300 font-bold px-1.5 py-0.5 rounded">CURRENT</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300"><?= htmlspecialchars($d['owner'] ?? '') ?></td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 font-mono"><?= htmlspecialchars($d['encoding'] ?? 'UTF8') ?></td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 font-mono"><?= htmlspecialchars($dCollate) ?></td>
                            <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-200"><?= htmlspecialchars($dSize) ?></td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <?php if (!$isCurrent): ?>
                                    <form action="/database/switch" method="POST" class="inline">
                                        <?= PosMyAdmin\Auth\CSRF::field() ?>
                                        <input type="hidden" name="database" value="<?= htmlspecialchars($dName) ?>">
                                        <button type="submit" class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                            Switch
                                        </button>
                                    </form>
                                    <form action="/database/drop" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to drop database \'<?= htmlspecialchars($dName) ?>\'? This action is IRREVERSIBLE!');">
                                        <?= PosMyAdmin\Auth\CSRF::field() ?>
                                        <input type="hidden" name="name" value="<?= htmlspecialchars($dName) ?>">
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400">
                                            Drop
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Schemas in Current Database -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Schemas in '<?= htmlspecialchars((string)($currentDb ?? $user['database'] ?? '')) ?>' (<?= count($schemas) ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-750 text-slate-500 dark:text-slate-400 uppercase font-semibold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3">Schema Name</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($schemas as $s): ?>
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                <i data-lucide="folder" class="w-4 h-4 text-amber-500"></i>
                                <span><?= htmlspecialchars($s) ?></span>
                            </td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                                <?= ($s === 'public') ? 'Default Schema' : 'User Schema' ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <?php if ($s !== 'public'): ?>
                                    <form action="/schema/drop" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to drop schema \'<?= htmlspecialchars($s) ?>\' and all its tables?');">
                                        <?= PosMyAdmin\Auth\CSRF::field() ?>
                                        <input type="hidden" name="name" value="<?= htmlspecialchars($s) ?>">
                                        <input type="hidden" name="cascade" value="1">
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400">
                                            Drop Cascade
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 italic">Protected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Create Database -->
<div id="createDbModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-brand-500"></i>
                <span>Create New Database</span>
            </h3>
            <button onclick="document.getElementById('createDbModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="/database/create" method="POST" class="mt-4 space-y-4">
            <?= PosMyAdmin\Auth\CSRF::field() ?>
            <div>
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1" for="db_name">Database Name</label>
                <input type="text" id="db_name" name="name" required placeholder="my_new_database" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1" for="db_encoding">Encoding</label>
                <select id="db_encoding" name="encoding" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="UTF8" selected>UTF8 (Recommended)</option>
                    <option value="LATIN1">LATIN1</option>
                    <option value="SQL_ASCII">SQL_ASCII</option>
                </select>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('createDbModal').classList.add('hidden')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-brand-600 hover:bg-brand-500 text-white shadow-sm">Create Database</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Create Schema -->
<div id="createSchemaModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="folder-plus" class="w-4 h-4 text-amber-500"></i>
                <span>Create New Schema</span>
            </h3>
            <button onclick="document.getElementById('createSchemaModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="/schema/create" method="POST" class="mt-4 space-y-4">
            <?= PosMyAdmin\Auth\CSRF::field() ?>
            <div>
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1" for="schema_name">Schema Name</label>
                <input type="text" id="schema_name" name="name" required placeholder="analytics" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('createSchemaModal').classList.add('hidden')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-amber-600 hover:bg-amber-500 text-white shadow-sm">Create Schema</button>
            </div>
        </form>
    </div>
</div>

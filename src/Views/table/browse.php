<?php
// Helper to build URLs preserving existing filters, order, and pagination
$buildUrl = function(array $overrides = []) use ($schema, $table, $current_page, $per_page, $order_by, $order_dir, $filter_col, $filter_op, $filter_val): string {
    $params = [
        'schema' => $schema,
        'table' => $table,
        'page' => $overrides['page'] ?? $current_page,
        'per_page' => $overrides['per_page'] ?? $per_page,
    ];

    $ob = array_key_exists('order_by', $overrides) ? $overrides['order_by'] : $order_by;
    if (!empty($ob)) {
        $params['order_by'] = $ob;
        $params['order_dir'] = $overrides['order_dir'] ?? $order_dir ?? 'ASC';
    }

    $fc = array_key_exists('filter_col', $overrides) ? $overrides['filter_col'] : $filter_col;
    if (!empty($fc)) {
        $params['filter_col'] = $fc;
        $params['filter_op'] = $overrides['filter_op'] ?? $filter_op;
        $params['filter_val'] = $overrides['filter_val'] ?? $filter_val;
    }

    return '/table/browse?' . http_build_query($params);
};

// Map foreign keys by column name for instant relation lookup
$fkMap = [];
if (!empty($foreign_keys) && is_array($foreign_keys)) {
    foreach ($foreign_keys as $fk) {
        $fkMap[$fk['column_name']] = [
            'schema' => !empty($fk['foreign_table_schema']) ? $fk['foreign_table_schema'] : 'public',
            'table' => $fk['foreign_table_name'],
            'column' => $fk['foreign_column_name'],
            'constraint' => $fk['constraint_name'] ?? '',
        ];
    }
}
?>
<div class="space-y-4">

    <!-- Table Header & Sub-navigation Tabs -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-700">
            <div>
                <div class="flex items-center gap-2">
                    <i data-lucide="table-2" class="w-5 h-5 text-brand-500"></i>
                    <h1 class="text-lg font-bold text-slate-900 dark:text-white font-mono">
                        <span class="text-slate-400 font-normal"><?= htmlspecialchars($schema) ?>.</span><?= htmlspecialchars($table) ?>
                    </h1>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-1">
                    <span>Total Rows: <strong><?= number_format($total_rows) ?></strong></span>
                    <span>•</span>
                    <span>Page <strong><?= $current_page ?></strong> of <strong><?= max(1, $total_pages) ?></strong></span>
                </div>
            </div>

            <!-- Table Actions: Truncate & Drop -->
            <div class="flex items-center gap-2">
                <form action="/table/truncate" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to TRUNCATE \'<?= htmlspecialchars($table) ?>\'? All data will be deleted!');">
                    <?= PosMyAdmin\Auth\CSRF::field() ?>
                    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                    <input type="hidden" name="schema" value="<?= htmlspecialchars($schema) ?>">
                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800 transition">
                        <i data-lucide="scissors" class="w-3.5 h-3.5"></i>
                        <span>Truncate</span>
                    </button>
                </form>
                <form action="/table/drop" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to DROP table \'<?= htmlspecialchars($table) ?>\'? This action is IRREVERSIBLE!');">
                    <?= PosMyAdmin\Auth\CSRF::field() ?>
                    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                    <input type="hidden" name="schema" value="<?= htmlspecialchars($schema) ?>">
                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/40 hover:bg-red-100 dark:hover:bg-red-900/60 border border-red-200 dark:border-red-800 transition">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        <span>Drop</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="flex items-center gap-2 mt-3 pt-1 text-xs font-medium overflow-x-auto">
            <a href="/table/browse?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-500 text-white font-semibold">
                <i data-lucide="grid" class="w-3.5 h-3.5"></i>
                <span>Browse</span>
            </a>
            <a href="/table/structure?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
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
            <!-- Export Dropdown -->
            <div class="ml-auto flex items-center gap-1">
                <span class="text-slate-400 text-[11px] mr-1">Export:</span>
                <a href="/export/table?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>&format=sql" class="px-2 py-1 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded text-[11px] font-semibold text-slate-700 dark:text-slate-200">SQL</a>
                <a href="/export/table?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>&format=csv" class="px-2 py-1 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded text-[11px] font-semibold text-slate-700 dark:text-slate-200">CSV</a>
                <a href="/export/table?schema=<?= urlencode($schema) ?>&table=<?= urlencode($table) ?>&format=json" class="px-2 py-1 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded text-[11px] font-semibold text-slate-700 dark:text-slate-200">JSON</a>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar with Order By -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <form method="GET" action="/table/browse" class="flex flex-wrap items-center gap-2.5 flex-1">
            <input type="hidden" name="schema" value="<?= htmlspecialchars($schema) ?>">
            <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
            <input type="hidden" name="per_page" value="<?= (int)$per_page ?>">
            
            <!-- Filter Section -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-slate-400 font-semibold flex items-center gap-1">
                    <i data-lucide="filter" class="w-3.5 h-3.5 text-brand-500"></i> Filter:
                </span>

                <select name="filter_col" class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">-- All Columns --</option>
                    <?php foreach ($columns as $c): ?>
                        <option value="<?= htmlspecialchars($c['column_name']) ?>" <?= ($filter_col === $c['column_name']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['column_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="filter_op" class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2 py-1.5 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="=" <?= ($filter_op === '=') ? 'selected' : '' ?>>= (Equal)</option>
                    <option value="ILIKE" <?= ($filter_op === 'ILIKE' || empty($filter_op)) ? 'selected' : '' ?>>Contains (ILIKE)</option>
                    <option value="!=" <?= ($filter_op === '!=') ? 'selected' : '' ?>>!= (Not Equal)</option>
                    <option value=">" <?= ($filter_op === '>') ? 'selected' : '' ?>>&gt; (Greater)</option>
                    <option value="<" <?= ($filter_op === '<') ? 'selected' : '' ?>>&lt; (Less)</option>
                    <option value="IS NULL" <?= ($filter_op === 'IS NULL') ? 'selected' : '' ?>>IS NULL</option>
                    <option value="IS NOT NULL" <?= ($filter_op === 'IS NOT NULL') ? 'selected' : '' ?>>IS NOT NULL</option>
                </select>

                <input type="text" name="filter_val" value="<?= htmlspecialchars($filter_val ?? '') ?>" placeholder="Search value..." class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand-500 w-32 sm:w-40">
            </div>

            <!-- Divider -->
            <div class="h-5 w-px bg-slate-200 dark:bg-slate-700 hidden lg:block"></div>

            <!-- Order By Section -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-slate-400 font-semibold flex items-center gap-1">
                    <i data-lucide="arrow-up-down" class="w-3.5 h-3.5 text-sky-500"></i> Order By:
                </span>

                <select name="order_by" class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">-- Default Order --</option>
                    <?php foreach ($columns as $c): ?>
                        <option value="<?= htmlspecialchars($c['column_name']) ?>" <?= ($order_by === $c['column_name']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['column_name']) ?> <?= $c['is_primary_key'] ? '(PK)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="order_dir" class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2 py-1.5 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="ASC" <?= ($order_dir === 'ASC') ? 'selected' : '' ?>>ASC (A &rarr; Z / 0 &rarr; 9)</option>
                    <option value="DESC" <?= ($order_dir === 'DESC') ? 'selected' : '' ?>>DESC (Z &rarr; A / 9 &rarr; 0)</option>
                </select>
            </div>

            <!-- Actions: Submit & Reset -->
            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-lg shadow-sm transition">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span>Apply</span>
                </button>

                <?php if (!empty($filter_col) || !empty($order_by)): ?>
                    <a href="<?= $buildUrl(['filter_col' => null, 'filter_op' => null, 'filter_val' => null, 'order_by' => null, 'page' => 1]) ?>" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center gap-1">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Per Page Selector -->
        <div class="flex items-center gap-2">
            <span class="text-slate-400">Rows:</span>
            <?php foreach ([25, 50, 100] as $sz): ?>
                <a href="<?= $buildUrl(['per_page' => $sz, 'page' => 1]) ?>" class="px-2 py-1 rounded text-xs <?= ($per_page == $sz) ? 'bg-brand-500 text-white font-bold' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300' ?>">
                    <?= $sz ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Data Grid Table -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto max-h-[600px]">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-slate-50 dark:bg-slate-750 text-slate-600 dark:text-slate-300 uppercase font-semibold border-b border-slate-200 dark:border-slate-700 sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-3 w-16 text-center">Actions</th>
                        <?php foreach ($columns as $c): 
                            $colName = $c['column_name'];
                            $isSorted = ($order_by === $colName);
                            $nextDir = ($isSorted && $order_dir === 'ASC') ? 'DESC' : 'ASC';
                        ?>
                            <th class="px-4 py-3">
                                <a href="<?= $buildUrl(['order_by' => $colName, 'order_dir' => $nextDir, 'page' => 1]) ?>" class="flex items-center gap-1.5 hover:text-brand-600 dark:hover:text-brand-400">
                                    <span><?= htmlspecialchars($colName) ?></span>
                                    <?php if ($c['is_primary_key']): ?>
                                        <i data-lucide="key" class="w-3 h-3 text-amber-500" title="Primary Key"></i>
                                    <?php endif; ?>
                                    <?php if (isset($fkMap[$colName])): ?>
                                        <i data-lucide="link" class="w-3 h-3 text-purple-500" title="Foreign Key to <?= htmlspecialchars($fkMap[$colName]['table']) ?>.<?= htmlspecialchars($fkMap[$colName]['column']) ?>"></i>
                                    <?php endif; ?>
                                    <?php if ($isSorted): ?>
                                        <i data-lucide="<?= ($order_dir === 'ASC') ? 'arrow-up' : 'arrow-down' ?>" class="w-3.5 h-3.5 text-brand-600 dark:text-brand-400"></i>
                                    <?php endif; ?>
                                </a>
                                <span class="text-[10px] text-slate-400 font-normal lowercase block"><?= htmlspecialchars($c['udt_name']) ?></span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="<?= count($columns) + 1 ?>" class="px-6 py-12 text-center text-slate-400 italic">
                                No records found matching the criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $rowIndex => $row): 
                            // Extract primary key dictionary for this row
                            $pkVals = [];
                            foreach ($primary_keys as $pk) {
                                $pkVals[$pk] = $row[$pk] ?? null;
                            }
                            $pkJson = htmlspecialchars(json_encode($pkVals), ENT_QUOTES, 'UTF-8');
                            $rowJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-750/60 transition group">
                                <td class="px-3 py-2 text-center space-x-1 shrink-0">
                                    <!-- Edit Row Modal Trigger -->
                                    <button type="button" onclick="openEditModal(<?= $rowJson ?>, <?= $pkJson ?>)" title="Edit Row" class="p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 hover:text-brand-600">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <!-- Delete Row Form -->
                                    <form action="/table/delete" method="POST" class="inline" onsubmit="return confirm('Delete this row?');">
                                        <?= PosMyAdmin\Auth\CSRF::field() ?>
                                        <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                        <input type="hidden" name="schema" value="<?= htmlspecialchars($schema) ?>">
                                        <?php foreach ($pkVals as $pkKey => $pkVal): ?>
                                            <input type="hidden" name="primary_keys[<?= htmlspecialchars($pkKey) ?>]" value="<?= htmlspecialchars((string)$pkVal) ?>">
                                        <?php endforeach; ?>
                                        <button type="submit" title="Delete Row" class="p-1 rounded hover:bg-red-100 dark:hover:bg-red-950/60 text-slate-500 hover:text-red-600">
                                            <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </td>

                                <?php foreach ($columns as $c): 
                                    $colName = $c['column_name'];
                                    $val = $row[$colName] ?? null;
                                ?>
                                    <td class="px-4 py-2 text-slate-700 dark:text-slate-300 font-mono text-[11px] max-w-xs truncate" title="<?= htmlspecialchars((string)$val) ?>">
                                        <?php if ($val === null): ?>
                                            <span class="text-slate-400 italic">NULL</span>
                                        <?php elseif (is_bool($val)): ?>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold <?= $val ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300' ?>">
                                                <?= $val ? 'TRUE' : 'FALSE' ?>
                                            </span>
                                        <?php elseif (isset($fkMap[$colName]) && (string)$val !== ''): 
                                            $fkTarget = $fkMap[$colName];
                                            $fkUrl = '/table/browse?' . http_build_query([
                                                'schema' => $fkTarget['schema'],
                                                'table' => $fkTarget['table'],
                                                'filter_col' => $fkTarget['column'],
                                                'filter_op' => '=',
                                                'filter_val' => (string)$val,
                                            ]);
                                        ?>
                                            <a href="<?= htmlspecialchars($fkUrl) ?>" title="Go to <?= htmlspecialchars($fkTarget['table']) ?> (<?= htmlspecialchars($fkTarget['column']) ?> = <?= htmlspecialchars((string)$val) ?>)" class="inline-flex items-center gap-1 font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-800 dark:hover:text-brand-300 hover:underline bg-brand-50/70 dark:bg-brand-950/60 px-1.5 py-0.5 rounded border border-brand-200 dark:border-brand-800 transition">
                                                <span><?= htmlspecialchars((string)$val) ?></span>
                                                <i data-lucide="external-link" class="w-3 h-3 text-brand-500 shrink-0"></i>
                                            </a>
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

        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
            <div class="p-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs">
                <div class="text-slate-500 dark:text-slate-400">
                    Showing <?= min($total_rows, ($current_page - 1) * $per_page + 1) ?> to <?= min($total_rows, $current_page * $per_page) ?> of <?= number_format($total_rows) ?> rows
                </div>
                <div class="flex items-center gap-1">
                    <?php if ($current_page > 1): ?>
                        <a href="<?= $buildUrl(['page' => $current_page - 1]) ?>" class="px-2.5 py-1.5 rounded border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            Previous
                        </a>
                    <?php endif; ?>

                    <span class="px-3 py-1.5 font-bold text-slate-800 dark:text-white">
                        Page <?= $current_page ?> / <?= $total_pages ?>
                    </span>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="<?= $buildUrl(['page' => $current_page + 1]) ?>" class="px-2.5 py-1.5 rounded border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Row Edit Modal -->
<div id="editRowModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-2xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="edit" class="w-4 h-4 text-brand-500"></i>
                <span>Edit Row</span>
            </h3>
            <button onclick="document.getElementById('editRowModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="editRowForm" action="/table/update" method="POST" class="p-6 overflow-y-auto space-y-4 text-xs">
            <?= PosMyAdmin\Auth\CSRF::field() ?>
            <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
            <input type="hidden" name="schema" value="<?= htmlspecialchars($schema) ?>">
            
            <div id="modalPrimaryKeysContainer"></div>
            <div id="modalFieldsContainer" class="space-y-3"></div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="document.getElementById('editRowModal').classList.add('hidden')" class="px-3.5 py-2 text-xs font-medium rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-brand-600 hover:bg-brand-500 text-white shadow-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(rowData, pkData) {
        const modal = document.getElementById('editRowModal');
        const pkContainer = document.getElementById('modalPrimaryKeysContainer');
        const fieldsContainer = document.getElementById('modalFieldsContainer');

        pkContainer.innerHTML = '';
        fieldsContainer.innerHTML = '';

        // Fill primary keys
        for (const [key, val] of Object.entries(pkData)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `primary_keys[${key}]`;
            input.value = val !== null ? val : '';
            pkContainer.appendChild(input);
        }

        // Fill column input fields
        for (const [col, val] of Object.entries(rowData)) {
            const wrapper = document.createElement('div');
            wrapper.className = 'grid grid-cols-4 gap-2 items-center';

            const label = document.createElement('label');
            label.className = 'col-span-1 font-semibold text-slate-700 dark:text-slate-300 font-mono truncate';
            label.innerText = col;
            label.title = col;

            const inputWrap = document.createElement('div');
            inputWrap.className = 'col-span-3';

            let input;
            if (val !== null && typeof val === 'string' && val.length > 80) {
                input = document.createElement('textarea');
                input.rows = 3;
                input.className = 'w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg p-2 font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none';
            } else {
                input = document.createElement('input');
                input.type = 'text';
                input.className = 'w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-1.5 font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none';
            }

            input.name = `data[${col}]`;
            input.value = val !== null ? val : '';

            inputWrap.appendChild(input);
            wrapper.appendChild(label);
            wrapper.appendChild(inputWrap);
            fieldsContainer.appendChild(wrapper);
        }

        modal.classList.remove('hidden');
        lucide.createIcons();
    }
</script>

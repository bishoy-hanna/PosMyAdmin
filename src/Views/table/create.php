<div class="max-w-4xl space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-5 h-5 text-brand-500"></i>
                <span>Create New Table</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Define table name, schema, and columns.</p>
        </div>
    </div>

    <!-- Create Table Form -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 shadow-sm">
        <form action="/table/create" method="POST" class="space-y-6 text-xs">
            <?= PosMyAdmin\Auth\CSRF::field() ?>

            <!-- Table name & Schema -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1" for="table_name">Table Name</label>
                    <input type="text" id="table_name" name="table" required placeholder="users, orders, logs..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1" for="schema_name">Schema</label>
                    <input type="text" id="schema_name" name="schema" required value="<?= htmlspecialchars($schema ?? 'public') ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <!-- Columns Definition -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-800 dark:text-slate-200">Table Columns</span>
                    <button type="button" onclick="addColumnRow()" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded bg-brand-50 text-brand-700 hover:bg-brand-100 dark:bg-brand-950/60 dark:text-brand-300 transition">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Column
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-750 text-slate-500 dark:text-slate-400 uppercase font-semibold">
                            <tr>
                                <th class="px-3 py-2">Column Name</th>
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2 text-center">Nullable</th>
                                <th class="px-3 py-2">Default Value</th>
                                <th class="px-3 py-2 text-center">PK</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="columnsContainer" class="divide-y divide-slate-100 dark:divide-slate-700">
                            <!-- Default Row 1: ID -->
                            <tr class="column-row">
                                <td class="px-3 py-2">
                                    <input type="text" name="columns[0][name]" value="id" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2.5 py-1.5 font-mono focus:outline-none focus:ring-1 focus:ring-brand-500">
                                </td>
                                <td class="px-3 py-2">
                                    <select name="columns[0][type]" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2 py-1.5 font-mono focus:outline-none">
                                        <option value="BIGSERIAL" selected>BIGSERIAL (Auto-increment PK)</option>
                                        <option value="SERIAL">SERIAL</option>
                                        <option value="INT">INTEGER</option>
                                        <option value="BIGINT">BIGINT</option>
                                        <option value="VARCHAR(255)">VARCHAR(255)</option>
                                        <option value="TEXT">TEXT</option>
                                        <option value="BOOLEAN">BOOLEAN</option>
                                        <option value="JSONB">JSONB</option>
                                        <option value="TIMESTAMP WITH TIME ZONE">TIMESTAMPTZ</option>
                                        <option value="UUID">UUID</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox" name="columns[0][nullable]" value="1" class="rounded text-brand-600 focus:ring-brand-500">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" name="columns[0][default]" placeholder="Optional" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2.5 py-1.5 font-mono focus:outline-none">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox" name="columns[0][primary_key]" value="1" checked class="rounded text-brand-600 focus:ring-brand-500">
                                </td>
                                <td class="px-3 py-2 text-center"></td>
                            </tr>
                            <!-- Default Row 2: created_at -->
                            <tr class="column-row">
                                <td class="px-3 py-2">
                                    <input type="text" name="columns[1][name]" value="created_at" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2.5 py-1.5 font-mono focus:outline-none focus:ring-1 focus:ring-brand-500">
                                </td>
                                <td class="px-3 py-2">
                                    <select name="columns[1][type]" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2 py-1.5 font-mono focus:outline-none">
                                        <option value="TIMESTAMPTZ" selected>TIMESTAMPTZ</option>
                                        <option value="VARCHAR(255)">VARCHAR(255)</option>
                                        <option value="TEXT">TEXT</option>
                                        <option value="INTEGER">INTEGER</option>
                                        <option value="BIGINT">BIGINT</option>
                                        <option value="BOOLEAN">BOOLEAN</option>
                                        <option value="JSONB">JSONB</option>
                                        <option value="UUID">UUID</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox" name="columns[1][nullable]" value="1" class="rounded text-brand-600 focus:ring-brand-500">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" name="columns[1][default]" value="CURRENT_TIMESTAMP" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2.5 py-1.5 font-mono focus:outline-none">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox" name="columns[1][primary_key]" value="1" class="rounded text-brand-600 focus:ring-brand-500">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <button type="button" onclick="this.closest('tr').remove()" class="text-slate-400 hover:text-red-500">
                                        <i data-lucide="trash" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
                <a href="/" class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</a>
                <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-lg shadow-sm">
                    Create Table
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    let colIndex = 2;
    function addColumnRow() {
        const container = document.getElementById('columnsContainer');
        const tr = document.createElement('tr');
        tr.className = 'column-row';
        tr.innerHTML = `
            <td class="px-3 py-2">
                <input type="text" name="columns[${colIndex}][name]" placeholder="column_name" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2.5 py-1.5 font-mono focus:outline-none focus:ring-1 focus:ring-brand-500">
            </td>
            <td class="px-3 py-2">
                <select name="columns[${colIndex}][type]" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2 py-1.5 font-mono focus:outline-none">
                    <option value="VARCHAR(255)" selected>VARCHAR(255)</option>
                    <option value="TEXT">TEXT</option>
                    <option value="INTEGER">INTEGER</option>
                    <option value="BIGINT">BIGINT</option>
                    <option value="BOOLEAN">BOOLEAN</option>
                    <option value="JSONB">JSONB</option>
                    <option value="TIMESTAMPTZ">TIMESTAMPTZ</option>
                    <option value="UUID">UUID</option>
                    <option value="NUMERIC(10,2)">NUMERIC(10,2)</option>
                    <option value="BYTEA">BYTEA</option>
                </select>
            </td>
            <td class="px-3 py-2 text-center">
                <input type="checkbox" name="columns[${colIndex}][nullable]" value="1" checked class="rounded text-brand-600 focus:ring-brand-500">
            </td>
            <td class="px-3 py-2">
                <input type="text" name="columns[${colIndex}][default]" placeholder="Default value" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2.5 py-1.5 font-mono focus:outline-none">
            </td>
            <td class="px-3 py-2 text-center">
                <input type="checkbox" name="columns[${colIndex}][primary_key]" value="1" class="rounded text-brand-600 focus:ring-brand-500">
            </td>
            <td class="px-3 py-2 text-center">
                <button type="button" onclick="this.closest('tr').remove()" class="text-slate-400 hover:text-red-500">
                    <i data-lucide="trash" class="w-4 h-4"></i>
                </button>
            </td>
        `;
        container.appendChild(tr);
        colIndex++;
        lucide.createIcons();
    }
</script>

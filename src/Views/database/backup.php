<div class="max-w-4xl space-y-6">

    <!-- Header -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <i data-lucide="archive" class="w-5 h-5 text-brand-500"></i>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Database Backup & Import</h1>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Active Database: <strong class="text-slate-800 dark:text-slate-200 font-mono"><?= htmlspecialchars($currentDb) ?></strong>
            </p>
        </div>

        <!-- Mode Tabs -->
        <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-700 rounded-lg text-xs font-semibold">
            <a href="/database/backup?tab=export" class="flex items-center gap-1.5 px-3 py-1.5 rounded-md transition <?= ($activeTab === 'export') ? 'bg-white dark:bg-slate-800 text-brand-600 dark:text-brand-400 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white' ?>">
                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                <span>Export Database</span>
            </a>
            <a href="/database/backup?tab=import" class="flex items-center gap-1.5 px-3 py-1.5 rounded-md transition <?= ($activeTab === 'import') ? 'bg-white dark:bg-slate-800 text-brand-600 dark:text-brand-400 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white' ?>">
                <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                <span>Import SQL</span>
            </a>
        </div>
    </div>

    <?php if ($activeTab === 'export'): ?>
        <!-- ================= EXPORT TAB ================= -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 shadow-sm space-y-6">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="download-cloud" class="w-4 h-4 text-brand-500"></i>
                    <span>Export Full Database (SQL Dump)</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Generate a complete, standard PostgreSQL `.sql` script containing all schemas, tables, views, and data.</p>
            </div>

            <form action="/database/export" method="POST" class="space-y-5">
                <?= PosMyAdmin\Auth\CSRF::field() ?>

                <!-- Export Options Checkboxes -->
                <div class="p-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl space-y-3 text-xs">
                    <span class="font-bold text-slate-800 dark:text-slate-200 block mb-1">Export Options</span>

                    <label class="flex items-center gap-2.5 cursor-pointer text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="include_structure" value="1" checked class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
                        <div>
                            <strong class="block font-semibold">Include Structure (DDL)</strong>
                            <span class="text-slate-400 text-[11px]">Generate <code>CREATE SCHEMA</code> and <code>CREATE TABLE</code> definitions.</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 cursor-pointer text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="include_data" value="1" checked class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
                        <div>
                            <strong class="block font-semibold">Include Data (INSERTs)</strong>
                            <span class="text-slate-400 text-[11px]">Generate multi-row <code>INSERT INTO</code> statements for all existing records.</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 cursor-pointer text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="drop_tables" value="1" checked class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
                        <div>
                            <strong class="block font-semibold">Add DROP TABLE Statements</strong>
                            <span class="text-slate-400 text-[11px]">Prepends <code>DROP TABLE IF EXISTS ... CASCADE</code> before each table creation.</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 cursor-pointer text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="use_transaction" value="1" checked class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
                        <div>
                            <strong class="block font-semibold">Wrap in Transaction (BEGIN / COMMIT)</strong>
                            <span class="text-slate-400 text-[11px]">Ensures atomic execution when importing into another database.</span>
                        </div>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="pt-2 flex items-center justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-lg text-xs shadow-md transition">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Download Full SQL Dump (<?= htmlspecialchars($currentDb) ?>)</span>
                    </button>
                </div>
            </form>
        </div>

    <?php else: ?>
        <!-- ================= IMPORT TAB ================= -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 shadow-sm space-y-6">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="upload-cloud" class="w-4 h-4 text-emerald-500"></i>
                    <span>Import SQL Script into Database</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Execute an exported SQL file or raw PostgreSQL script into database <code class="font-mono text-brand-600 dark:text-brand-400"><?= htmlspecialchars($currentDb) ?></code>.
                </p>
            </div>

            <form action="/database/import" method="POST" enctype="multipart/form-data" class="space-y-5 text-xs">
                <?= PosMyAdmin\Auth\CSRF::field() ?>

                <!-- Option A: File Upload -->
                <div>
                    <label class="block font-semibold text-slate-800 dark:text-slate-200 mb-2">Option 1: Upload .sql File</label>
                    <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-brand-500 dark:hover:border-brand-400 rounded-xl p-6 text-center bg-slate-50 dark:bg-slate-900 transition">
                        <i data-lucide="file-up" class="w-8 h-8 text-slate-400 mx-auto mb-2"></i>
                        <div class="text-xs text-slate-600 dark:text-slate-300 mb-1">
                            <label for="sql_file" class="cursor-pointer font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                                Choose a file
                            </label>
                            <span>or drag and drop here</span>
                        </div>
                        <input id="sql_file" name="sql_file" type="file" accept=".sql,.txt" class="hidden" onchange="updateFileName(this)">
                        <div id="selectedFileName" class="text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-1">Accepted: .sql (Up to server upload limit)</div>
                    </div>
                </div>

                <div class="flex items-center my-2">
                    <div class="flex-1 border-t border-slate-200 dark:border-slate-700"></div>
                    <span class="px-3 text-xs text-slate-400 uppercase font-semibold">OR</span>
                    <div class="flex-1 border-t border-slate-200 dark:border-slate-700"></div>
                </div>

                <!-- Option B: Direct Raw SQL Paste -->
                <div>
                    <label class="block font-semibold text-slate-800 dark:text-slate-200 mb-1" for="raw_sql">Option 2: Paste Raw SQL Statements</label>
                    <textarea id="raw_sql" name="raw_sql" rows="6" placeholder="CREATE TABLE ...; INSERT INTO ...;" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg p-3 font-mono text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
                </div>

                <!-- Transaction Safety Option -->
                <div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <label class="flex items-center gap-2 cursor-pointer text-amber-900 dark:text-amber-200">
                        <input type="checkbox" name="use_transaction" value="1" checked class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500">
                        <div>
                            <strong class="font-semibold block">Run within Transaction (Recommended)</strong>
                            <span class="text-[11px] text-amber-800 dark:text-amber-300">If any statement fails, all changes will be safely rolled back to prevent corrupted schema state.</span>
                        </div>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="pt-2 flex items-center justify-end">
                    <button type="submit" onclick="return confirm('Execute this SQL script against database \'<?= htmlspecialchars($currentDb) ?>\'?');" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-lg text-xs shadow-md transition">
                        <i data-lucide="play" class="w-4 h-4"></i>
                        <span>Execute Import</span>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

</div>

<script>
    function updateFileName(input) {
        const display = document.getElementById('selectedFileName');
        if (input.files && input.files.length > 0) {
            display.innerText = 'Selected: ' + input.files[0].name + ' (' + (input.files[0].size / 1024).toFixed(1) + ' KB)';
            display.className = 'text-[11px] text-emerald-600 dark:text-emerald-400 font-bold font-mono mt-1';
        }
    }
</script>

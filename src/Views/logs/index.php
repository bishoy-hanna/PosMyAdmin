<div class="space-y-6">

    <!-- Header & Action Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="<?= ($type === 'auth') ? 'shield' : 'file-text' ?>" class="w-5 h-5 <?= ($type === 'auth') ? 'text-amber-500' : 'text-red-500' ?>"></i>
                <span><?= htmlspecialchars($pageTitle) ?></span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Active log: <code class="font-mono text-slate-700 dark:text-slate-300"><?= htmlspecialchars($logPath) ?></code>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.location.reload()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                <span>Refresh</span>
            </button>
            <a href="/logs/download?type=<?= urlencode($type) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-sky-600 hover:bg-sky-500 text-white shadow-sm transition">
                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                <span>Download Log</span>
            </a>
            <form action="/logs/clear" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to clear this log file?');">
                <?= PosMyAdmin\Auth\CSRF::field() ?>
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-600 hover:bg-red-500 text-white shadow-sm transition">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Clear Log</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Navigation Tabs: error.log vs auth.log -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-3 text-xs font-medium">
        <a href="/logs?type=error" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg transition <?= ($type !== 'auth') ? 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/30 font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500"></i>
            <span>Application Error Log (error.log)</span>
            <span class="text-[10px] bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-1.5 py-0.2 rounded-full font-mono">
                <?= number_format($errorSize / 1024, 1) ?> KB
            </span>
        </a>

        <a href="/logs?type=auth" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg transition <?= ($type === 'auth') ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30 font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            <i data-lucide="shield-alert" class="w-4 h-4 text-amber-500"></i>
            <span>Authentication & Rate Limit (auth.log)</span>
            <span class="text-[10px] bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-1.5 py-0.2 rounded-full font-mono">
                <?= number_format($authSize / 1024, 1) ?> KB
            </span>
        </a>
    </div>

    <!-- Log File Info Pill -->
    <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
        <span>File Size: <strong class="text-slate-800 dark:text-slate-200 font-mono"><?= number_format($fileSize / 1024, 2) ?> KB</strong></span>
        <span>•</span>
        <span>Auto-rotates at 10 MB limit</span>
    </div>

    <!-- Log Viewer Console -->
    <div class="bg-slate-900 border border-slate-700 rounded-xl overflow-hidden shadow-lg">
        <div class="p-3 bg-slate-800 border-b border-slate-700 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>
                <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
                <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                <span class="ml-2 font-mono text-slate-300 font-semibold text-[11px]"><?= ($type === 'auth') ? 'auth.log' : 'error.log' ?></span>
            </div>
            <span class="text-slate-400 text-[11px]">Latest events appended at bottom</span>
        </div>

        <div class="p-4 overflow-x-auto max-h-[650px] font-mono text-[11px] leading-relaxed select-text">
            <?php if (empty(trim($content))): ?>
                <div class="py-12 text-center text-slate-500 italic">
                    Log file is currently empty. No events recorded yet! 🎉
                </div>
            <?php else: ?>
                <pre class="text-slate-300 whitespace-pre-wrap"><?= htmlspecialchars($content) ?></pre>
            <?php endif; ?>
        </div>
    </div>

</div>

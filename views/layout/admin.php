<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    /** @var array<string, mixed> $shop */
    $shop = $shop ?? [];
    $storeName = (string) ($shop['store_name'] ?? 'Astral Shop');
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    ?>
    <title><?= htmlspecialchars($title ?? 'Admin', ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/shop.css">
    <?php $primary = (string) ($shop['primary'] ?? '#0f6b4c'); ?>
    <style>
        :root {
            --accent: <?= htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') ?>;
            --shop-primary: <?= htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') ?>;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="h-full font-sans antialiased text-slate-800">
<div class="min-h-full flex">

    <aside class="hidden md:flex md:w-60 md:flex-col bg-slate-900 text-slate-300">
        <div class="h-14 flex flex-col justify-center px-4 border-b border-slate-800">
            <span class="text-white font-semibold tracking-tight truncate"><?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="text-[10px] uppercase tracking-wider text-indigo-300/90">Back-office</span>
        </div>
        <nav class="flex-1 p-3 space-y-1 text-sm">
            <?php
            $navItems = $adminNav ?? [];
            foreach ($navItems as $item):
                $href  = (string) ($item['href'] ?? '#');
                $label = (string) ($item['label'] ?? '');
                $active = $href !== '/' && str_starts_with($currentPath, $href);
                $linkClass = $active
                    ? 'block rounded-lg px-3 py-2 bg-slate-800 text-white font-medium'
                    : 'block rounded-lg px-3 py-2 hover:bg-slate-800 hover:text-white transition';
            ?>
                <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                   class="<?= $linkClass ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach ?>
        </nav>
        <div class="p-3 border-t border-slate-800 space-y-2">
            <?php if (isset($auth) && $auth->check()): ?>
                <p class="text-xs text-slate-500 truncate px-1"><?= htmlspecialchars($auth->name(), ENT_QUOTES, 'UTF-8') ?></p>
                <form method="POST" action="/logout" class="m-0">
                    <?= $csrf->field() ?>
                    <button type="submit"
                            class="w-full text-left rounded-lg px-3 py-2 text-sm text-slate-400 hover:bg-slate-800 hover:text-white transition">
                        Déconnexion
                    </button>
                </form>
            <?php endif ?>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-14 bg-white border-b border-slate-200 flex items-center justify-between gap-3 px-4 sm:px-6">
            <h1 class="text-sm font-semibold text-slate-700 truncate">
                <?= htmlspecialchars($title ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <a href="/" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 shrink-0">Voir la boutique</a>
        </header>

        <!-- Nav mobile -->
        <nav class="md:hidden flex gap-2 overflow-x-auto px-3 py-2 bg-slate-900 text-xs">
            <?php foreach (($adminNav ?? []) as $item): ?>
                <a href="<?= htmlspecialchars((string) ($item['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
                   class="shrink-0 rounded-full px-3 py-1.5 bg-slate-800 text-slate-200">
                    <?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach ?>
        </nav>

        <main class="flex-1 p-4 sm:p-6">
            <?php if (isset($viewEngine)): ?>
                <?= $viewEngine->partial('partials/flash') ?>
            <?php endif ?>
            <?= $content ?? '' ?>
        </main>
    </div>

</div>
</body>
</html>

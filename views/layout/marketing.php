<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Astral', ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['Fraunces', 'Georgia', 'serif'],
                        sans: ['Source Sans 3', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        ink: { DEFAULT: '#1a2332', muted: '#5c6b7a' },
                        sand: { DEFAULT: '#f3efe6', deep: '#e4dcc8' },
                        pine: { DEFAULT: '#2f5d50', light: '#3d7a68' },
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-full font-sans antialiased text-ink bg-sand">

    <header class="border-b border-sand-deep/80 bg-sand/80 backdrop-blur sticky top-0 z-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="/" class="font-display text-xl font-bold text-ink tracking-tight">
                <?= htmlspecialchars($brand ?? 'Astral', ENT_QUOTES, 'UTF-8') ?>
            </a>
            <nav class="flex items-center gap-6 text-sm text-ink-muted">
                <?php
                $links = $marketingNav ?? [
                    ['href' => '/', 'label' => 'Accueil'],
                    ['href' => '/docs', 'label' => 'Docs'],
                ];
                foreach ($links as $link):
                ?>
                    <a href="<?= htmlspecialchars((string) $link['href'], ENT_QUOTES, 'UTF-8') ?>"
                       class="hover:text-pine transition">
                        <?= htmlspecialchars((string) $link['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach ?>
            </nav>
        </div>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer class="mt-24 border-t border-sand-deep py-10 text-center text-sm text-ink-muted">
        <?= htmlspecialchars($footer ?? 'Propulsé par Astral MVC · astral-template', ENT_QUOTES, 'UTF-8') ?>
    </footer>

</body>
</html>

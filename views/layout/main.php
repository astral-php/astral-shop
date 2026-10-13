<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    /** @var array<string, mixed> $shop */
    $shop = $shop ?? [];
    $storeName = (string) ($shop['store_name'] ?? 'Astral Shop');
    $primary   = (string) ($shop['primary'] ?? '#4f46e5');
    $isAdmin   = $auth->check() && $auth->is(\Core\Auth\Role::ADMIN);
    $cartBump  = $session->hasFlash('cart_bump');
    if ($cartBump) {
        $session->getFlash('cart_bump');
    }
    ?>
    <title><?= htmlspecialchars($title ?? $storeName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/shop.css">
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --paper: #f8fafc;
            --panel: #ffffff;
            --line: #e2e8f0;
            --accent: <?= htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') ?>;
            --accent-soft: #eef2ff;
            --shop-primary: <?= htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') ?>;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Plus Jakarta Sans", system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1100px 480px at 8% -12%, #e0e7ff 0%, transparent 55%),
                radial-gradient(900px 420px at 100% 0%, #eef2ff 0%, transparent 50%),
                var(--paper);
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 1.25rem 1.25rem 3rem; }
        header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; padding: 0.65rem 0 1.35rem; border-bottom: 1px solid var(--line);
        }
        .brand {
            font-weight: 700; font-size: 1.2rem; color: var(--ink); text-decoration: none;
            letter-spacing: -0.02em;
        }
        .brand:hover { text-decoration: none; color: var(--accent); }
        nav { display: flex; flex-wrap: wrap; gap: 0.85rem; align-items: center; font-size: 0.9rem; }
        nav a { color: var(--muted); text-decoration: none; }
        nav a:hover { color: var(--accent); }
        nav a.btn,
        nav a.btn:hover { text-decoration: none; }
        nav a.btn-nav-account { color: var(--accent); }
        nav a.btn-nav-account:hover { color: #3730a3; }
        .btn {
            display: inline-block; border-radius: 0.55rem; padding: 0.55rem 1rem;
            font-weight: 600; font-size: 0.9rem; border: 1px solid transparent;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { filter: brightness(1.05); text-decoration: none; color: #fff; }
        .btn-ghost { border-color: var(--line); color: var(--ink); background: var(--panel); }
        .btn-ghost:hover { border-color: var(--accent); color: var(--accent); text-decoration: none; }
        .btn-nav-account {
            border-color: #c7d2fe;
            background: var(--accent-soft);
            color: var(--accent);
            padding: 0.4rem 0.85rem;
            font-size: 0.8rem;
        }
        .btn-nav-account:hover {
            border-color: var(--accent);
            background: #e0e7ff;
            color: #3730a3;
        }
        .nav-user {
            display: inline-block;
            border-radius: 0.55rem;
            padding: 0.4rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid var(--line);
            background: var(--panel);
            color: var(--ink);
            line-height: 1.2;
        }
        main { padding-top: 1.75rem; }
        .flash-ok, .flash-err {
            border-radius: 0.75rem; padding: 0.75rem 1rem; margin-bottom: 1rem; font-size: 0.9rem;
        }
        .flash-ok { background: var(--accent-soft); color: #3730a3; border: 1px solid #c7d2fe; }
        .flash-err { background: #fdeceb; color: #8a1f1a; border: 1px solid #f0c2be; }
        footer {
            margin-top: 3rem; padding-top: 1.25rem; border-top: 1px solid var(--line);
            font-size: 0.8rem; color: var(--muted);
        }
        .input:focus {
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.18);
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <?php
            $cartBag = $session->get('cart', ['items' => []]);
            $cartCount = 0;
            if (is_array($cartBag) && isset($cartBag['items']) && is_array($cartBag['items'])) {
                foreach ($cartBag['items'] as $cartRow) {
                    if (is_array($cartRow)) {
                        $cartCount += (int) ($cartRow['qty'] ?? 0);
                    }
                }
            }
            ?>
            <a class="brand" href="/"><?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?></a>

            <nav>
                <a href="/boutique">Boutique</a>
                <?php if ($auth->check()): ?>
                    <span class="nav-user"><?= htmlspecialchars($auth->name(), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($isAdmin): ?>
                        <a class="btn btn-ghost" href="/admin/orders" style="padding:0.4rem 0.85rem;font-size:0.8rem">Espace admin</a>
                    <?php else: ?>
                        <a class="btn btn-nav-account" href="/orders">Commandes</a>
                        <a class="btn btn-nav-account" href="/profile">Profil</a>
                    <?php endif ?>
                    <form method="POST" action="/logout" style="display:inline;margin:0">
                        <?= $csrf->field() ?>
                        <button type="submit" class="btn btn-ghost" style="cursor:pointer;font:inherit">Déconnexion</button>
                    </form>
                <?php else: ?>
                    <a href="/login">Connexion</a>
                    <a class="btn btn-primary" href="/register">Inscription</a>
                <?php endif ?>
                <?php if (!$isAdmin): ?>
                    <a class="cart-link cart-link--prominent<?= $cartBump ? ' cart-link--bump' : '' ?><?= $cartCount > 0 ? ' is-filled' : '' ?>"
                       href="/panier"
                       aria-label="Panier<?= $cartCount > 0 ? ', ' . $cartCount . ' article(s)' : '' ?>">
                        <span class="cart-link__box" aria-hidden="true">
                            <svg class="cart-link__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                            </svg>
                            <?php if ($cartCount > 0): ?>
                                <span class="cart-link__badge">
                                    <img class="cart-link__badge-gif" src="/img/cart-badge.svg" alt="" width="22" height="22">
                                    <span class="cart-link__badge-num"><?= (int) $cartCount ?></span>
                                </span>
                            <?php endif ?>
                        </span>
                        <span class="cart-link__label">Panier</span>
                    </a>
                <?php endif ?>
            </nav>
        </header>

        <main>
            <?php if ($session->hasFlash('success')): ?>
                <div class="flash-ok"><?= htmlspecialchars((string) $session->getFlash('success'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif ?>
            <?php if ($session->hasFlash('error')): ?>
                <div class="flash-err"><?= htmlspecialchars((string) $session->getFlash('error'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif ?>

            <?= $content ?? '' ?>
        </main>

        <footer>
            <?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?> ·
            <a href="https://github.com/astral-php/astral-core" target="_blank" rel="noopener">astral-core</a>
        </footer>
    </div>
</body>
</html>

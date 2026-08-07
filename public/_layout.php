<?php

declare(strict_types=1);

use BayarcashDemo\Config;

function layout_head(string $title, Config $config, string $current = ''): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#fbfaf7">
    <title><?= e($title) ?> · Bayarcash PHP Demo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>

<div class="ambient" aria-hidden="true"><i></i><i></i><i></i></div>

<div class="shell">

    <header class="masthead">
        <h1 class="wordmark">
            <img src="assets/img/bayarcash-wordmark.png"
                 srcset="assets/img/bayarcash-wordmark.png 1x, assets/img/bayarcash-wordmark@2x.png 2x"
                 alt="Bayarcash" width="363" height="72">
        </h1>

        <p class="tagline"><b>PHP demo</b> — payment integration in vanilla PHP</p>
    </header>

    <nav class="nav">
        <?php
        $links = [
            'index.php'       => 'Checkout',
            'orders.php'      => 'Transactions',
            'api-console.php' => 'API console',
        ];
        foreach ($links as $href => $label): ?>
            <a href="<?= e($href) ?>" <?= $current === $href ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <main>
    <?php
}

/** The environment badge, shown in section headers rather than the masthead. */
function env_badge(Config $config): void
{
    $env = $config->environment();
    echo '<span class="env" data-env="' . e($env) . '">' . e($env) . '</span>';
}

function layout_foot(Config $config): void
{
    ?>
    </main>

    <footer class="site-foot">
        <div class="foot-links">
            <a href="https://github.com/bayarcash/php-demo" target="_blank" rel="noopener">
                <b>GitHub repo</b>
                <span>This demo, source and all</span>
            </a>
            <a href="https://github.com/bayarcash/php-sdk" target="_blank" rel="noopener">
                <b>PHP SDK</b>
                <span>bayarcash/php-sdk on GitHub</span>
            </a>
            <a href="https://api.webimpian.support/bayarcash" target="_blank" rel="noopener">
                <b>Bayarcash API</b>
                <span>Endpoint and field reference</span>
            </a>
            <a href="https://docs.bayarcash.com" target="_blank" rel="noopener">
                <b>Platform docs</b>
                <span>Guides and onboarding</span>
            </a>
        </div>

        <div class="foot-meta">
            <span>PHP <?= e(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION) ?></span>
            <span class="sep">·</span>
            <span>bayarcash/php-sdk <?= e(sdk_version()) ?></span>
            <span class="sep">·</span>
            <span>API <?= e($config->apiVersion()) ?></span>
        </div>
    </footer>
</div>
</body>
</html>
    <?php
}

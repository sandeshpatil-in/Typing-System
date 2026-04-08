<?php
require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/xml; charset=utf-8');

$base = rtrim(BASE_URL, '/') . '/';

$pages = [
    ['loc' => $base,                       'path' => __DIR__ . '/index.php'],
    ['loc' => $base . 'about.php',         'path' => __DIR__ . '/about.php'],
    ['loc' => $base . 'contact.php',       'path' => __DIR__ . '/contact.php'],
    ['loc' => $base . 'typing-preference.php', 'path' => __DIR__ . '/typing-preference.php'],
    ['loc' => $base . 'support.php',       'path' => __DIR__ . '/support.php'],
    ['loc' => $base . 'privacy.php',       'path' => __DIR__ . '/privacy.php'],
    ['loc' => $base . 'terms.php',         'path' => __DIR__ . '/terms.php'],
    ['loc' => $base . 'account/login.php', 'path' => __DIR__ . '/account/login.php'],
    ['loc' => $base . 'account/register.php', 'path' => __DIR__ . '/account/register.php'],
];

$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as $page) {
    $lastmod = $today;
    if (!empty($page['path']) && is_file($page['path'])) {
        $lastmod = date('Y-m-d', filemtime($page['path']));
    }
    ?>
    <url>
        <loc><?php echo htmlspecialchars($page['loc']); ?></loc>
        <lastmod><?php echo $lastmod; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php } ?>
</urlset>

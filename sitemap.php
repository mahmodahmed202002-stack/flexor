<?php
include('includes/db.php');

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<!-- الصفحة الرئيسية -->
<url>
    <loc>https://flexor.gt.tc/</loc>
    <lastmod><?php echo date('Y-m-d'); ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
</url>

<!-- الأفلام -->
<?php
$movies = $conn->query("SELECT slug FROM movies WHERE slug IS NOT NULL");
while($m = $movies->fetch_assoc()):
?>
<url>
    <loc>https://flexor.gt.tc/movie/<?php echo htmlspecialchars($m['slug']); ?></loc>
    <lastmod><?php echo date('Y-m-d'); ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
</url>
<?php endwhile; ?>

<!-- المسلسلات -->
<?php
$series = $conn->query("SELECT slug FROM series WHERE slug IS NOT NULL");
while($s = $series->fetch_assoc()):
?>
<url>
    <loc>https://flexor.gt.tc/series/<?php echo htmlspecialchars($s['slug']); ?></loc>
    <lastmod><?php echo date('Y-m-d'); ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
</url>
<?php endwhile; ?>

<!-- اليوتيوب -->
<url>
    <loc>https://flexor.gt.tc/youtube-platforms.php</loc>
    <lastmod><?php echo date('Y-m-d'); ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.7</priority>
</url>

<!-- البث المباشر -->
<url>
    <loc>https://flexor.gt.tc/flexor-tv.php</loc>
    <lastmod><?php echo date('Y-m-d'); ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.7</priority>
</url>

</urlset>
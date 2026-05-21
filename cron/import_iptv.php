<?php

    if (!isset($_GET['manual'])) {
    exit("Forbidden");
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

echo "🌍 بدء استيراد جميع القنوات...\n";

$m3u_url = "https://iptv-org.github.io/iptv/index.m3u";

$content = @file_get_contents($m3u_url);

if (!$content) {
    exit("❌ فشل تحميل القنوات");
}

$lines = explode("\n", $content);

$name = "";
$logo = "";
$group = "";
$count = 0;

foreach ($lines as $line) {

    $line = trim($line);

    // بيانات القناة
    if (strpos($line, "#EXTINF") === 0) {

        preg_match('/tvg-name="([^"]*)"/', $line, $name_match);
        preg_match('/tvg-logo="([^"]*)"/', $line, $logo_match);
        preg_match('/group-title="([^"]*)"/', $line, $group_match);

        $name = $name_match[1] ?? '';
        $logo = $logo_match[1] ?? '';
        $group = $group_match[1] ?? '';

        if (!$name) {
            $parts = explode(",", $line);
            $name = trim(end($parts));
        }
    }

    // الرابط
    if (strpos($line, "http") === 0) {

        $url = $line;

        // فلترة
        if (strpos($url, '.m3u8') === false) continue;
        if (!$name) continue;

        // منع التكرار
        $check = $conn->prepare("SELECT id FROM live_channels WHERE stream_url=?");
        $check->bind_param("s", $url);
        $check->execute();
        $check->store_result();

        if ($check->num_rows == 0) {

            $stmt = $conn->prepare("
                INSERT INTO live_channels 
                (channel_name, stream_url, logo_url, category, status, sort_order) 
                VALUES (?, ?, ?, ?, 'active', 0)
            ");

            $stmt->bind_param("ssss", $name, $url, $logo, $group);
            $stmt->execute();

            $count++;
        }
    }
}

echo "✅ تم إضافة $count قناة\n";
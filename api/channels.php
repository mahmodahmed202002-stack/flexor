<?php
header('Content-Type: application/json; charset=utf-8');

// 🚀 منع الكاش القديم من المتصفح
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

// =========================
// ⚡ CACHE (اختياري سريع)
// =========================
$cacheFile = __DIR__ . '/channels_cache.json';
$cacheTime = 10; // ثواني

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    echo file_get_contents($cacheFile);
    exit;
}

// =========================
// 📡 DB QUERY
// =========================
$sql = "SELECT id, channel_name, stream_url, logo_url, backup_url
        FROM live_channels 
        WHERE status='active' 
        ORDER BY sort_order ASC";

$result = $conn->query($sql);

$channels = [];

if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        $channels[] = [
            "id" => (int)$row['id'],
            "channel_name" => $row['channel_name'],
            "stream_url" => $row['stream_url'],
            "logo_url" => $row['logo_url'] ?: "",
            "backup_url" => $row['backup_url'] ?: ""
        ];
    }
}

// =========================
// 📦 RESPONSE
// =========================
$response = [
    "status" => "success",
    "count" => count($channels),
    "channels" => $channels
];

// 💾 حفظ الكاش
file_put_contents($cacheFile, json_encode($response, JSON_UNESCAPED_UNICODE));

// إخراج
echo json_encode($response, JSON_UNESCAPED_UNICODE);
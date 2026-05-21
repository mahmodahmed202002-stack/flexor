<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

set_time_limit(0);

echo "🚀 START CHECK...\n";

// ⚡ CURL FAST CHECK (HEAD فقط)
function fastCheck($url){

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true, // 🔥 HEAD ONLY
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    curl_exec($ch);

    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return ($http >= 200 && $http < 400);
}

// =========================
// 📡 GET CHANNELS
// =========================
$res = $conn->query("SELECT id, stream_url, backup_url FROM live_channels");

$active = 0;
$inactive = 0;

while($row = $res->fetch_assoc()){

    $isWorking = false;

    // 🔥 جرب الأساسي
    if(!empty($row['stream_url'])){
        if(fastCheck($row['stream_url'])){
            $isWorking = true;
        }
    }

    // 🔥 fallback backup
    if(!$isWorking && !empty($row['backup_url'])){
        if(fastCheck($row['backup_url'])){
            $isWorking = true;
        }
    }

    // =========================
    // 🧠 UPDATE STATUS
    // =========================
    $status = $isWorking ? 'active' : 'inactive';

    $stmt = $conn->prepare("UPDATE live_channels SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $row['id']);
    $stmt->execute();

    if($status === 'active') $active++;
    else $inactive++;
}

echo "✅ ACTIVE: $active\n";
echo "❌ INACTIVE: $inactive\n";

echo "🎯 DONE";
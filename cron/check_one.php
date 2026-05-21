<?php
include('../includes/db.php');

header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);

if(!$id){
    echo json_encode(['status'=>'inactive']);
    exit;
}

/* =========================
   🔥 GET STREAM
========================= */
$stmt = $conn->prepare("SELECT stream_url FROM live_channels WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$url = trim($res['stream_url'] ?? '');

if(!$url){
    echo json_encode(['status'=>'inactive']);
    exit;
}

/* =========================
   🔥 CURL FETCH
========================= */
function fetch($url){

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => "Mozilla/5.0",
        CURLOPT_RANGE => "0-8000" // 🔥 مهم جداً
    ]);

    $data = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return [$data, $http];
}

/* =========================
   🔥 REAL STREAM CHECK (ULTRA)
========================= */
function checkStreamReal($url){

    // 1️⃣ جلب playlist
    list($playlist, $http) = fetch($url);

    if(!$playlist || $http < 200 || $http >= 400){
        return false;
    }

    // 🎯 لو M3U8
    if(strpos($playlist, "#EXTM3U") !== false){

        // 🔥 استخراج أول segment (.ts أو chunk)
        preg_match('/^(?!#)(.*)$/m', $playlist, $m);

        if(empty($m[1])){
            return false;
        }

        $segment = trim($m[1]);

        // 🔥 إصلاح الروابط النسبية
        if(!preg_match('#^https?://#', $segment)){
            $base = rtrim(dirname($url), '/') . '/';
            $segment = $base . ltrim($segment, '/');
        }

        // 2️⃣ اختبار segment الحقيقي
        list($segData, $segHttp) = fetch($segment);

        if($segData && $segHttp == 200){
            return true;
        }

        return false;
    }

    // 🎯 لو stream مباشر (mp4 / ts)
    if(strpos($url, ".ts") !== false || strpos($url, ".mp4") !== false){
        return ($http >= 200 && $http < 400);
    }

    // fallback
    return false;
}

/* =========================
   🔥 CHECK
========================= */
$status = checkStreamReal($url) ? 'active' : 'inactive';

/* =========================
   🔥 UPDATE DB
========================= */
$update = $conn->prepare("UPDATE live_channels SET status=? WHERE id=?");
$update->bind_param("si", $status, $id);
$update->execute();

/* =========================
   🔥 RESPONSE
========================= */
echo json_encode([
    'status' => $status
]);
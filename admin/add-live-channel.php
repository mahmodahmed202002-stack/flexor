<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if(!isset($_SESSION['admin_logged_in'])) { exit("Access Denied"); }
include('../includes/db.php');

$message = "";

/* =========================
   🔥 REAL STREAM CHECK
========================= */
function checkStream($url){

    if(strpos($url, 'http') === false) return false;

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RANGE => '0-5000',
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);

    $data = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if(!$data) return false;

    if($http >= 200 && $http < 400){
        if(strpos($url, '.m3u8') !== false){
            return (strpos($data, '#EXTM3U') !== false);
        }
        return true;
    }

    return false;
}

/* =========================
   🔥 SCAN API
========================= */
if(isset($_GET['scan_stream'])){
    header('Content-Type: application/json');
    echo json_encode([
        'status'=> checkStream($_GET['scan_stream']) ? 'working' : 'dead'
    ]);
    exit;
}

/* =========================
   ➕ ADD SINGLE
========================= */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manual_add'])) {

    $name = trim($_POST['channel_name']);
    $url = trim($_POST['stream_url']);
    $logo = trim($_POST['logo_url']);
    $order = intval($_POST['sort_order']);

    $stmt = $conn->prepare("SELECT id FROM live_channels WHERE stream_url=? LIMIT 1");
    $stmt->bind_param("s", $url);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0){
        $message = "<div style='color:red'>❌ القناة مضافة بالفعل</div>";
    }else{
        if(!checkStream($url)){
            $message = "<div style='color:red'>❌ الرابط لا يعمل</div>";
        }else{
            $stmt = $conn->prepare("
                INSERT INTO live_channels 
                (channel_name, stream_url, logo_url, sort_order, status)
                VALUES (?, ?, ?, ?, 'active')
            ");
            $stmt->bind_param("sssi", $name, $url, $logo, $order);
            $stmt->execute();
            $message = "<div style='color:lime'>✅ تم إضافة القناة</div>";
        }
    }
}

/* =========================
   🔥 FETCH
========================= */
function fetchUrl($url){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/* =========================
   🔥 PARSE
========================= */
function parseM3U($content){
    $lines = explode("\n", $content);
    $channels = [];
    $name=""; $logo="";

    foreach($lines as $line){
        $line = trim($line);

        if(strpos($line, '#EXTINF') !== false){
            preg_match('/tvg-logo="([^"]*)"/', $line, $m);
            $logo = $m[1] ?? '';
            $parts = explode(',', $line);
            $name = trim(end($parts));
        }

        if(preg_match('#^https?://#', $line)){
            $channels[] = [
                'name'=>$name ?: 'Channel',
                'url'=>$line,
                'logo'=>$logo
            ];
        }
    }
    return $channels;
}

/* =========================
   🔥 FETCH SMART
========================= */
if(isset($_GET['fetch_any'])){
    header('Content-Type: application/json');

    $url = $_GET['fetch_any'];

    if(strpos($url, 'github.com') !== false && strpos($url, '/blob/') !== false){
        $url = str_replace(
            ['github.com', '/blob/'],
            ['raw.githubusercontent.com', '/'],
            $url
        );
    }

    $content = fetchUrl($url);

    if(!$content){
        echo json_encode(['status'=>false]);
        exit;
    }

    $channels = parseM3U($content);

    echo json_encode([
        'status'=>true,
        'channels'=>$channels,
        'count'=>count($channels)
    ]);
    exit;
}

/* =========================
   🔥 START IMPORT
========================= */
if(isset($_POST['start_import'])){
    $_SESSION['import_queue'] = json_decode($_POST['channels'], true);
    $_SESSION['import_index'] = 0;
    $_SESSION['added'] = 0;
    $_SESSION['skipped'] = 0;

    echo json_encode(['status'=>'started']);
    exit;
}

/* =========================
   🔥 PROCESS BATCH
========================= */
if(isset($_GET['process_batch'])){

    header('Content-Type: application/json');

    $batch = 15;

    $queue = $_SESSION['import_queue'] ?? [];
    $index = $_SESSION['import_index'] ?? 0;

    $added = $_SESSION['added'] ?? 0;
    $skipped = $_SESSION['skipped'] ?? 0;

    $total = count($queue);

    for($i=0; $i<$batch && $index < $total; $i++, $index++){

        $ch = $queue[$index];

        $stmt = $conn->prepare("SELECT id FROM live_channels WHERE stream_url=? LIMIT 1");
        $stmt->bind_param("s", $ch['url']);
        $stmt->execute();
        $stmt->store_result();

        if($stmt->num_rows > 0){
            $skipped++;
            continue;
        }

        $stmt = $conn->prepare("
            INSERT INTO live_channels (channel_name, stream_url, logo_url, status)
            VALUES (?, ?, ?, 'active')
        ");

        $stmt->bind_param("sss", $ch['name'], $ch['url'], $ch['logo']);
        $stmt->execute();

        $added++;
    }

    $_SESSION['import_index'] = $index;
    $_SESSION['added'] = $added;
    $_SESSION['skipped'] = $skipped;

    echo json_encode([
        'done'=>$index,
        'total'=>$total,
        'added'=>$added,
        'skipped'=>$skipped,
        'finished'=>($index >= $total)
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>IPTV Panel</title>

<style>
body{background:#0a0a0a;color:#fff;font-family:Cairo;padding:20px}
input{width:100%;padding:12px;margin-bottom:10px;background:#111;border:1px solid #333;color:#fff}
.btn{padding:12px;background:#d4ff00;border:none;cursor:pointer;width:100%;margin-bottom:5px}
.box{background:#161616;padding:15px;border-radius:10px;margin-bottom:20px}
.channel-item{padding:5px;border-bottom:1px solid #222}
</style>
</head>

<body>

<?= $message ?>

<div class="box">
<h3>➕ إضافة قناة</h3>
<form method="POST">
<input type="hidden" name="manual_add" value="1">
<input name="channel_name" placeholder="اسم القناة">
<input name="stream_url" placeholder="رابط m3u8">
<input name="logo_url" placeholder="لوجو">
<input name="sort_order" value="0">
<button class="btn">💾 إضافة</button>
</form>
</div>

<div class="box">
<h3>📥 سحب القنوات</h3>

<input id="any_url" placeholder="أي رابط">

<button class="btn" onclick="fetchChannels()">🔍 فحص</button>
<button class="btn" onclick="startImport()">🚀 استيراد</button>
<button class="btn" onclick="startScan()">⚡ فحص</button>

<div id="info"></div>
<div id="channelsList"></div>

</div>

<script>

let channels = [];

function fetchChannels(){
info.innerText="⏳...";
fetch('?fetch_any='+encodeURIComponent(any_url.value))
.then(r=>r.json())
.then(d=>{
channels=d.channels||[];
info.innerText="تم جلب "+d.count;

channelsList.innerHTML="";
channels.forEach((ch,i)=>{
channelsList.innerHTML+=`
<div class="channel-item">
📺 ${ch.name}
<span id="status-${i}">⏳</span>
</div>`;
});
});
}

/* =========================
   🔥 SCAN
========================= */
async function startScan(){

let ok=0, bad=0;

for(let i=0;i<channels.length;i++){

let res=await fetch('?scan_stream='+encodeURIComponent(channels[i].url));
let d=await res.json();

let el=document.getElementById('status-'+i);

if(d.status==="working"){
el.innerText="✔";
el.style.color="lime";
channels[i].valid=true;
ok++;
}else{
el.innerText="❌";
el.style.color="red";
channels[i].valid=false;
bad++;
}

info.innerText=`✔ ${ok} | ❌ ${bad}`;
await new Promise(r=>setTimeout(r,40));
}
}

/* =========================
   🔥 IMPORT FIXED
========================= */
function startImport(){

let filtered = channels.filter(c=>c.valid);

if(filtered.length===0){
alert("لا يوجد قنوات شغالة");
return;
}

fetch('',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:'start_import=1&channels='+encodeURIComponent(JSON.stringify(filtered))
}).then(()=>{
runBatch();
});

}

function runBatch(){

fetch('?process_batch=1')
.then(r=>r.json())
.then(d=>{

info.innerText = `${d.done}/${d.total} | ✔ ${d.added}`;

if(!d.finished){
setTimeout(runBatch,150);
}else{
alert("🔥 تم إضافة القنوات");
}

});
}

</script>

</body>
</html>
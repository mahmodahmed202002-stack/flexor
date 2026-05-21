<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) { exit("Access Denied"); }
include('../includes/db.php');

/* ================================
   🗑 حذف قناة
================================ */
if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM live_channels WHERE id = $id");
    header("Location: manage-live-tv.php");
    exit;
}

/* ================================
   🧹 حذف غير الشغال
================================ */
if(isset($_GET['delete_inactive'])){
    $conn->query("DELETE FROM live_channels WHERE status='inactive'");
    header("Location: manage-live-tv.php");
    exit;
}

/* ================================
   📊 إحصائيات
================================ */
$total = $conn->query("SELECT COUNT(*) as c FROM live_channels")->fetch_assoc()['c'];
$active = $conn->query("SELECT COUNT(*) as c FROM live_channels WHERE status='active'")->fetch_assoc()['c'];
$inactive = $conn->query("SELECT COUNT(*) as c FROM live_channels WHERE status='inactive'")->fetch_assoc()['c'];

/* ================================
   📡 جلب القنوات
================================ */
$channels = $conn->query("
SELECT id, channel_name, logo_url, stream_url, status 
FROM live_channels 
ORDER BY sort_order ASC
");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body { font-family:Cairo; background:#0a0a0a; color:#fff; padding:20px; }

/* Header */
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }

.stats { display:flex; gap:10px; }

.stat {
    background:#161616;
    padding:10px 15px;
    border-radius:8px;
    border:1px solid #222;
}

/* Actions */
.actions { display:flex; gap:10px; flex-wrap:wrap; }

.actions button {
    padding:10px 15px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight:bold;
    transition:.2s;
}

.actions button:hover { transform:scale(1.05); }

.import { background:#00bcd4; color:#000; }
.check { background:#2196f3; color:#fff; }
.clean { background:#ff4444; color:#fff; }
.delete-inactive { background:#ff9800; color:#000; }

/* Progress */
.progress-box {
    margin:15px 0;
    display:none;
    background:#111;
    padding:15px;
    border-radius:10px;
    border:1px solid #222;
}

.progress-bar {
    width:100%;
    height:12px;
    background:#222;
    border-radius:10px;
    overflow:hidden;
    margin-top:10px;
}

.progress-fill {
    height:100%;
    width:0%;
    background:#d4ff00;
    transition:.3s;
}

/* List */
.channel-item {
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px;
    background:#161616;
    margin-bottom:8px;
    border-radius:8px;
    border:1px solid #222;
    transition:.2s;
}

.channel-item:hover {
    border-color:#d4ff00;
    background:#1a1a1a;
}

.logo {
    width:40px;
    height:40px;
    border-radius:8px;
    object-fit:cover;
    background:#222;
}

.status {
    margin-right:auto;
    font-size:13px;
    padding:4px 10px;
    border-radius:20px;
}

.active { background:#00ff66; color:#000; }
.inactive { background:#ff4444; }
</style>
</head>

<body>

<div class="header">

<h2>📡 إدارة القنوات</h2>

<div class="stats">
    <div class="stat">📊 الكل: <?= $total ?></div>
    <div class="stat" style="color:#00ff66">✔️ <?= $active ?></div>
    <div class="stat" style="color:#ff4444">❌ <?= $inactive ?></div>
</div>

<div class="actions">
    <button class="import" onclick="runImport()">📥 Import</button>
    <button class="check" onclick="startCheck()">🔍 Check</button>
    <button class="clean" onclick="runClean()">🧹 Clean API</button>
    <button class="delete-inactive" onclick="deleteInactive()">🔥 حذف غير الشغال</button>
</div>

</div>

<!-- Progress -->
<div class="progress-box" id="progressBox">
    <div id="progressText">0 / 0</div>
    <div class="progress-bar">
        <div class="progress-fill" id="progressFill"></div>
    </div>
</div>

<!-- List -->
<?php while($row = $channels->fetch_assoc()): ?>
<div class="channel-item" data-id="<?= $row['id'] ?>">

    <img src="<?= $row['logo_url'] ?: 'https://via.placeholder.com/40?text=TV' ?>" class="logo">

    <div><?= htmlspecialchars($row['channel_name']) ?></div>

    <div class="status <?= $row['status']=='active'?'active':'inactive' ?>">
        <?= $row['status'] ?>
    </div>

</div>
<?php endwhile; ?>

<script>

/* =========================
   IMPORT
========================= */
function runImport(){
    if(!confirm("بدء استيراد القنوات؟")) return;

    fetch('../cron/import_iptv.php')
    .then(()=>alert("تم الاستيراد ✅"));
}

/* =========================
   CLEAN API
========================= */
function runClean(){
    if(confirm("تنظيف القنوات غير الشغالة؟")){
        fetch('../cron/clean_channels.php?manual=1')
        .then(()=>location.reload());
    }
}

/* =========================
   DELETE INACTIVE
========================= */
function deleteInactive(){
    if(confirm("🔥 حذف كل القنوات غير الشغالة؟")){
        window.location.href = "?delete_inactive=1";
    }
}

/* =========================
   CHECK SYSTEM
========================= */
async function startCheck(){

    let items = Array.from(document.querySelectorAll('.channel-item'));
    let total = items.length;

    let active = 0;
    let inactive = 0;
    let done = 0;

    let box = document.getElementById("progressBox");
    let fill = document.getElementById("progressFill");
    let text = document.getElementById("progressText");

    box.style.display = "block";

    const batchSize = 10; // 🔥 السرعة

    for(let i=0; i<items.length; i+=batchSize){

        let batch = items.slice(i,i+batchSize);

        await Promise.all(batch.map(async (item)=>{

            let id = item.dataset.id;

            try{
                let res = await fetch('../cron/check_one.php?id='+id);
                let data = await res.json();

                let statusDiv = item.querySelector('.status');

                if(data.status==="active"){
                    active++;
                    statusDiv.className="status active";
                    statusDiv.innerText="active";
                }else{
                    inactive++;
                    statusDiv.className="status inactive";
                    statusDiv.innerText="inactive";
                }

            }catch{
                inactive++;
            }

            done++;

            let percent = (done/total)*100;
            fill.style.width = percent+"%";
            text.innerText = `${done}/${total} | ✔️ ${active} | ❌ ${inactive}`;
        }));

    }

    alert("تم الفحص بسرعة 🚀");
}

</script>

</body>
</html>
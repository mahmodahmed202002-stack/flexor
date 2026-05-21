<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) { exit("Access Denied"); }
include('../includes/db.php');

// التأكد من وجود ID القناة
if(!isset($_GET['channel_id'])) { exit("Channel ID missing"); }
$channel_id = intval($_GET['channel_id']);

// جلب بيانات القناة لعرض اسمها
$channel_info = $conn->query("SELECT channel_name FROM yt_channels WHERE id = $channel_id")->fetch_assoc();

// 1. معالجة إضافة فيديو جديد
if(isset($_POST['add_video'])) {
    $title = mysqli_real_escape_string($conn, $_POST['video_title']);
    $url = $_POST['video_url'];
    
    // دالة لاستخراج ID الفيديو من رابط يوتيوب
    function getYoutubeId($url) {
        parse_str(parse_url($url, PHP_URL_QUERY), $my_array_of_vars);
        if(isset($my_array_of_vars['v'])) return $my_array_of_vars['v'];
        
        $path = explode('/', parse_url($url, PHP_URL_PATH));
        return end($path);
    }
    
    $video_id = getYoutubeId($url);
    
    if($video_id) {
        $conn->query("INSERT INTO yt_videos (channel_id, video_title, video_id) VALUES ($channel_id, '$title', '$video_id')");
        header("Location: manage-yt-videos.php?channel_id=$channel_id&success=1");
    }
}

// 2. معالجة الحذف
if(isset($_GET['delete'])) {
    $vid = intval($_GET['delete']);
    $conn->query("DELETE FROM yt_videos WHERE id = $vid");
    header("Location: manage-yt-videos.php?channel_id=$channel_id");
}

// 3. جلب الفيديوهات التابعة لهذه القناة فقط
$videos = $conn->query("SELECT * FROM yt_videos WHERE channel_id = $channel_id ORDER BY sort_order ASC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; background: #0a0a0a; color: white; padding: 20px; }
        .breadcrumb { color: #d4ff00; margin-bottom: 10px; font-size: 14px; }
        .page-title { color: white; margin-bottom: 25px; }
        .page-title span { color: #d4ff00; }
        
        .add-form { background: #111; padding: 20px; border-radius: 15px; border: 1px solid #222; margin-bottom: 30px; }
        .flexor-input { width: 100%; background: #000; border: 1px solid #333; color: #fff; padding: 12px; border-radius: 8px; margin-bottom: 10px; box-sizing: border-box; }
        .btn-add { background: #d4ff00; color: #000; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 900; cursor: pointer; width: 100%; }

        .video-list { list-style: none; padding: 0; }
        .video-item { 
            background: #161616; border: 1px solid #222; 
            margin-bottom: 8px; padding: 10px 15px; 
            border-radius: 10px; display: flex; align-items: center; 
        }
        .handle { cursor: grab; color: #444; margin-left: 15px; }
        .thumb { width: 80px; height: 45px; border-radius: 5px; object-fit: cover; margin-left: 15px; border: 1px solid #333; }
        .v-title { flex-grow: 1; font-size: 14px; }
        .btn-del { color: #ff4444; text-decoration: none; font-size: 12px; }
    </style>
</head>
<body>

    <div class="breadcrumb">
        <a href="manage-yt-channels.php" style="color: #d4ff00; text-decoration: none;">إدارة القنوات</a> / فيديوهات القناة
    </div>
    <h2 class="page-title">إدارة فيديوهات: <span><?php echo $channel_info['channel_name']; ?></span></h2>

    <form method="POST" class="add-form">
        <input type="text" name="video_title" class="flexor-input" placeholder="عنوان الفيديو (سيظهر في البلاي ليست)" required>
        <input type="text" name="video_url" class="flexor-input" placeholder="ضع رابط الفيديو من يوتيوب هنا" required>
        <button type="submit" name="add_video" class="btn-add">إضافة الفيديو للبلاي ليست</button>
    </form>

    <h3>ترتيب البلاي ليست (اسحب للترتيب)</h3>
    <ul id="video-sort-list" class="video-list">
        <?php while($row = $videos->fetch_assoc()): ?>
        <li class="video-item" data-id="<?php echo $row['id']; ?>">
            <span class="handle">☰</span>
            <img src="https://img.youtube.com/vi/<?php echo $row['video_id']; ?>/mqdefault.jpg" class="thumb">
            <div class="v-title"><?php echo htmlspecialchars($row['video_title']); ?></div>
            <a href="?channel_id=<?php echo $channel_id; ?>&delete=<?php echo $row['id']; ?>" class="btn-del" onclick="return confirm('حذف؟')">حذف</a>
        </li>
        <?php endwhile; ?>
    </ul>

    <script>
        const dragArea = document.getElementById('video-sort-list');
        new Sortable(dragArea, {
            animation: 150,
            handle: '.handle',
            onEnd: function() {
                let orderData = {};
                dragArea.querySelectorAll('.video-item').forEach((item, index) => {
                    orderData[item.getAttribute('data-id')] = index + 1;
                });

                fetch('update-yt-videos-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'order=' + JSON.stringify(orderData)
                });
            }
        });
    </script>
</body>
</html>
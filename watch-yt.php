<?php
include('includes/db.php');
include('includes/header.php');

// جلب القناة بناءً على الـ Slug
if (!isset($_GET['slug'])) {
    header("Location: youtube-platforms.php");
    exit();
}

$slug = mysqli_real_escape_string($conn, $_GET['slug']);
$channel_res = $conn->query("SELECT * FROM yt_channels WHERE slug = '$slug'");
$channel = $channel_res->fetch_assoc();

if (!$channel) { exit("Channel not found"); }

$channel_id = $channel['id'];

// جلب الفيديوهات الخاصة بهذه القناة
$videos_res = $conn->query("SELECT * FROM yt_videos WHERE channel_id = $channel_id ORDER BY sort_order ASC");
$videos = [];
while($v = $videos_res->fetch_assoc()) {
    $videos[] = $v;
}

// الفيديو الافتراضي (أول فيديو في القائمة)
$current_video_id = isset($_GET['v']) ? mysqli_real_escape_string($conn, $_GET['v']) : (isset($videos[0]) ? $videos[0]['video_id'] : '');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <style>
        body { background: #050505; color: white; }
        .watch-container {
            display: flex;
            flex-wrap: wrap;
            padding: 90px 2% 30px;
            gap: 20px;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* منطقة المشغل */
        .video-main { flex: 1; min-width: 60%; }
        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .video-wrapper iframe {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
        }

        .video-info { margin-top: 20px; }
        .video-info h1 { font-size: 22px; color: #fff; font-weight: 700; }
        .channel-meta { display: flex; align-items: center; margin-top: 15px; border-bottom: 1px solid #222; padding-bottom: 15px; }
        .channel-meta img { width: 50px; height: 50px; border-radius: 50%; margin-left: 15px; border: 2px solid #d4ff00; }

        /* قائمة التشغيل الجانبية */
        .playlist-sidebar {
            width: 380px;
            background: #111;
            border-radius: 12px;
            border: 1px solid #222;
            display: flex;
            flex-direction: column;
            max-height: 80vh;
        }
        .playlist-header { padding: 20px; border-bottom: 1px solid #222; }
        .playlist-header h3 { margin: 0; font-size: 18px; color: #d4ff00; }
        
        .playlist-items { overflow-y: auto; flex: 1; }
        .playlist-item {
            display: flex;
            padding: 10px;
            gap: 12px;
            text-decoration: none;
            color: #ccc;
            transition: 0.3s;
            border-bottom: 1px solid #1a1a1a;
        }
        .playlist-item:hover { background: #1a1a1a; color: #fff; }
        .playlist-item.active { background: rgba(212, 255, 0, 0.1); border-right: 4px solid #d4ff00; }
        
        .item-thumb { position: relative; width: 120px; flex-shrink: 0; }
        .item-thumb img { width: 100%; border-radius: 6px; }
        .item-title { font-size: 14px; font-weight: 600; line-height: 1.4; }

        @media (max-width: 1024px) {
            .playlist-sidebar { width: 100%; max-height: none; }
            .video-main { min-width: 100%; }
        }
    </style>
</head>
<body>

<div class="watch-container">
    <!-- اليسار: مشغل الفيديو -->
    <div class="video-main">
        <div class="video-wrapper">
            <?php if($current_video_id): ?>
                <iframe src="https://www.youtube.com/embed/<?php echo $current_video_id; ?>?autoplay=1&rel=0" frameborder="0" allowfullscreen></iframe>
            <?php else: ?>
                <div style="padding: 100px; text-align: center;">لا توجد فيديوهات حالياً</div>
            <?php endif; ?>
        </div>
        
        <div class="video-info">
            <div class="channel-meta">
                <img src="<?php echo $channel['channel_image']; ?>">
                <div>
                    <h2 style="font-size: 18px; margin: 0;"><?php echo $channel['channel_name']; ?></h2>
                    <span style="color: #666; font-size: 13px;">محتوى يوتيوب مختار</span>
                </div>
            </div>
        </div>
    </div>

    <!-- اليمين: قائمة التشغيل -->
    <div class="playlist-sidebar">
        <div class="playlist-header">
            <h3>قائمة الحلقات</h3>
        </div>
        <div class="playlist-items">
            <?php foreach($videos as $video): 
                $active_class = ($video['video_id'] == $current_video_id) ? 'active' : '';
            ?>
                <a href="?slug=<?php echo $slug; ?>&v=<?php echo $video['video_id']; ?>" class="playlist-item <?php echo $active_class; ?>">
                    <div class="item-thumb">
                        <img src="https://img.youtube.com/vi/<?php echo $video['video_id']; ?>/mqdefault.jpg">
                    </div>
                    <div class="item-title"><?php echo htmlspecialchars($video['video_title']); ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>

</body>
</html>
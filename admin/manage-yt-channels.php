<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) { exit("Access Denied"); }
include('../includes/db.php');

// 1. معالجة إضافة قناة جديدة
if(isset($_POST['add_channel'])) {
    $name = mysqli_real_escape_string($conn, $_POST['channel_name']);
    $slug = str_replace(' ', '-', $name);
    $image = mysqli_real_escape_string($conn, $_POST['channel_image']);
    $cover = mysqli_real_escape_string($conn, $_POST['channel_cover']);
    $is_hero = isset($_POST['is_hero']) ? 1 : 0;
    
    $conn->query("INSERT INTO yt_channels (channel_name, slug, channel_image, channel_cover, is_hero) VALUES ('$name', '$slug', '$image', '$cover', $is_hero)");
    header("Location: manage-yt-channels.php?success=1");
}

// 2. معالجة الحذف
if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM yt_channels WHERE id = $id");
    header("Location: manage-yt-channels.php");
}

// 3. جلب القنوات مرتبة
$channels = $conn->query("SELECT * FROM yt_channels ORDER BY sort_order ASC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; background: #0a0a0a; color: white; padding: 20px; }
        .page-title { color: #d4ff00; margin-bottom: 20px; font-weight: 700; }
        
        /* تصميم الفورم */
        .add-form { background: #111; padding: 25px; border-radius: 15px; border: 1px solid #222; margin-bottom: 30px; max-width: 800px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        .input-group label { display: block; margin-bottom: 8px; color: #aaa; font-size: 14px; }
        .flexor-input { width: 100%; background: #000; border: 1px solid #333; color: #fff; padding: 12px; border-radius: 8px; box-sizing: border-box; }
        .flexor-input:focus { border-color: #d4ff00; outline: none; }

        /* --- تصميم زر السويتش (كما في الصورة) --- */
        .hero-toggle-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(212, 255, 0, 0.05);
            padding: 15px;
            border-radius: 10px;
            border: 1px dashed #d4ff0044;
            margin: 20px 0;
        }
        .hero-label { display: flex; align-items: center; gap: 10px; color: #fff; font-weight: bold; }
        .hero-label i { color: #d4ff00; }
        
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; inset: 0;
            background-color: #333; transition: .4s; border-radius: 34px;
        }
        .slider:before {
            position: absolute; content: ""; height: 18px; width: 18px;
            left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: #d4ff00; }
        input:checked + .slider:before { transform: translateX(26px); background-color: #000; }

        .btn-add { background: #d4ff00; color: #000; padding: 12px; border: none; border-radius: 8px; font-weight: 900; cursor: pointer; width: 100%; font-size: 16px; transition: 0.3s; }
        .btn-add:hover { background: #fff; }

        /* تصميم القائمة */
        .channel-list { list-style: none; padding: 0; max-width: 800px; }
        .channel-item { 
            background: #161616; border: 1px solid #222; 
            margin-bottom: 12px; padding: 15px; 
            border-radius: 12px; display: flex; align-items: center; 
            transition: 0.3s;
        }
        .channel-item:hover { border-color: #d4ff00; }
        .handle { cursor: grab; color: #444; margin-left: 20px; font-size: 20px; }
        .preview-img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #333; margin-left: 15px; }
        .channel-info { flex-grow: 1; }
        .channel-info h4 { margin: 0; font-size: 16px; }
        
        .actions { display: flex; gap: 10px; align-items: center; }
        .hero-status { font-size: 11px; padding: 2px 8px; border-radius: 4px; background: #d4ff0022; color: #d4ff00; border: 1px solid #d4ff0044; }
        .btn-manage { background: #d4ff00; color: #000; text-decoration: none; padding: 6px 15px; border-radius: 6px; font-size: 13px; font-weight: bold; }
        .btn-del { color: #ff4444; text-decoration: none; font-size: 18px; padding: 0 10px; }
    </style>
</head>
<body>

    <h2 class="page-title">إدارة منصات يوتيوب</h2>

    <form method="POST" class="add-form">
        <div class="form-grid">
            <div class="input-group full-width">
                <label>اسم القناة أو السلسلة</label>
                <input type="text" name="channel_name" class="flexor-input" placeholder="مثلاً: الدحيح" required>
            </div>
            <div class="input-group">
                <label>رابط صورة البروفايل (دائرية)</label>
                <input type="text" name="channel_image" class="flexor-input" placeholder="https://..." required>
            </div>
            <div class="input-group">
                <label>رابط غلاف الكارت (المستطيل)</label>
                <input type="text" name="channel_cover" class="flexor-input" placeholder="https://..." required>
            </div>
        </div>

        <!-- زر السويتش حسب طلبك -->
        <div class="hero-toggle-container">
            <label class="switch">
                <input type="checkbox" name="is_hero">
                <span class="slider"></span>
            </label>
            <div class="hero-label">
                عرض القناة في السلايدر الرئيسي (Hero Section) ⭐
            </div>
        </div>

        <button type="submit" name="add_channel" class="btn-add">إضافة القناة للموقع</button>
    </form>

    <h3 style="margin-bottom: 15px;">الترتيب الحالي</h3>
    <ul id="items-list" class="channel-list">
        <?php while($row = $channels->fetch_assoc()): ?>
        <li class="channel-item" data-id="<?php echo $row['id']; ?>">
            <span class="handle">☰</span>
            <img src="<?php echo $row['channel_image']; ?>" class="preview-img">
            <div class="channel-info">
                <h4><?php echo htmlspecialchars($row['channel_name']); ?></h4>
                <?php if($row['is_hero']): ?>
                    <span class="hero-status">في الهيرو ⭐</span>
                <?php endif; ?>
            </div>
            <div class="actions">
                <a href="manage-yt-videos.php?channel_id=<?php echo $row['id']; ?>" class="btn-manage">إدارة الفيديوهات</a>
                <a href="?delete=<?php echo $row['id']; ?>" class="btn-del" onclick="return confirm('حذف؟')">🗑</a>
            </div>
        </li>
        <?php endwhile; ?>
    </ul>

    <script>
        // كود الترتيب (Sortable)
        const dragArea = document.getElementById('items-list');
        new Sortable(dragArea, {
            animation: 150,
            handle: '.handle',
            onEnd: function() {
                let orderData = {};
                dragArea.querySelectorAll('.channel-item').forEach((item, index) => {
                    orderData[item.getAttribute('data-id')] = index + 1;
                });

                fetch('update-yt-channels-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'order=' + JSON.stringify(orderData)
                });
            }
        });
    </script>
</body>
</html>
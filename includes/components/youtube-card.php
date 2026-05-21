<?php
function renderYoutubeCard($row, $conn) {
    $cid = $row['id'];
    // جلب عدد الفيديوهات الفعلي من قاعدة البيانات لكل قناة
    $v_count_res = $conn->query("SELECT id FROM yt_videos WHERE channel_id = $cid");
    $v_count = $v_count_res ? $v_count_res->num_rows : 0;
    ?>
    <style>
        /* إضافة ستايل الكارت لضمان ظهوره بشكل صحيح في الانديكس */
        .channel-card-home {
            position: relative;
            background: #111;
            border-radius: 15px;
            overflow: hidden;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid #222;
            height: 220px;
            display: block;
        }
        .channel-card-home:hover {
            transform: scale(1.05);
            border-color: #d4ff00;
            box-shadow: 0 15px 40px rgba(0,0,0,0.7);
            z-index: 10;
        }
        .card-cover-home {
            width: 100%; height: 100%; object-fit: cover;
            position: absolute; top: 0; left: 0; z-index: 1;
            transition: 0.5s; opacity: 0.7;
        }
        .channel-card-home:hover .card-cover-home { opacity: 0.4; filter: blur(2px); }
        .overlay-dark-home {
            position: absolute; inset: 0; z-index: 2;
            background: linear-gradient(to top, rgba(0,0,0,0.9) 20%, rgba(0,0,0,0.2));
        }
        .card-content-home {
            position: absolute; inset: 0; z-index: 3;
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; padding: 20px; text-align: center;
        }
        .profile-img-home {
            width: 80px; height: 80px; border-radius: 50%;
            border: 3px solid #d4ff00; margin-bottom: 15px;
            object-fit: cover; transition: 0.4s; background: #222;
        }
        .channel-name-home {
            color: #fff; font-size: 18px; font-weight: 800; margin: 0;
        }
        .video-count-badge-home {
            margin-top: 10px; background: #d4ff00; color: #000;
            font-size: 11px; padding: 3px 12px; border-radius: 20px; font-weight: 900;
        }
    </style>

    <a href="watch-yt.php?slug=<?php echo $row['slug']; ?>" class="channel-card-home">
        <img src="<?php echo $row['channel_cover']; ?>" class="card-cover-home" alt="Cover">
        <div class="overlay-dark-home"></div>
        <div class="card-content-home">
            <img src="<?php echo $row['channel_image']; ?>" class="profile-img-home" alt="<?php echo $row['channel_name']; ?>">
            <h3 class="channel-name-home"><?php echo htmlspecialchars($row['channel_name']); ?></h3>
            <div class="video-count-badge-home"><?php echo $v_count; ?> فيديو</div>
        </div>
    </a>
    <?php
}
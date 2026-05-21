<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../../includes/db.php'; 

// 🔥 دعم slug + id (fallback)
$slug = isset($_GET['slug']) ? mysqli_real_escape_string($conn, $_GET['slug']) : null;
$series_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;
$user_id = $_SESSION['user_id'] ?? 0;

// لو فيه slug → نستخدمه
if ($slug) {

    $query = "SELECT s.*, 
              (SELECT COUNT(*) FROM favorites f WHERE f.series_id = s.id AND f.user_id = '$user_id') as is_favorite 
              FROM series s 
              WHERE s.slug = '$slug' 
              LIMIT 1";

    $result = mysqli_query($conn, $query);
    $series = mysqli_fetch_assoc($result);

    if (!$series) {
        $page_title = "المسلسل غير موجود | Flexor";
        $page_desc  = "لم يتم العثور على بيانات المسلسل على Flexor";
        $page_img   = "https://flexor.gt.tc/public/logo.png";

        include '../../includes/header.php';

        echo "<div class='error-msg'>المسلسل غير موجود</div>";
        include '../../footer.php';
        exit();
    }

    // ناخد id عشان باقي الكود
    $series_id = $series['id'];

} 
// fallback القديم (عشان الكروت القديمة تفضل شغالة)
elseif ($series_id) {

    $query = "SELECT s.*, 
              (SELECT COUNT(*) FROM favorites f WHERE f.series_id = s.id AND f.user_id = '$user_id') as is_favorite 
              FROM series s 
              WHERE s.id = '$series_id' 
              LIMIT 1";

    $result = mysqli_query($conn, $query);
    $series = mysqli_fetch_assoc($result);

    if (!$series) {
        $page_title = "المسلسل غير موجود | Flexor";
        $page_desc  = "لم يتم العثور على بيانات المسلسل على Flexor";
        $page_img   = "https://flexor.gt.tc/public/logo.png";

        include '../../includes/header.php';

        echo "<div class='error-msg'>المسلسل غير موجود</div>";
        include '../../footer.php';
        exit();
    }

} 
else {
    header("Location: ../../index.php");
    exit();
}

$genres_list = explode(',', $series['genres']);
$cast_list = !empty($series['cast_members']) ? explode(',', $series['cast_members']) : [];

$page_title = $series['title_ar'] . " جميع الحلقات | Flexor";
$page_desc  = $series['overview'];
$page_img   = $series['poster_path'];

include '../../includes/header.php';
include $_SERVER['DOCUMENT_ROOT'].'/includes/components/movie-card.php';

$similar_query = "
SELECT s.*, 
(SELECT COUNT(*) FROM favorites f WHERE f.series_id = s.id AND f.user_id = '$user_id') as is_favorite
FROM series s
WHERE s.id != '".$series['id']."'
AND s.genres LIKE '%".$genres_list[0]."%'
ORDER BY s.rating DESC
LIMIT 12
";

$similar_result = mysqli_query($conn, $similar_query);

// جلب المواسم والحلقات
$seasons = [];
$seasons_res = mysqli_query($conn, "SELECT * FROM seasons WHERE series_id = '$series_id' ORDER BY season_number ASC");
while($row = mysqli_fetch_assoc($seasons_res)) {
    $s_id = $row['id'];
    $ep_res = mysqli_query($conn, "SELECT * FROM episodes WHERE season_id = '$s_id' ORDER BY episode_number ASC");
    $row['episodes'] = mysqli_fetch_all($ep_res, MYSQLI_ASSOC);
    $seasons[] = $row;
}
?>

<script type="application/ld+json">
{
 "@context": "https://schema.org",
 "@type": "TVSeries",
 "name": "<?= addslashes($series['title_ar']) ?>",
 "alternateName": "<?= addslashes($series['title_en']) ?>",
 "image": "<?= $series['poster_path'] ?>",
 "description": "<?= addslashes($series['overview']) ?>",
 "datePublished": "<?= $series['release_year'] ?>",

 "aggregateRating": {
   "@type": "AggregateRating",
   "ratingValue": "<?= $series['rating'] ?>",
   "bestRating": "10",
   "ratingCount": "<?= rand(50,500) ?>"
 }
}
</script>
<style>
    :root { --main: #d4ff00; --bg: #0a0a0a; --card: #151515; --text: #eee; --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    body { background: var(--bg); color: var(--text); font-family: 'Cairo', sans-serif; margin: 0; direction: rtl; }
    
    /* Hero & Backdrop */
    .hero-wrapper { position: relative; width: 100%; min-height: 85vh; display: flex; align-items: center; background-size: cover; background-position: center; transition: 0.8s; }
    .hero-wrapper::before { content: ''; position: absolute; inset: 0; background: linear-gradient(to left, rgba(10,10,10,0.6), var(--bg)), linear-gradient(to top, var(--bg) 5%, transparent); }
    
    .hero-content { position: relative; z-index: 10; display: flex; gap: 50px; padding: 120px 20px 60px; }
    
    /* Poster & Favorite Button */
    .poster-area { flex: 0 0 300px; position: relative; transition: var(--transition); }
    .poster-area:hover { transform: translateY(-10px); }
    .poster-area img { width: 100%; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.9); border: 1px solid rgba(255,255,255,0.1); transition: var(--transition); }
    .poster-area:hover img { border-color: var(--main); box-shadow: 0 25px 60px rgba(212, 255, 0, 0.15); }
    
    .fav-btn-abs {
        position: absolute; top: 15px; left: 15px; z-index: 20; background: rgba(0,0,0,0.6);
        width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        cursor: pointer; border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(8px); transition: var(--transition);
    }
    .fav-btn-abs:hover { transform: scale(1.15) rotate(5deg); background: var(--main); border-color: var(--main); }
    .fav-btn-abs:hover i { color: #000; }
    .fav-btn-abs i { font-size: 1.4rem; color: #fff; transition: var(--transition); }
    .fav-btn-abs.active i { color: #ff0000; }

    .info-area h1 { font-size: 3.8rem; font-weight: 900; margin: 0; color: #fff; text-shadow: 2px 2px 10px rgba(0,0,0,0.5); }
    .info-area h2 { font-size: 1.5rem; color: #888; margin: -5px 0 15px; }
    
    .meta-line { display: flex; gap: 15px; align-items: center; margin-bottom: 20px; font-weight: bold; }
    .rating-badge { background: var(--main); color: #000; padding: 2px 12px; border-radius: 6px; }
    
    .genre-pill { background: rgba(212, 255, 0, 0.08); color: var(--main); padding: 5px 16px; border-radius: 50px; font-size: 0.85rem; border: 1px solid rgba(212, 255, 0, 0.15); margin-left: 8px; transition: var(--transition); }
    .genre-pill:hover { background: var(--main); color: #000; }

    /* Cast Pill Styles & Hover */
    .cast-pill { 
        background: rgba(255, 255, 255, 0.05); 
        color: #bbb; 
        padding: 6px 15px; 
        border-radius: 10px; 
        font-size: 0.9rem; 
        border: 1px solid rgba(255,255,255,0.08); 
        margin-left: 8px; 
        display: inline-block; 
        margin-bottom: 10px; 
        transition: all 0.3s ease;
        cursor: default;
    }
    .cast-pill:hover { 
        background: rgba(255, 255, 255, 0.12); 
        color: #fff; 
        border-color: var(--main); 
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    /* Description & Read More Button */
    .desc-container { margin-top: 15px; max-width: 800px; }
    .description { font-size: 1.1rem; line-height: 1.8; color: #ccc; margin-bottom: 10px; overflow: hidden; transition: max-height 0.5s ease; }
    .description.collapsed { max-height: 80px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    
    .read-more-btn { 
        background: none; border: none; color: var(--main); cursor: pointer; 
        font-weight: bold; font-family: 'Cairo'; font-size: 0.95rem; padding: 0;
        display: none; align-items: center; gap: 5px; transition: 0.3s;
    }
    .read-more-btn:hover { letter-spacing: 0.5px; opacity: 0.8; }

    /* Buttons */
    .btn-main-action { background: var(--main); color: #000; padding: 18px 45px; border-radius: 15px; border: none; font-weight: 900; cursor: pointer; font-size: 1.1rem; box-shadow: 0 10px 20px rgba(212, 255, 0, 0.15); transition: var(--transition); display: inline-flex; align-items: center; justify-content: center; gap: 10px;
        min-width: 180px; height: 55px; text-decoration: none; cursor: pointer; font-family: 'Cairo';}
    .btn-main-action:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(212, 255, 0, 0.3); }
    
    .btn-secondary-action { background: rgba(255,255,255,0.08); color: #fff; padding: 18px 30px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1); cursor: pointer; backdrop-filter: blur(10px); transition: var(--transition); font-family: 'Cairo'; font-weight: 900; font-size: 1.1rem; display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-width: 180px; height: 55px; text-decoration: none; cursor: pointer;}
    .btn-secondary-action:hover { background: rgba(255,255,255,0.15); transform: translateY(-5px); }

    /* --- تجميل سطور الحلقات --- */
    .episodes-list { display: flex; flex-direction: column; gap: 15px; margin-top: 25px; }
    .episode-row { 
        background: linear-gradient(145deg, #1a1a1a, #111); 
        padding: 20px 35px; border-radius: 18px; 
        display: flex; align-items: center; justify-content: space-between; 
        border: 1px solid rgba(255,255,255,0.03); 
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative; overflow: hidden;
    }
    .episode-row::before {
        content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%;
        background: var(--main); transform: scaleY(0); transition: 0.3s;
    }
    .episode-row:hover { 
        border-color: rgba(212, 255, 0, 0.3); 
        background: #1c1c1c; 
        transform: scale(1.015) translateX(-8px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    }
    .episode-row:hover::before { transform: scaleY(1); }
    
    .ep-info-box { display: flex; align-items: center; gap: 25px; }
    .ep-number-circle {
        width: 45px; height: 45px; background: rgba(212, 255, 0, 0.1);
        border: 1px solid rgba(212, 255, 0, 0.2); border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        color: var(--main); font-weight: 900; font-size: 1.2rem; transition: 0.3s;
    }
    .episode-row:hover .ep-number-circle { background: var(--main); color: #000; transform: rotate(-10deg); }
    
    .ep-details { display: flex; flex-direction: column; gap: 4px; }
    .ep-title { font-weight: 800; font-size: 1.15rem; color: #fff; transition: 0.3s; }
    .episode-row:hover .ep-title { color: var(--main); }
    
    .ep-meta-tag { font-size: 0.85rem; color: #666; display: flex; align-items: center; gap: 6px; }
    
    .ep-row-actions { display: flex; gap: 12px; }
    .ep-btn { 
        padding: 12px 25px; border-radius: 12px; border: none; cursor: pointer; 
        font-weight: bold; font-size: 0.95rem; text-decoration: none; 
        display: flex; align-items: center; gap: 10px; transition: var(--transition); 
    }
    .btn-play { background: var(--main); color: #000; box-shadow: 0 4px 15px rgba(212, 255, 0, 0.1); }
    .btn-play:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(212, 255, 0, 0.3); }
    
    .btn-down { background: rgba(255,255,255,0.05); color: #ccc; border: 1px solid rgba(255,255,255,0.1); }
    .btn-down:hover { background: #fff; color: #000; border-color: #fff; transform: translateY(-3px); }

    /* Tabs */
    .tab-btn { background: none; border: none; color: #666; padding: 15px 35px; cursor: pointer; font-size: 1.2rem; font-weight: bold; transition: var(--transition); position: relative; }
    .tab-btn.active { color: var(--main); }
    .tab-btn.active::after { content: ''; position: absolute; bottom: 0; right: 20%; left: 20%; height: 3px; background: var(--main); border-radius: 10px; box-shadow: 0 0 15px var(--main); }

    /* Trailer Modal CSS */
    .modal { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.9); backdrop-filter: blur(10px); align-items: center; justify-content: center; padding: 20px; }
    .modal-content { position: relative; width: 100%; max-width: 1000px; aspect-ratio: 16/9; }
    .close-modal { position: absolute; top: -50px; left: 0; color: #fff; font-size: 2rem; cursor: pointer; transition: 0.3s; }
    .close-modal:hover { color: var(--main); transform: rotate(90deg); }
    /* تنسيقات الموبايل - Mobile Styles */
@media (max-width: 768px) {
    /* ترتيب رأس الصفحة */
    .hero-content { 
        flex-direction: column !important; 
        text-align: center !important; 
        padding-top: 60px !important; 
        gap: 30px !important; 
    }

    /* تصغير وحيادية البوستر */
    .poster-area { 
        flex: 0 0 auto !important; 
        width: 200px !important; 
        margin: 0 auto !important; 
    }

    /* العناوين */
    .info-area h1 { 
        font-size: 2.2rem !important; 
        line-height: 1.2;
    }
    
    .info-area h2 { 
        font-size: 1.2rem !important; 
    }

    /* جعل الأزرار تأخذ العرض الكامل لسهولة الضغط */
    .cta-btns {
        flex-direction: column !important;
        width: 100%;
    }

    .btn-action, .btn-main-action, .btn-secondary-action { 
        width: 100% !important; 
        min-width: unset !important;
        margin: 5px 0;
    }

    /* تنسيق الحلقات في الموبايل */
    .episode-row {
        flex-direction: column !important;
        gap: 15px !important;
        text-align: center !important;
        padding: 15px !important;
    }

    .ep-info-box, .ep-info {
        flex-direction: column !important;
        gap: 10px !important;
    }

    .ep-row-actions, .ep-btns {
        width: 100% !important;
        flex-direction: row !important; /* جعل زر المشاهدة والتحميل بجانب بعضهما */
        gap: 10px !important;
    }

    .ep-btn, .btn-ep {
        flex: 1 !important; /* توزيع المساحة بالتساوي بين الزرين */
        justify-content: center !important;
        padding: 10px !important;
        font-size: 0.9rem !important;
    }

    /* تنسيق المواسم (Tabs) لتكون قابلة للتمرير العرضي */
    .season-tabs {
        display: flex !important;
        overflow-x: auto !important;
        white-space: nowrap !important;
        padding-bottom: 10px !important;
        justify-content: flex-start !important;
        -webkit-overflow-scrolling: touch;
    }

    .tab-btn {
        padding: 10px 20px !important;
        font-size: 1rem !important;
        flex: 0 0 auto !important;
    }

    /* المشغل (Player) */
    .player-wrapper {
        border-radius: 15px !important; /* حواف أنعم على الموبايل */
    }
}
    
    .movies-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
    gap:20px;
    margin-top:20px;
}
</style>

<div class="hero-wrapper" style="background-image: url('<?php echo $series['backdrop_path']; ?>')">
    <div class="container hero-content">
        <div class="poster-area">
            <div class="fav-btn-abs <?php echo ($series['is_favorite'] > 0) ? 'active' : ''; ?>" onclick="toggleFav(this, <?php echo $series['id']; ?>)">
                <i class="<?php echo ($series['is_favorite'] > 0) ? 'fas fa-heart' : 'far fa-heart'; ?>"></i>
            </div>
            <img loading="lazy" src="<?= $series['poster_path']; ?>" alt="<?= $series['title_ar']; ?>">
        </div>
        
        <div class="info-area">
            <div class="meta-line">
                <span class="rating-badge">⭐ <?php echo number_format($series['rating'],1); ?></span>
                <span><?php echo $series['release_year']; ?></span>
                <span style="color: #666;">|</span>
                <span><?php echo count($seasons); ?> مواسم</span>
            </div>
            <h1><?= $series['title_ar']; ?> (مشاهدة اون لاين)</h1>
            <h2><?php echo $series['title_en']; ?></h2>
            
            <div style="margin: 15px 0;">
                <?php foreach($genres_list as $g): ?>
                    <span class="genre-pill"><?php echo trim($g); ?></span>
                <?php endforeach; ?>
            </div>

            <div class="desc-container">
                <p id="mainDesc" class="description collapsed">
    <?= $series['overview']; ?> مشاهدة جميع الحلقات مترجمة بجودة عالية بدون تقطيع.<?php echo $series['overview']; ?></p>
                <button id="readMoreBtn" class="read-more-btn" onclick="toggleDescription()">قراءة المزيد <i class="fas fa-chevron-down"></i></button>
            </div>

            <div class="cta-btns" style="margin-top:35px; display: flex; gap: 20px; align-items: center;">
                <button onclick="scrollToEpisodes()" class="btn-main-action">▶ مشاهدة الآن</button>
                <?php if($series['trailer_url']): ?>
                <button onclick="openTrailer('<?php echo $series['trailer_url']; ?>')" class="btn-secondary-action">
                   <i class="fab fa-youtube" style="color: #ff0000; margin-left: 8px;"></i> الإعلان الرسمي
                </button>
                <?php endif; ?>
            </div>

            <?php if(!empty($cast_list)): ?>
            <div style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;">
                <span style="color: #666; font-size: 0.95rem; display: block; margin-bottom: 12px; font-weight: bold;">طاقم العمل:</span>
                <div class="cast-container">
                    <?php foreach($cast_list as $actor): ?>
                        <span class="cast-pill"><?php echo trim($actor); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<div style="display:none">

<h1><?= $series['title_ar'] ?> مشاهدة اون لاين جميع المواسم</h1>

<p>
شاهد مسلسل <?= $series['title_ar'] ?> مترجم كامل بجودة عالية على Flexor.
استمتع بجميع الحلقات بدون تقطيع وبأفضل السيرفرات السريعة.
</p>

<p>
عدد المواسم: <?= count($seasons) ?>  
التصنيف: <?= implode(', ', $genres_list) ?>  
سنة الإنتاج: <?= $series['release_year'] ?>
</p>

</div>

<div class="container" style="margin-top: 50px;">
    <div id="playerSection" style="display:none; padding-bottom: 50px;">
        <h2 id="playingTitle" style="color:var(--main); margin-bottom:25px; border-right: 4px solid var(--main); padding-right: 15px;"></h2>
        <div id="serverList" class="meta-line" style="margin-bottom:20px; flex-wrap: wrap; gap: 10px;"></div>
        <div style="position:relative; padding-bottom:56.25%; height:0; border-radius:25px; overflow:hidden; background:#000; border: 1px solid #333; box-shadow: 0 30px 60px rgba(0,0,0,0.5);">
            <div id="videoFrame" style="position:absolute; inset:0;"></div>
        </div>
    </div>

    <div id="episodesArea">
        <h2 style="display:none">
جميع حلقات مسلسل <?= $series['title_ar'] ?> كاملة
</h2>
        <div class="season-tabs">
            <?php foreach($seasons as $index => $s): ?>
                <button class="tab-btn <?php echo $index==0?'active':''; ?>" onclick="switchSeason(this, 's-<?php echo $s['id']; ?>')">
                    الموسم <?php echo $s['season_number']; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach($seasons as $index => $s): ?>
        <div id="s-<?php echo $s['id']; ?>" class="season-content" style="display: <?php echo $index==0?'block':'none'; ?>">
            <div class="episodes-list">
                <?php foreach($s['episodes'] as $e): ?>
                <div class="episode-row">
                    <div class="ep-info-box">
                        <div class="ep-number-circle"><?php echo $e['episode_number']; ?></div>
                        <div class="ep-details">
                            <span class="ep-title">الحلقة <?php echo $e['episode_number']; ?></span>
                            <span class="ep-meta-tag">
                                <i class="far fa-clock"></i> <?php echo $e['duration'] ?? '45'; ?> دقيقة
                                <span style="margin: 0 5px; color: #333;">•</span>
                                <i class="fas fa-closed-captioning"></i> مترجم HD
                            </span>
                        </div>
                    </div>
                    <div class="ep-row-actions">
                        <button class="ep-btn btn-play" onclick="initPlayer('<?php echo base64_encode($e['watch_link']); ?>', 'الحلقة <?php echo $e['episode_number']; ?>')">
                            <i class="fas fa-play-circle"></i> مشاهدة
                        </button>
                        <a href="<?php echo $e['download_links']; ?>" target="_blank" class="ep-btn btn-down">
                            <i class="fas fa-download"></i> تحميل
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="trailerModal" class="modal" onclick="closeTrailer()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="close-modal" onclick="closeTrailer()"><i class="fas fa-times"></i></div>
        <div id="trailerBox" style="width:100%; height:100%; border-radius: 20px; overflow: hidden; background:#000;"></div>
    </div>
</div>


<div class="container" style="margin-top:50px;">
    <h2 class="section-title">🎬 أعمال مشابهة</h2>

    <div class="movies-grid">
        <?php while($sim = mysqli_fetch_assoc($similar_result)): ?>
            <?php 
            // 🔥 مهم: نحدد النوع عشان الكارت يشتغل صح
            $sim['content_type'] = 'series';
            renderMovieCard($sim); 
            ?>
        <?php endwhile; ?>
    </div>
</div>

<script>
window.onload = function() {
    const desc = document.getElementById('mainDesc');
    const btn = document.getElementById('readMoreBtn');
    if (desc && desc.scrollHeight > 80) { 
        btn.style.display = 'flex'; 
    }
};

document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") closeTrailer();
});

function toggleDescription() {
    const desc = document.getElementById('mainDesc');
    const btn = document.getElementById('readMoreBtn');
    desc.classList.toggle('collapsed');
    
    if (desc.classList.contains('collapsed')) {
        btn.innerHTML = 'قراءة المزيد <i class="fas fa-chevron-down"></i>';
    } else {
        btn.innerHTML = 'عرض أقل <i class="fas fa-chevron-up"></i>';
    }
}

function openTrailer(url) {
    let id = "";
    if(url.includes('v=')) {
        id = url.split('v=')[1].split('&')[0];
    } else {
        id = url.split('/').pop();
    }
    
    document.getElementById('trailerModal').style.display = 'flex';
    document.getElementById('trailerBox').innerHTML = `<iframe width="100%" height="100%" src="https://www.youtube.com/embed/${id}?autoplay=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
}

function closeTrailer() {
    document.getElementById('trailerModal').style.display = 'none';
    document.getElementById('trailerBox').innerHTML = '';
}

function initPlayer(watchLinks64, title) {
    const playerSection = document.getElementById('playerSection');
    playerSection.style.display = 'block';
    document.getElementById('playingTitle').innerText = title;
    const links = atob(watchLinks64).split('\n').filter(l => l.trim() !== "");
    const serverList = document.getElementById('serverList');
    serverList.innerHTML = '';
    
    links.forEach((url, i) => {
        const btn = document.createElement('div');
        btn.className = `genre-pill server-tag ${i===0?'active':''}`;
        btn.style.cursor = 'pointer';
        btn.innerText = `سيرفر ${i+1}`;
        btn.onclick = () => loadServer(url, btn);
        serverList.appendChild(btn);
    });
    loadServer(links[0]);
    playerSection.scrollIntoView({ behavior: 'smooth' });
}

function loadServer(url, btn = null) {
    if(btn) {
        document.querySelectorAll('.server-tag').forEach(b => {
            b.style.background = 'rgba(212, 255, 0, 0.08)';
            b.style.color = 'var(--main)';
        });
        btn.style.background = 'var(--main)';
        btn.style.color = '#000';
    }
    document.getElementById('videoFrame').innerHTML = `<iframe src="${url}" width="100%" height="100%" allowfullscreen frameborder="0"></iframe>`;
}

function switchSeason(btn, id) {
    document.querySelectorAll('.season-content').forEach(c => c.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id).style.display = 'block';
    btn.classList.add('active');
}

function scrollToEpisodes() {
    document.getElementById('episodesArea').scrollIntoView({ behavior: 'smooth' });
}

function toggleFav(element, seriesId) {
    const userId = <?php echo $user_id; ?>;
    if (userId === 0) { alert('يجب تسجيل الدخول أولاً'); return; }

    fetch('/auth/toggle_favorite.php?series_id=' + seriesId)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'added') {
            element.classList.add('active');
            element.querySelector('i').className = 'fas fa-heart';
        } else if (data.status === 'removed') {
            element.classList.remove('active');
            element.querySelector('i').className = 'far fa-heart';
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>

<?php include '../../footer.php'; ?>

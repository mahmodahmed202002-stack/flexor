<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تضمين الملفات الأساسية
include '../../includes/db.php'; 

if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header("Location: ../../index.php");
    exit();
}

$slug = mysqli_real_escape_string($conn, $_GET['slug']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// جلب بيانات الفيلم
$query = "SELECT m.*, 
          (SELECT COUNT(*) FROM favorites f WHERE f.movie_id = m.id AND f.user_id = '$user_id') as is_favorite 
          FROM movies m 
          WHERE m.slug = '$slug' LIMIT 1";

$result = mysqli_query($conn, $query);
$movie = mysqli_fetch_assoc($result);

if (!$movie) {
    $page_title = "الفيلم غير موجود | Flexor";
    $page_desc  = "لم يتم العثور على بيانات الفيلم على Flexor";
    $page_img   = "https://flexor.gt.tc/public/logo.png";

    include '../../includes/header.php';

    echo "<div class='container text-center' style='margin-top:150px; color:white;'><h2>عذراً، لم يتم العثور على بيانات الفيلم.</h2></div>";
    include '../../footer.php';
    exit();
}
$page_title = $movie['title_ar'] . " مشاهدة اون لاين | Flexor";
$page_desc  = $movie['overview'];
$page_img   = $movie['poster_path'];

include '../../includes/header.php';

$genres_list = explode(',', $movie['genres']);
$cast_list = !empty($movie['cast_members']) ? explode(',', $movie['cast_members']) : [];
include $_SERVER['DOCUMENT_ROOT'].'/includes/components/movie-card.php';
$similar_query = "
SELECT m.*, 
(SELECT COUNT(*) FROM favorites f WHERE f.movie_id = m.id AND f.user_id = '$user_id') as is_favorite
FROM movies m
WHERE m.id != '".$movie['id']."'
AND m.genres LIKE '%".$genres_list[0]."%'
ORDER BY m.rating DESC
LIMIT 12
";

$similar_result = mysqli_query($conn, $similar_query);
?>

<script type="application/ld+json">
{
 "@context": "https://schema.org",
 "@type": "Movie",
 "name": "<?= addslashes($movie['title_ar']) ?>",
 "alternateName": "<?= addslashes($movie['title_en']) ?>",
 "image": "<?= $movie['poster_path'] ?>",
 "description": "<?= addslashes($movie['overview']) ?>",
 "datePublished": "<?= $movie['release_year'] ?>",
 "genre": "<?= implode(',', $genres_list) ?>",

 "aggregateRating": {
   "@type": "AggregateRating",
   "ratingValue": "<?= $movie['rating'] ?>",
   "bestRating": "10",
   "ratingCount": "<?= rand(50,500) ?>"
 },

 "interactionStatistic": {
   "@type": "InteractionCounter",
   "interactionType": { "@type": "WatchAction" },
   "userInteractionCount": "<?= rand(100,2000) ?>"
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
    
    /* Poster Area Hover */
    .poster-area { flex: 0 0 300px; position: relative; transition: var(--transition); }
    .poster-area img { width: 100%; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.9); border: 1px solid rgba(255,255,255,0.1); transition: var(--transition); }
    .poster-area:hover { transform: scale(1.02); }
    
    .fav-btn-abs {
        position: absolute; top: 15px; left: 15px; z-index: 20; background: rgba(0,0,0,0.6);
        width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        cursor: pointer; border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(8px); transition: var(--transition);
    }
    .fav-btn-abs:hover { transform: scale(1.1); background: var(--main); border-color: var(--main); }
    .fav-btn-abs:hover i { color: #000; }
    .fav-btn-abs.active i { color: #ff0000; }

    /* Info Area */
    .info-area h1 { font-size: 3.8rem; font-weight: 900; margin: 0; color: #fff; text-shadow: 2px 2px 10px rgba(0,0,0,0.5); }
    .info-area h2 { font-size: 1.5rem; color: #888; margin: -5px 0 15px; }
    
    .meta-line { display: flex; gap: 15px; align-items: center; margin-bottom: 20px; font-weight: bold; }
    .rating-badge { background: var(--main); color: #000; padding: 2px 12px; border-radius: 6px; }
    
    /* Genre Pill Hover */
    .genre-pill { 
        background: rgba(212, 255, 0, 0.08); color: var(--main); padding: 5px 16px; border-radius: 50px; 
        font-size: 0.85rem; border: 1px solid rgba(212, 255, 0, 0.15); margin-left: 8px; 
        transition: var(--transition); display: inline-block; cursor: pointer;
    }
    .genre-pill:hover { background: var(--main); color: #000; }

    /* Description */
    .description { font-size: 1.1rem; line-height: 1.8; color: #ccc; margin-bottom: 10px; overflow: hidden; transition: max-height 0.5s ease; }
    .description.collapsed { max-height: 80px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .read-more-btn { background: none; border: none; color: var(--main); cursor: pointer; font-weight: bold; font-family: 'Cairo'; font-size: 0.95rem; display: none; }

    /* Buttons Hover */
    .cta-btns { margin-top: 35px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
    .btn-action { 
        display: inline-flex; align-items: center; justify-content: center; gap: 10px;
        min-width: 180px; height: 55px; border-radius: 15px; font-weight: 900; font-size: 1.1rem; 
        transition: var(--transition); text-decoration: none; border: none; cursor: pointer; font-family: 'Cairo';
    }

    .btn-play { background: var(--main); color: #000; }
    .btn-play:hover { transform: translateY(-5px); background: #fff; }

    .btn-download { background: #fff; color: #000; }
    .btn-download:hover { transform: translateY(-5px); background: var(--main); }

    .btn-trailer { background: rgba(255,255,255,0.08); color: #fff; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px); }
    .btn-trailer:hover { background: rgba(255,255,255,0.15); transform: translateY(-5px); border-color: var(--main); }

    /* Player */
    .section-title { color: var(--main); margin-bottom: 25px; border-right: 4px solid var(--main); padding-right: 15px; font-weight: 800; }
    .player-wrapper { position:relative; padding-bottom:56.25%; height:0; border-radius:25px; overflow:hidden; background:#000; border: 1px solid #333; }
    .player-wrapper iframe { position:absolute; inset:0; width: 100%; height: 100%; }
    
    /* Cast Pill Hover */
    .cast-pill { background: rgba(255, 255, 255, 0.05); color: #bbb; padding: 6px 15px; border-radius: 10px; font-size: 0.9rem; margin-left: 8px; display: inline-block; margin-bottom: 10px; transition: 0.3s; border: 1px solid transparent; }
    .cast-pill:hover { border-color: var(--main); color: #fff; transform: scale(1.05); }

    /* Modal - الثبات هنا أساسي */
    .modal { display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.95); backdrop-filter: blur(10px); align-items: center; justify-content: center; padding: 20px; }
    .modal-content { position: relative; width: 90%; max-width: 1000px; aspect-ratio: 16/9; z-index: 10000; }
    .close-modal { position: absolute; top: -50px; left: 0; color: #fff; font-size: 2.5rem; cursor: pointer; transition: 0.3s; }
    .close-modal:hover { color: var(--main); transform: rotate(90deg); }

    @media (max-width: 768px) {
        .hero-content { flex-direction: column; text-align: center; padding-top: 60px; }
        .poster-area { flex: 0 0 auto; width: 200px; margin: 0 auto; }
        .info-area h1 { font-size: 2.2rem; }
        .btn-action { width: 100%; }
    }
    
.movies-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));
    gap:20px;
    padding: 10px 0;
}
    
    .movie-card{
    text-decoration:none;
    color:#fff;
}

    .movie-card-parent{
    width:100%;
    height:100%;
}
    
    .card-main-content{
    width:100%;
    height:100%;
}
    
.movie-card img{
    width:100%;
    border-radius:10px;
}

.card-title{
    font-size:14px;
    margin-top:5px;
}
</style>

<div class="hero-wrapper" style="background-image: url('<?php echo $movie['backdrop_path']; ?>')">
    <div class="container hero-content">
        <div class="poster-area">
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="fav-btn-abs <?php echo ($movie['is_favorite'] > 0) ? 'active' : ''; ?>" onclick="toggleFavoriteLocal(this, <?php echo $movie['id']; ?>)">
                <i class="<?php echo ($movie['is_favorite'] > 0) ? 'fas fa-heart' : 'far fa-heart'; ?>"></i>
            </div>
            <?php endif; ?>
            <img loading="lazy" src="<?= $movie['poster_path']; ?>" alt="<?= $movie['title_ar']; ?>">
        </div>
        
        <div class="info-area">
            <div class="meta-line">
                <span class="rating-badge">⭐ <?php echo number_format($movie['rating'], 1); ?></span>
                <span><?php echo $movie['release_year']; ?></span>
                <span style="color: #666;">|</span>
                <span><?php echo $movie['duration']; ?></span>
            </div>
            <h1><?= $movie['title_ar']; ?> (مشاهدة اون لاين)</h1>
            <h2><?php echo $movie['title_en']; ?></h2>
            
            <div style="margin: 15px 0;">
                <?php foreach($genres_list as $g): ?>
                    <span class="genre-pill"><?php echo trim($g); ?></span>
                <?php endforeach; ?>
            </div>

            <div class="desc-container">
                <p id="movie-desc" class="description collapsed"><?php echo $movie['overview']; ?></p>
                <button id="read-more-btn" class="read-more-btn" onclick="toggleDescription()">قراءة المزيد <i class="fas fa-chevron-down"></i></button>
            </div>

            <div class="cta-btns">
                <a href="#player" class="btn-action btn-play">▶ مشاهدة الآن</a>
                
                <?php 
                if (!empty($movie['download_links'])) {
                    $links = explode("\n", $movie['download_links']);
                    foreach ($links as $index => $link) {
                        if (!empty(trim($link))) {
                            echo '<a href="'.trim($link).'" target="_blank" class="btn-action btn-download">
                                    <i class="fas fa-download"></i> تحميل '.($index > 0 ? '#'.($index + 1) : '').'
                                  </a>';
                        }
                    }
                }
                ?>

                <?php if(!empty($movie['trailer_url'])): ?>
                <button onclick="openTrailer('<?php echo $movie['trailer_url']; ?>')" class="btn-action btn-trailer">
                   <i class="fab fa-youtube" style="color: #ff0000;"></i> الإعلان الرسمي
                </button>
                <?php endif; ?>
            </div>

            <?php if(!empty($cast_list)): ?>
            <div style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;">
                <span style="color: #666; font-size: 0.95rem; display: block; margin-bottom: 12px; font-weight: bold;">طاقم العمل:</span>
                <?php foreach($cast_list as $actor): ?>
                    <span class="cast-pill"><?php echo trim($actor); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="display:none">

<h1><?= $movie['title_ar'] ?> مشاهدة اون لاين</h1>

<p>
شاهد فيلم <?= $movie['title_ar'] ?> مترجم بجودة عالية على Flexor.
استمتع بأفضل تجربة مشاهدة بدون تقطيع وسيرفرات قوية.
</p>

<p>
تصنيف الفيلم: <?= implode(', ', $genres_list) ?>  
تاريخ الإصدار: <?= $movie['release_year'] ?>
</p>

</div>

<div class="container" style="margin-top: 50px;">
    <div id="player" style="margin-bottom: 60px;">
        <h2 class="section-title">مشاهدة الفيلم</h2>
        <div class="player-wrapper">
            <?php 
            $watch_url = $movie['watch_link'];
            if (strpos($watch_url, 'youtube.com') !== false || strpos($watch_url, 'youtu.be') !== false) {
                preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $watch_url, $match);
                $youtube_id = isset($match[1]) ? $match[1] : '';
                echo '<iframe src="https://www.youtube.com/embed/'.$youtube_id.'?rel=0" frameborder="0" allowfullscreen></iframe>';
            } else {
                echo '<iframe src="'.$watch_url.'" frameborder="0" allowfullscreen></iframe>';
            }
            ?>
        </div>
    </div>
</div>

<div id="trailerModal" class="modal" onclick="closeTrailer()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <span class="close-modal" onclick="closeTrailer()">&times;</span>
        <div id="trailerBox" style="width:100%; height:100%; border-radius: 20px; overflow: hidden; background:#000;"></div>
    </div>
</div>

<div class="container" style="margin-top:50px;">
    <h2 class="section-title">🎬 أعمال مشابهة</h2>

    <div class="movies-grid">
<?php while($sim = mysqli_fetch_assoc($similar_result)): ?>
    <?php renderMovieCard($sim); ?>
<?php endwhile; ?>
</div>
</div>

<script>
function toggleDescription() {
    const desc = document.getElementById('movie-desc');
    const btn = document.getElementById('read-more-btn');
    desc.classList.toggle('collapsed');
    btn.innerHTML = desc.classList.contains('collapsed') ? 'قراءة المزيد <i class="fas fa-chevron-down"></i>' : 'عرض أقل <i class="fas fa-chevron-up"></i>';
}

document.addEventListener('DOMContentLoaded', function() {
    const desc = document.getElementById('movie-desc');
    if (desc && desc.scrollHeight > 80) {
        document.getElementById('read-more-btn').style.display = "inline-flex";
    }
});

function openTrailer(url) {
    let id = "";
    if(url.includes('v=')) { 
        id = url.split('v=')[1].split('&')[0]; 
    } else if(url.includes('youtu.be/')) {
        id = url.split('youtu.be/')[1].split('?')[0];
    } else {
        id = url.split('/').pop();
    }
    
    const modal = document.getElementById('trailerModal');
    const box = document.getElementById('trailerBox');
    
    modal.style.display = 'flex';
    box.innerHTML = `<iframe width="100%" height="100%" src="https://www.youtube.com/embed/${id}?autoplay=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
}

function closeTrailer() {
    document.getElementById('trailerModal').style.display = 'none';
    document.getElementById('trailerBox').innerHTML = '';
}

function toggleFavoriteLocal(btn, movieId) {
    fetch('../../auth/toggle_favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'movie_id=' + movieId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'added') {
            btn.classList.add('active');
            btn.querySelector('i').className = 'fas fa-heart';
        } else {
            btn.classList.remove('active');
            btn.querySelector('i').className = 'far fa-heart';
        }
    });
}

document.addEventListener('keydown', e => { 
    if (e.key === "Escape") closeTrailer(); 
});
</script>

<?php include '../../footer.php'; ?>

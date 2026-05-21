<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$u_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// ✅ استعلام جلب البيانات (أفلام + مسلسلات + قنوات)
$sql = "
SELECT * FROM (
    /* 1. الأفلام */
    SELECT 
        m.id,
        CONVERT(m.title_ar USING utf8mb4) COLLATE utf8mb4_general_ci AS title_ar,
        CONVERT(m.title_en USING utf8mb4) COLLATE utf8mb4_general_ci AS title_en,
        CONVERT(m.overview USING utf8mb4) COLLATE utf8mb4_general_ci AS overview,
        CONVERT(m.backdrop_path USING utf8mb4) COLLATE utf8mb4_general_ci AS backdrop_path,
        CONVERT(m.poster_path USING utf8mb4) COLLATE utf8mb4_general_ci AS poster_path,
        m.main_cat_id,
        'movie' AS content_type,
        m.slug AS slug,
        IF(f.movie_id IS NOT NULL, 1, 0) AS is_favorite,
        m.created_at AS created_at
    FROM movies m
    LEFT JOIN favorites f 
        ON m.id = f.movie_id AND f.user_id = $u_id
    WHERE m.is_hero = 1

    UNION ALL

    /* 2. المسلسلات */
    SELECT 
        s.id,
        CONVERT(s.title_ar USING utf8mb4) COLLATE utf8mb4_general_ci AS title_ar,
        CONVERT(s.title_en USING utf8mb4) COLLATE utf8mb4_general_ci AS title_en,
        CONVERT(s.overview USING utf8mb4) COLLATE utf8mb4_general_ci AS overview,
        CONVERT(s.backdrop_path USING utf8mb4) COLLATE utf8mb4_general_ci AS backdrop_path,
        CONVERT(s.poster_path USING utf8mb4) COLLATE utf8mb4_general_ci AS poster_path,
        s.main_cat_id,
        'series' AS content_type,
        s.slug AS slug,
        IF(f.series_id IS NOT NULL, 1, 0) AS is_favorite,
        s.created_at AS created_at
    FROM series s
    LEFT JOIN favorites f 
        ON s.id = f.series_id AND f.user_id = $u_id
    WHERE s.is_hero = 1

    UNION ALL

    /* 3. القنوات */
    SELECT 
        c.id,
        CONVERT(c.channel_name USING utf8mb4) COLLATE utf8mb4_general_ci AS title_ar,
        CONVERT(c.channel_name USING utf8mb4) COLLATE utf8mb4_general_ci AS title_en,
        'شاهد الآن أحدث فيديوهات وبث القناة المباشر' AS overview,
        CONVERT(c.channel_cover USING utf8mb4) COLLATE utf8mb4_general_ci AS backdrop_path,
        CONVERT(c.channel_image USING utf8mb4) COLLATE utf8mb4_general_ci AS poster_path,
        0 AS main_cat_id,
        'channel' AS content_type,
        CONVERT(c.slug USING utf8mb4) COLLATE utf8mb4_general_ci AS slug,
        0 AS is_favorite,
        c.created_at AS created_at
    FROM yt_channels c
    WHERE c.is_hero = 1

) AS all_items
ORDER BY created_at DESC
LIMIT 5
";

$slider_query = $conn->query($sql);
if (!$slider_query) {
    die("<h2 style='color:red'>SQL ERROR:</h2>" . $conn->error);
}

$slider_items = [];
while ($row = $slider_query->fetch_assoc()) {
    $slider_items[] = [
        "id"          => $row['id'],
        "title_ar"    => $row['title_ar'] ?? '',
        "title_en"    => $row['title_en'] ?? '',
        "desc"        => $row['overview'] ?? '',
        "backdrop"    => !empty($row['backdrop_path']) ? $row['backdrop_path'] : '/img/no-backdrop.jpg',
        "poster"      => !empty($row['poster_path']) ? $row['poster_path'] : '/img/no-poster.jpg',
        "main_cat_id" => $row['main_cat_id'],
        "content_type"=> $row['content_type'],
        "slug"        => $row['slug'],
        "is_favorite" => (int)$row['is_favorite']
    ];
}
?>

<style>
/* --- تصميم الهيرو (مع تعديلات توحيد الخلفية) --- */
#heroSlider { position: relative; width: 100%; height: 90vh; background: #000; overflow: hidden; direction: rtl; }

.main-backdrop { 
    position: absolute; 
    inset: 0; 
    /* 🔥 تعديلات توحيد مقاس الخلفية */
    background-size: cover; 
    background-position: center center; 
    background-repeat: no-repeat;
    background-color: #000; 
    transition: opacity 0.6s ease-in-out; /* تنعيم حركة التغيير */
    z-index: 1; 
}

.slider-overlay { 
    position: absolute; inset: 0; 
    background: linear-gradient(to left, rgba(0,0,0,0.9) 10%, rgba(0,0,0,0.5) 40%, transparent 100%),
                linear-gradient(to top, rgba(0,0,0,1) 5%, transparent 30%);
    z-index: 2;
}

.slider-content { position: relative; z-index: 10; height: 100%; display: flex; flex-direction: column; justify-content: center; padding: 0 8%; max-width: 800px; color: #fff; }
.slider-content h1 { font-size: 3.5rem; margin-bottom: 10px; color: var(--main-color); text-shadow: 2px 2px 10px rgba(0,0,0,0.5); }
.slider-content h3 { font-size: 1.5rem; opacity: 0.8; margin-bottom: 20px; font-weight: 300; }

.desc-wrapper { margin-bottom: 30px; max-width: 600px; }
#sliderDesc { 
    font-size: 1.1rem; line-height: 1.5; color: #ccc; 
    margin: 0; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; 
    overflow: hidden; transition: 0.3s;
}
#sliderDesc.expanded { -webkit-line-clamp: unset; display: block; overflow: visible; }
.read-more-btn { background: none; border: none; color: var(--main-color); cursor: pointer; font-weight: bold; padding: 5px 0; font-size: 0.9rem; display: block; }

.hero-btns { display: flex; gap: 15px; align-items: center; }
.btn-watch { background: var(--main-color); color: #000; padding: 14px 45px; border-radius: 8px; font-weight: 800; text-decoration: none; transition: 0.3s; box-shadow: 0 4px 15px rgba(212, 255, 0, 0.2); }
.btn-watch:hover { transform: scale(1.05); }

.btn-favorite {
    background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2);
    width: 55px; height: 55px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; transition: 0.3s;
    display: flex; align-items: center; justify-content: center;
}
.btn-favorite[data-fav="true"] { background: #ff4757; border-color: #ff4757; color: #fff; }

.slider-thumbnails { position: absolute; bottom: 40px; left: 8%; display: flex; gap: 12px; z-index: 20; }
.thumb-card { width: 90px; height: 130px; opacity: 0.4; cursor: pointer; border-radius: 8px; overflow: hidden; border: 2px solid transparent; transition: 0.3s; }
.thumb-card img { width: 100%; height: 100%; object-fit: cover; }
.thumb-card.active { border-color: var(--main-color); opacity: 1; transform: translateY(-5px); }

/* --- Media Queries --- */
@media (max-width: 992px){
    #heroSlider{ height:auto; min-height:100vh; display:flex; flex-direction:column; justify-content:space-between; }
    .slider-content{ padding:120px 20px 20px 20px; text-align:center; align-items:center; }
    .slider-content h1{ font-size:2rem; }
    .slider-content h3{ font-size:1rem; margin-bottom:10px; }
    #sliderDesc{ font-size:0.9rem; -webkit-line-clamp:2; }
    .hero-btns{ justify-content:center; gap:10px; }
    .btn-watch{ padding:10px 20px; font-size:14px; }
    .btn-favorite{ width:45px; height:45px; }
    .slider-thumbnails{ position:relative; width:100%; display:flex; gap:6px; padding:15px 10px; margin-top:auto; background:rgba(0,0,0,0.6); }
    .thumb-card{ width:20%; height:100px; flex:none; border-radius:6px; overflow:hidden; opacity:0.7; }
    .thumb-card img{ width:100%; height:100%; object-fit:cover; }
    .thumb-card.active{ opacity:1; border:2px solid var(--main-color); transform:scale(1.05); }
}

@media (max-width: 480px){
    .thumb-card{ height:85px; }
    .slider-content h1{ font-size:1.5rem; }
    .slider-content h3{ font-size:0.9rem; }
    #sliderDesc{ font-size:0.8rem; }
}
</style>

<div id="heroSlider" onmouseenter="pauseAutoPlay()" onmouseleave="startAutoPlay()">
    <div class="main-backdrop" id="sliderBg"></div>
    <div class="slider-overlay"></div>

    <div class="slider-content">
        <h1 id="sliderTitleAr"></h1>
        <h3 id="sliderTitleEn"></h3>
        <div class="desc-wrapper">
            <p id="sliderDesc"></p>
            <button id="readMoreBtn" class="read-more-btn" onclick="toggleReadMore()">قراءة المزيد</button>
        </div>
        <div class="hero-btns">
            <a href="#" id="sliderBtn" class="btn-watch">شاهد الآن</a>
            <button id="favBtn" class="btn-favorite" data-fav="false" onclick="handleHeroFavorite(event)">❤</button>
        </div>
    </div>

    <div class="slider-thumbnails">
        <?php foreach($slider_items as $index => $item): ?>
            <div class="thumb-card <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeSlide(<?php echo $index; ?>)">
                <img src="<?php echo $item['poster']; ?>" alt="Poster">
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const sliderData = <?php echo json_encode($slider_items); ?>;
let currentIndex = 0;
let autoPlayTimer;

function updateSlider(index) {
    if (!sliderData[index]) return;
    const item = sliderData[index];

    const bg = document.getElementById('sliderBg');
    
    // 🔥 تأثير Fade Out لإخفاء القفزة بين الصور
    bg.style.opacity = '0'; 

    setTimeout(() => {
        bg.style.backgroundImage = `url('${item.backdrop}')`;
        // ✅ التأكد من تثبيت الحجم عند كل تغيير
        bg.style.backgroundSize = 'cover'; 
        bg.style.opacity = '1';
    }, 250);

    document.getElementById('sliderTitleAr').innerText = item.title_ar;
    document.getElementById('sliderTitleEn').innerText = item.title_en;
    document.getElementById('sliderDesc').innerText = item.desc;
    
    const desc = document.getElementById('sliderDesc');
    const btn = document.getElementById('readMoreBtn');
    desc.classList.remove('expanded');
    btn.innerText = "قراءة المزيد";

    // الروابط
    let finalLink = "";

if (item.content_type === 'channel') {
    finalLink = `watch-yt.php?slug=${item.slug}`;
} else if (item.content_type === 'series') {
    finalLink = `/series/${item.slug}`;
} else {
    finalLink = `/movie/${item.slug}`;
}
    document.getElementById('sliderBtn').href = finalLink;

    // زر المفضلة
    const favBtn = document.getElementById('favBtn');
    if (item.content_type === 'channel') {
        favBtn.style.display = 'none';
    } else {
        favBtn.style.display = 'flex';
        favBtn.setAttribute('data-fav', item.is_favorite ? 'true' : 'false');
    }

    document.querySelectorAll('.thumb-card').forEach((el, i) => {
        el.classList.toggle('active', i === index);
    });

    currentIndex = index;
}

function toggleReadMore() {
    const desc = document.getElementById('sliderDesc');
    const btn = document.getElementById('readMoreBtn');
    if (desc.classList.contains('expanded')) {
        desc.classList.remove('expanded');
        btn.innerText = "قراءة المزيد";
    } else {
        desc.classList.add('expanded');
        btn.innerText = "عرض أقل";
    }
}

function handleHeroFavorite(e) {
    const btn = e.currentTarget;
    const item = sliderData[currentIndex];
    if (item.content_type === 'channel') return;

    let url = 'auth/toggle_favorite.php?' + (item.content_type === 'series' ? 'series_id=' : 'movie_id=') + item.id;

    fetch(url)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'added') {
            item.is_favorite = 1;
            btn.setAttribute('data-fav', 'true');
        } else if (data.status === 'removed') {
            item.is_favorite = 0;
            btn.setAttribute('data-fav', 'false');
        }
    });
}

function changeSlide(index) {
    updateSlider(index);
}

function startAutoPlay() {
    autoPlayTimer = setInterval(() => {
        let nextIndex = (currentIndex + 1) % sliderData.length;
        updateSlider(nextIndex);
    }, 5000);
}

function pauseAutoPlay() {
    clearInterval(autoPlayTimer);
}

if (sliderData.length > 0) {
    updateSlider(0);
    startAutoPlay();
}
</script>
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include('../includes/db.php');

// 1. جلب بيانات المسلسل الأساسية
if(!isset($_GET['id'])) { die("ID المسلسل غير موجود"); }
$series_id = intval($_GET['id']);
$series_res = $conn->query("SELECT * FROM series WHERE id = $series_id");
$series = $series_res->fetch_assoc();

if(!$series) { die("المسلسل غير موجود في قاعدة البيانات"); }

// 2. جلب الأقسام الرئيسية
$main_cats = $conn->query("SELECT * FROM categories WHERE parent_id = 0 AND status = 'active' ORDER BY sort_order ASC");

// 3. جلب الأقسام الفرعية (للقسم المختار حالياً)
$sub_cats = $conn->query("SELECT * FROM categories WHERE parent_id = " . $series['main_cat_id']);

// 4. جلب المواسم والحلقات المخزنة
// ملاحظة: افترضنا أن لديك جدول seasons وجدول episodes مرتبطين بـ series_id
$db_seasons = $conn->query("SELECT * FROM seasons WHERE series_id = $series_id ORDER BY season_number ASC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل مسلسل: <?php echo $series['title_ar']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* سأستخدم نفس الستايل الذي أرسلته أنت تماماً لضمان التطابق */
        :root {
            --main-color: #d4ff00;
            --bg-dark: #0a0a0a;
            --card-bg: #141414;
            --input-bg: #000;
            --border-color: #222;
            --text-muted: #888;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body { margin: 0; padding: 25px; font-family: 'Cairo', sans-serif; background-color: var(--bg-dark); color: #fff; }
        h1 { font-size: 24px; font-weight: 900; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; }
        .tmdb-search-box { position: relative; margin-bottom: 30px; background: var(--card-bg); padding: 20px; border-radius: 15px; border: 1px solid var(--border-color); }
        #tmdb-search { width: 100%; padding: 15px 20px; background: var(--input-bg); border: 1px solid #333; color: #fff; border-radius: 10px; font-family: 'Cairo'; font-size: 16px; box-sizing: border-box; }
        #tmdb-results { position: absolute; top: 95%; left: 20px; right: 20px; background: #1a1a1a; border: 1px solid var(--main-color); z-index: 1000; max-height: 300px; overflow-y: auto; display: none; border-radius: 0 0 12px 12px; }
        .tmdb-item { display: flex; align-items: center; padding: 12px; cursor: pointer; border-bottom: 1px solid #222; }
        .tmdb-item:hover { background: #222; color: var(--main-color); }
        .tmdb-item img { width: 40px; height: 55px; margin-left: 15px; border-radius: 5px; object-fit: cover; }
        .admin-card { background: var(--card-bg); padding: 30px; border-radius: 18px; border: 1px solid var(--border-color); margin-bottom: 25px; }
        .form-section-title { color: var(--main-color); font-size: 16px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .form-group label { color: #ccc; font-weight: 600; font-size: 13px; }
        input, select, textarea { padding: 12px 15px; background: var(--input-bg); border: 1px solid #222; color: #fff; border-radius: 8px; font-family: 'Cairo'; }
        input:focus, select:focus, textarea:focus { border-color: var(--main-color); outline: none; }
        .hero-option { background: rgba(212, 255, 0, 0.03); padding: 18px 25px; border-radius: 12px; border: 1px dashed rgba(212, 255, 0, 0.2); display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; }
        .switch { position: relative; display: inline-block; width: 46px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background-color: #333; transition: .4s; border-radius: 34px; }
        .slider:before { content: ""; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--main-color); }
        input:checked + .slider:before { transform: translateX(22px); }
        .visual-assets-container { display: grid; grid-template-columns: 280px 1fr; gap: 30px; align-items: start; }
        .poster-main-wrapper { position: relative; background: #000; border-radius: 15px; overflow: hidden; border: 2px solid #222; aspect-ratio: 2/3; }
        .poster-main-wrapper img { width: 100%; height: 100%; object-fit: cover; }
        .backdrop-preview-box { width: 100%; height: 220px; border-radius: 15px; background: #000; border: 2px solid #222; overflow: hidden; position: relative; margin-bottom: 15px; }
        .backdrop-preview-box img { width: 100%; height: 100%; object-fit: cover; opacity: 0.7; }
        .season-entry-box { background: rgba(255,255,255,0.02); border: 1px solid #222; padding: 20px; border-radius: 15px; margin-bottom: 20px; border-right: 4px solid var(--main-color); }
        .episode-row { background: #0a0a0a; padding: 15px; border-radius: 10px; border: 1px solid #1a1a1a; margin-top: 10px; }
        .btn-add-action { background: transparent; color: var(--main-color); border: 1px dashed var(--main-color); padding: 8px 15px; border-radius: 8px; cursor: pointer; font-family: 'Cairo'; }
        .btn-submit { margin-top: 20px; width: 100%; background: var(--main-color); color: #000; padding: 18px; border: none; border-radius: 12px; font-weight: 900; cursor: pointer; font-size: 18px; }
        .gallery-grid img { width: 100%; height: 80px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid transparent; }
    </style>
</head>
<body>

<div class="main-content-inner">
    <h1>
        <i class="fas fa-edit" style="color:var(--main-color)"></i>
        تعديل مسلسل: <?php echo htmlspecialchars($series['title_ar']); ?>
    </h1>

    <div class="tmdb-search-box">
        <label>تحديث البيانات تلقائياً من TMDB (اختياري)</label>
        <input type="text" id="tmdb-search" placeholder="ابحث لتحديث البيانات..." autocomplete="off">
        <div id="tmdb-results"></div>
    </div>

    <form action="update-series-full.php" method="POST" id="seriesForm">
        <input type="hidden" name="series_id" value="<?php echo $series_id; ?>">
        <input type="hidden" id="tmdb_id" name="tmdb_id" value="<?php echo $series['tmdb_id']; ?>">
        <input type="hidden" name="content_type" value="series">

        <div class="hero-option">
            <div style="display:flex; align-items:center; gap:12px;">
                <i class="fas fa-bolt" style="color:var(--main-color)"></i>
                <span style="font-weight:700;">تثبيت في السلايدر الرئيسي (Hero Section)</span>
            </div>
            <label class="switch">
                <input type="checkbox" name="is_hero" value="1" <?php echo ($series['is_hero'] == 1) ? 'checked' : ''; ?>>
                <span class="slider"></span>
            </label>
        </div>

        <div class="admin-card">
            <div class="form-section-title"><i class="fas fa-info-circle"></i> البيانات الأساسية</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>الاسم بالعربية</label>
                    <input type="text" id="title_ar" name="title_ar" value="<?php echo htmlspecialchars($series['title_ar']); ?>" required>
                </div>
                <div class="form-group">
                    <label>الاسم الأصلي (English)</label>
                    <input type="text" id="title_en" name="title_en" value="<?php echo htmlspecialchars($series['title_en']); ?>" required>
                </div>
            </div>

            <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                <div class="form-group">
                    <label>سنة الإنتاج</label>
                    <input type="text" id="release_year" name="release_year" value="<?php echo $series['release_year']; ?>">
                </div>
                <div class="form-group">
                    <label>تقييم TMDB</label>
                    <input type="text" id="rating" name="rating" value="<?php echo $series['rating']; ?>">
                </div>
                <div class="form-group">
                    <label>القسم الرئيسي</label>
                    <select name="main_cat_id" onchange="loadSubCats(this.value)" required>
                        <option value="">اختر القسم...</option>
                        <?php while($c = $main_cats->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($series['main_cat_id'] == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo $c['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" id="sub_cat_area">
                <label>القسم الفرعي</label>
                <select name="sub_cat_id" id="sub_cat">
                    <?php while($sc = $sub_cats->fetch_assoc()): ?>
                        <option value="<?php echo $sc['id']; ?>" <?php echo ($series['sub_cat_id'] == $sc['id']) ? 'selected' : ''; ?>>
                            <?php echo $sc['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>التصنيفات (Genres)</label>
                <input type="text" id="genres" name="genres" value="<?php echo htmlspecialchars($series['genres']); ?>">
            </div>

            <div class="form-group">
                <label>قصة المسلسل</label>
                <textarea id="overview" name="overview" rows="4"><?php echo htmlspecialchars($series['overview']); ?></textarea>
            </div>
        </div>

        <div class="admin-card">
            <div class="form-section-title"><i class="fas fa-image"></i> الوسائط المرئية</div>
            <div class="visual-assets-container">
                <div class="poster-main-wrapper">
                    <img id="poster_preview" src="<?php echo $series['poster_path']; ?>">
                    <div class="poster-label">بوستر المسلسل الحالي</div>
                </div>

                <div class="backdrop-area">
                    <div class="backdrop-preview-box">
                        <img id="backdrop_preview" src="<?php echo $series['backdrop_path']; ?>">
                    </div>
                    <div class="form-grid" style="gap:15px;">
                        <div class="form-group">
                            <label>رابط البوستر</label>
                            <input type="text" id="poster_path" name="poster_path" value="<?php echo $series['poster_path']; ?>" oninput="document.getElementById('poster_preview').src = this.value">
                        </div>
                        <div class="form-group">
                            <label>رابط الخلفية</label>
                            <input type="text" id="backdrop_path" name="backdrop_path" value="<?php echo $series['backdrop_path']; ?>" oninput="document.getElementById('backdrop_preview').src = this.value">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Youtube Trailer ID</label>
                        <input type="text" id="trailer_url" name="trailer_url" value="<?php echo $series['trailer_url']; ?>">
                    </div>
                </div>
            </div>
            <div id="gallery-placeholder"></div>
        </div>

        <div class="admin-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div class="form-section-title" style="margin-bottom:0;"><i class="fas fa-list-ol"></i> إدارة المواسم والحلقات</div>
                <button type="button" class="btn-add-action" onclick="addNewSeason()"><i class="fas fa-plus"></i> إضافة موسم يدوي</button>
            </div>
            
            <div id="seasons-wrapper">
                </div>
        </div>

        <div class="admin-card">
            <div class="form-section-title"><i class="fas fa-users"></i> طاقم العمل</div>
            <textarea id="cast_members" name="cast_members" rows="2"><?php echo htmlspecialchars($series['cast_members']); ?></textarea>
        </div>

        <button type="submit" class="btn-submit">💾 حفظ التعديلات وتحديث المسلسل</button>
    </form>
</div>

<script>
const apiKey = '848951d3bbec3a919bf8bb3738a60628';
let seasonCounter = 0;

// دالة البحث من TMDB (نفسها في صفحة الإضافة)
document.getElementById('tmdb-search').addEventListener('input', function() {
    let query = this.value;
    if(query.length > 2) {
        fetch(`https://api.themoviedb.org/3/search/tv?api_key=${apiKey}&query=${query}&language=ar-SA`)
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.results.forEach(tv => {
                let poster = tv.poster_path ? `https://image.tmdb.org/t/p/w92${tv.poster_path}` : 'img/no-image.png';
                html += `<div class="tmdb-item" onclick="getSeriesDetails(${tv.id})"><img src="${poster}"><span>${tv.name}</span></div>`;
            });
            document.getElementById('tmdb-results').innerHTML = html;
            document.getElementById('tmdb-results').style.display = 'block';
        });
    } else { document.getElementById('tmdb-results').style.display = 'none'; }
});

async function getSeriesDetails(id) {
    document.getElementById('tmdb-results').style.display = 'none';
    const resAr = await fetch(`https://api.themoviedb.org/3/tv/${id}?api_key=${apiKey}&append_to_response=videos,images,credits&language=ar-SA&include_image_language=en,null`);
    const dataAr = await resAr.json();
    const resEn = await fetch(`https://api.themoviedb.org/3/tv/${id}?api_key=${apiKey}&append_to_response=videos&language=en-US`);
    const dataEn = await resEn.json();
    fillForm(dataAr, dataEn);
    generateSeasonsFromTMDB(dataAr);
    if(dataAr.images && dataAr.images.backdrops.length > 0) renderGallery(dataAr.images.backdrops);
}

function fillForm(ar, en) {
    document.getElementById('tmdb_id').value = ar.id;
    document.getElementById('title_ar').value = ar.name;
    document.getElementById('title_en').value = en.original_name || ar.original_name;
    document.getElementById('release_year').value = ar.first_air_date ? ar.first_air_date.split('-')[0] : '';
    document.getElementById('rating').value = ar.vote_average.toFixed(1);
    document.getElementById('overview').value = ar.overview;
    document.getElementById('genres').value = ar.genres.map(g => g.name).join(', ');
    document.getElementById('cast_members').value = ar.credits.cast.slice(0, 10).map(c => c.name).join(', ');
    let pPath = ar.poster_path;
    document.getElementById('poster_path').value = `https://image.tmdb.org/t/p/w500${pPath}`;
    document.getElementById('poster_preview').src = `https://image.tmdb.org/t/p/w500${pPath}`;
    document.getElementById('backdrop_path').value = `https://image.tmdb.org/t/p/original${ar.backdrop_path}`;
    document.getElementById('backdrop_preview').src = `https://image.tmdb.org/t/p/w500${ar.backdrop_path}`;
}

function addNewSeason(sNumManual = null) {
    seasonCounter++;
    const sNum = sNumManual || seasonCounter;
    const wrapper = document.getElementById('seasons-wrapper');
    const div = document.createElement('div');
    div.className = 'season-entry-box';
    div.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <span style="color:var(--main-color); font-weight:900;"><i class="fas fa-layer-group"></i> الموسم رقم <input type="number" name="seasons[${seasonCounter}][number]" value="${sNum}" style="width:60px; padding:4px;"></span>
            <i class="fas fa-trash" style="color:#ff4444; cursor:pointer;" onclick="this.parentElement.parentElement.remove()"></i>
        </div>
        <div id="episodes-area-${seasonCounter}"></div>
        <button type="button" class="btn-add-action" onclick="addNewEpisode(${seasonCounter})"><i class="fas fa-plus"></i> إضافة حلقة</button>
    `;
    wrapper.appendChild(div);
    return seasonCounter;
}

function addNewEpisode(sID, data = null) {
    const area = document.getElementById(`episodes-area-${sID}`);
    const epNum = area.children.length + 1;
    const div = document.createElement('div');
    div.className = 'episode-row';
    div.innerHTML = `
        <div style="display:grid; grid-template-columns: 40px 1.5fr 1.5fr 1.5fr 1fr 30px; gap:10px; align-items:center;">
            <span style="font-weight:bold; color:var(--text-muted)">${epNum}</span>
            <input type="text" name="seasons[${sID}][episodes][${epNum}][title]" value="${data ? data.title : 'الحلقة '+epNum}">
            <input type="text" name="seasons[${sID}][episodes][${epNum}][watch_link]" value="${data ? data.watch_link : ''}" placeholder="رابط المشاهدة">
            <input type="text" name="seasons[${sID}][episodes][${epNum}][download_link]" value="${data ? data.download_links : ''}" placeholder="رابط التحميل">
            <input type="text" name="seasons[${sID}][episodes][${epNum}][duration]" value="${data ? data.duration : ''}" placeholder="المدة">
            <i class="fas fa-times" style="color:#666; cursor:pointer;" onclick="this.parentElement.parentElement.remove()"></i>
        </div>
    `;
    area.appendChild(div);
}

// تحميل المواسم الحالية عند فتح الصفحة
window.onload = function() {
    <?php
    while($s = $db_seasons->fetch_assoc()) {
        echo "let sID$s[id] = addNewSeason($s[season_number]);";
        $ep_res = $conn->query("SELECT * FROM episodes WHERE season_id = $s[id] ORDER BY episode_number ASC");
        while($e = $ep_res->fetch_assoc()) {
            $e_title = addslashes($e['title']);
            $e_watch = addslashes($e['watch_link']);
            // تم التعديل هنا أيضاً ليقرأ من الحقل الصحيح في قاعدة البيانات
            $e_down = addslashes($e['download_links']); 
            $e_dur = addslashes($e['duration']);
            echo "addNewEpisode(sID$s[id], {title: '$e_title', watch_link: '$e_watch', download_links: '$e_down', duration: '$e_dur'});";
        }
    }
    ?>
};

function renderGallery(backdrops) {
    let placeholder = document.getElementById('gallery-placeholder');
    placeholder.innerHTML = `<div style="background:rgba(212, 255, 0, 0.02); padding:20px; border-radius:12px; margin-top:25px; border:1px dashed #333;"><h3 style="color:var(--main-color); font-size:14px; margin-bottom:15px;">🖼️ خلفيات متاحة من TMDB:</h3><div class="gallery-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap:12px;"></div></div>`;
    let grid = placeholder.querySelector('.gallery-grid');
    backdrops.slice(0, 10).forEach(img => {
        let thumb = `https://image.tmdb.org/t/p/w300${img.file_path}`;
        let full = `https://image.tmdb.org/t/p/original${img.file_path}`;
        let imgTag = document.createElement('img');
        imgTag.src = thumb;
        imgTag.onclick = function() {
            document.getElementById('backdrop_path').value = full;
            document.getElementById('backdrop_preview').src = thumb;
        };
        grid.appendChild(imgTag);
    });
}

function loadSubCats(val) {
    fetch(`get-subcats.php?parent_id=${val}`).then(res => res.json()).then(data => {
        let options = '<option value="">اختر القسم الفرعي...</option>';
        data.forEach(sub => { options += `<option value="${sub.id}">${sub.name}</option>`; });
        document.getElementById('sub_cat').innerHTML = options;
    });
}
</script>
</body>
</html>
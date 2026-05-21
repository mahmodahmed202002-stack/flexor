<?php
// تأكد أن session_start() موجودة في الملف الرئيسي للداشبورد
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if(!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include('../includes/db.php');

// جلب الأقسام الرئيسية النشطة فقط
$main_cats = $conn->query("SELECT * FROM categories WHERE parent_id = 0 AND status = 'active' ORDER BY sort_order ASC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --main-color: #d4ff00;
            --bg-dark: #0a0a0a;
            --card-bg: #141414;
            --input-bg: #000;
            --border-color: #222;
            --text-muted: #888;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            margin: 0; padding: 25px;
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-dark);
            color: #fff;
        }

        h1 { font-size: 24px; font-weight: 900; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; }
        
        /* Search Box */
        .tmdb-search-box {
            position: relative; margin-bottom: 30px;
            background: var(--card-bg); padding: 20px;
            border-radius: 15px; border: 1px solid var(--border-color);
        }
        
        .tmdb-search-box label { display: block; margin-bottom: 12px; color: var(--text-muted); font-size: 14px; font-weight: 600; }
        
        #tmdb-search {
            width: 100%; padding: 15px 20px;
            background: var(--input-bg); border: 1px solid #333;
            color: #fff; border-radius: 10px; font-family: 'Cairo';
            font-size: 16px; transition: var(--transition); box-sizing: border-box;
        }

        #tmdb-search:focus { border-color: var(--main-color); outline: none; box-shadow: 0 0 15px rgba(212, 255, 0, 0.1); }

        #tmdb-results {
            position: absolute; top: 95%; left: 20px; right: 20px;
            background: #1a1a1a; border: 1px solid var(--main-color);
            z-index: 1000; max-height: 300px; overflow-y: auto; display: none;
            border-radius: 0 0 12px 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .tmdb-item {
            display: flex; align-items: center; padding: 12px; cursor: pointer;
            border-bottom: 1px solid #222; transition: var(--transition);
        }
        .tmdb-item:hover { background: #222; color: var(--main-color); }
        .tmdb-item img { width: 40px; height: 55px; margin-left: 15px; border-radius: 5px; object-fit: cover; }

        /* Cards & Forms */
        .admin-card {
            background: var(--card-bg); padding: 30px;
            border-radius: 18px; border: 1px solid var(--border-color);
            margin-bottom: 25px;
        }

        .form-section-title {
            color: var(--main-color); font-size: 16px; font-weight: 700;
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .form-group label { color: #ccc; font-weight: 600; font-size: 13px; }

        input, select, textarea {
            padding: 12px 15px; background: var(--input-bg);
            border: 1px solid #222; color: #fff; border-radius: 8px;
            font-family: 'Cairo'; transition: var(--transition);
        }
        input:focus, select:focus, textarea:focus { border-color: var(--main-color); outline: none; }

        /* Switch UI */
        .hero-option {
            background: rgba(212, 255, 0, 0.03); padding: 18px 25px;
            border-radius: 12px; border: 1px dashed rgba(212, 255, 0, 0.2);
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px;
        }
        .switch { position: relative; display: inline-block; width: 46px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; inset: 0; background-color: #333;
            transition: .4s; border-radius: 34px;
        }
        .slider:before {
            content: ""; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px;
            background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--main-color); }
        input:checked + .slider:before { transform: translateX(22px); }

        /* Visual Assets (Poster & Backdrop) */
        .visual-assets-container { display: grid; grid-template-columns: 280px 1fr; gap: 30px; align-items: start; }
        .poster-main-wrapper {
            position: relative; background: #000; border-radius: 15px;
            overflow: hidden; border: 2px solid #222; aspect-ratio: 2/3;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5); transition: var(--transition);
        }
        .poster-main-wrapper:hover { border-color: var(--main-color); }
        .poster-main-wrapper img { width: 100%; height: 100%; object-fit: cover; }
        .poster-label {
            position: absolute; bottom: 0; left: 0; right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.9));
            padding: 12px; text-align: center; font-size: 12px; color: var(--main-color); font-weight: 700;
        }

        .backdrop-preview-box {
            width: 100%; height: 220px; border-radius: 15px;
            background: #000; border: 2px solid #222; overflow: hidden;
            position: relative; margin-bottom: 15px;
        }
        .backdrop-preview-box img { width: 100%; height: 100%; object-fit: cover; opacity: 0.7; }

        /* Seasons & Episodes Area */
        .season-entry-box {
            background: rgba(255,255,255,0.02); border: 1px solid #222;
            padding: 20px; border-radius: 15px; margin-bottom: 20px;
            border-right: 4px solid var(--main-color);
        }
        .episode-row {
            background: #0a0a0a; padding: 15px; border-radius: 10px;
            border: 1px solid #1a1a1a; margin-top: 10px;
        }

        .btn-add-action {
            background: transparent; color: var(--main-color);
            border: 1px dashed var(--main-color); padding: 8px 15px;
            border-radius: 8px; cursor: pointer; font-family: 'Cairo';
            font-size: 13px; transition: 0.3s;
        }
        .btn-add-action:hover { background: rgba(212, 255, 0, 0.1); }

        .btn-submit {
            margin-top: 20px; width: 100%; background: var(--main-color);
            color: #000; padding: 18px; border: none; border-radius: 12px;
            font-weight: 900; cursor: pointer; font-size: 18px; transition: var(--transition);
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(212, 255, 0, 0.2); }

        .gallery-grid img { 
            width: 100%; height: 80px; object-fit: cover; border-radius: 8px; 
            cursor: pointer; border: 2px solid transparent; transition: 0.2s; 
        }
    </style>
</head>
<body>

<div class="main-content-inner">
    <h1>
        <i class="fas fa-tv" style="color:var(--main-color)"></i>
        إضافة مسلسل جديد (Smart TV Fetch)
    </h1>

    <div class="tmdb-search-box">
        <label>البحث التلقائي في TMDB (اكتب الاسم بالإنجليزي)</label>
        <input type="text" id="tmdb-search" placeholder="مثلاً: Game of Thrones, Breaking Bad, The Crown..." autocomplete="off">
        <div id="tmdb-results"></div>
    </div>

    <form action="save-series-full.php" method="POST" id="seriesForm">
        <input type="hidden" id="tmdb_id" name="tmdb_id">
        <input type="hidden" name="content_type" value="series">

        <div class="hero-option">
            <div style="display:flex; align-items:center; gap:12px;">
                <i class="fas fa-bolt" style="color:var(--main-color)"></i>
                <span style="font-weight:700;">تثبيت في السلايدر الرئيسي (Hero Section)</span>
            </div>
            <label class="switch">
                <input type="checkbox" name="is_hero" value="1">
                <span class="slider"></span>
            </label>
        </div>

        <div class="admin-card">
            <div class="form-section-title"><i class="fas fa-info-circle"></i> البيانات الأساسية</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>الاسم بالعربية</label>
                    <input type="text" id="title_ar" name="title_ar" placeholder="عنوان المسلسل بالعربي" required>
                </div>
                <div class="form-group">
                    <label>الاسم الأصلي (English)</label>
                    <input type="text" id="title_en" name="title_en" placeholder="Original Title" required>
                </div>
            </div>

            <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                <div class="form-group">
                    <label>سنة الإنتاج</label>
                    <input type="text" id="release_year" name="release_year" placeholder="2024">
                </div>
                <div class="form-group">
                    <label>تقييم TMDB</label>
                    <input type="text" id="rating" name="rating" placeholder="8.9">
                </div>
                <div class="form-group">
                    <label>القسم الرئيسي</label>
                    <select name="main_cat_id" onchange="loadSubCats(this.value)" required>
                        <option value="">اختر القسم...</option>
                        <?php while($c = $main_cats->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>التصنيفات (Genres)</label>
                <input type="text" id="genres" name="genres" placeholder="Drama, Action, Mystery">
            </div>

            <div class="form-group">
                <label>قصة المسلسل</label>
                <textarea id="overview" name="overview" rows="4" placeholder="ملخص القصة..."></textarea>
            </div>
        </div>

        <div class="admin-card">
            <div class="form-section-title"><i class="fas fa-image"></i> الوسائط المرئية</div>
            <div class="visual-assets-container">
                <div class="poster-main-wrapper">
                    <img id="poster_preview" src="img/no-image.png">
                    <div class="poster-label">بوستر المسلسل الرسمي</div>
                </div>

                <div class="backdrop-area">
                    <div class="backdrop-preview-box">
                        <img id="backdrop_preview" src="img/no-image.png">
                    </div>
                    <div class="form-grid" style="gap:15px;">
                        <div class="form-group">
                            <label>رابط البوستر</label>
                            <input type="text" id="poster_path" name="poster_path" oninput="document.getElementById('poster_preview').src = this.value">
                        </div>
                        <div class="form-group">
                            <label>رابط الخلفية</label>
                            <input type="text" id="backdrop_path" name="backdrop_path" oninput="document.getElementById('backdrop_preview').src = this.value">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Youtube Trailer ID</label>
                        <input type="text" id="trailer_url" name="trailer_url" placeholder="مثلاً: d9MyW72ELq0">
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
            
            <div id="seasons-wrapper"></div>
        </div>

        <div class="admin-card">
            <div class="form-section-title"><i class="fas fa-users"></i> طاقم العمل</div>
            <textarea id="cast_members" name="cast_members" rows="2" placeholder="أبرز الممثلين..."></textarea>
        </div>

        <button type="submit" class="btn-submit">🚀 حفظ ونشر المسلسل بالكامل</button>
    </form>
</div>

<script>
const apiKey = '848951d3bbec3a919bf8bb3738a60628';
let seasonCounter = 0;

// البحث التلقائي
document.getElementById('tmdb-search').addEventListener('input', function() {
    let query = this.value;
    if(query.length > 2) {
        fetch(`https://api.themoviedb.org/3/search/tv?api_key=${apiKey}&query=${query}&language=ar-SA`)
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.results.forEach(tv => {
                let poster = tv.poster_path ? `https://image.tmdb.org/t/p/w92${tv.poster_path}` : 'img/no-image.png';
                html += `
                <div class="tmdb-item" onclick="getSeriesDetails(${tv.id})">
                    <img src="${poster}">
                    <span>${tv.name} (${tv.first_air_date ? tv.first_air_date.split('-')[0] : 'N/A'})</span>
                </div>`;
            });
            document.getElementById('tmdb-results').innerHTML = html;
            document.getElementById('tmdb-results').style.display = 'block';
        });
    } else {
        document.getElementById('tmdb-results').style.display = 'none';
    }
});

async function getSeriesDetails(id) {
    document.getElementById('tmdb-results').style.display = 'none';
    document.getElementById('tmdb-search').value = "⏳ جاري جلب كامل البيانات...";
    
    // جلب البيانات بالعربي
    const resAr = await fetch(`https://api.themoviedb.org/3/tv/${id}?api_key=${apiKey}&append_to_response=videos,images,credits&language=ar-SA&include_image_language=en,null`);
    const dataAr = await resAr.json();
    
    // جلب الاسم الأصلي والتريلر من الإنجليزي لضمان الجودة
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
    
    // اختيار أفضل بوستر (غير مترجم إن وجد)
    let pPath = ar.poster_path;
    if(ar.images.posters.length > 0) {
        let bestPoster = ar.images.posters.find(p => p.iso_639_1 !== 'ar') || ar.images.posters[0];
        pPath = bestPoster.file_path;
    }

    document.getElementById('poster_path').value = `https://image.tmdb.org/t/p/w500${pPath}`;
    document.getElementById('poster_preview').src = `https://image.tmdb.org/t/p/w500${pPath}`;
    document.getElementById('backdrop_path').value = `https://image.tmdb.org/t/p/original${ar.backdrop_path}`;
    document.getElementById('backdrop_preview').src = `https://image.tmdb.org/t/p/w500${ar.backdrop_path}`;

    let trailer = ar.videos.results.find(v => v.type === 'Trailer') || en.videos.results.find(v => v.type === 'Trailer');
    if(trailer) document.getElementById('trailer_url').value = trailer.key;
    
    document.getElementById('tmdb-search').value = ar.name;
}

function addNewSeason(sNumManual = null) {
    seasonCounter++;
    const sNum = sNumManual || seasonCounter;
    const wrapper = document.getElementById('seasons-wrapper');
    const div = document.createElement('div');
    div.className = 'season-entry-box';
    div.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <span style="color:var(--main-color); font-weight:900;"><i class="fas fa-layer-group"></i> الموسم رقم <input type="number" name="seasons[${seasonCounter}][number]" value="${sNum}" style="width:60px; padding:4px; margin-right:5px;"></span>
            <i class="fas fa-trash" style="color:#ff4444; cursor:pointer;" onclick="this.parentElement.parentElement.remove()"></i>
        </div>
        <div id="episodes-area-${seasonCounter}"></div>
        <button type="button" class="btn-add-action" style="margin-top:10px;" onclick="addNewEpisode(${seasonCounter})"><i class="fas fa-plus"></i> إضافة حلقة للموسم</button>
    `;
    wrapper.appendChild(div);
    return seasonCounter;
}

function addNewEpisode(sID) {
    const area = document.getElementById(`episodes-area-${sID}`);
    const epNum = area.children.length + 1;
    const div = document.createElement('div');
    div.className = 'episode-row';
    div.innerHTML = `
        <div style="display:grid; grid-template-columns: 40px 1.5fr 1.5fr 1.5fr 1fr 30px; gap:10px; align-items:center;">
            <span style="font-weight:bold; color:var(--text-muted)">${epNum}</span>
            <input type="text" name="seasons[${sID}][episodes][${epNum}][title]" placeholder="اسم الحلقة" value="الحلقة ${epNum}">
            <input type="text" name="seasons[${sID}][episodes][${epNum}][watch_link]" placeholder="رابط المشاهدة">
            <input type="text" name="seasons[${sID}][episodes][${epNum}][download_links]" placeholder="رابط التحميل">
            <input type="text" name="seasons[${sID}][episodes][${epNum}][duration]" placeholder="المدة (مثلاً 45:00)">
            <i class="fas fa-times" style="color:#666; cursor:pointer;" onclick="this.parentElement.parentElement.remove()"></i>
        </div>
    `;
    area.appendChild(div);
}

function generateSeasonsFromTMDB(data) {
    const wrapper = document.getElementById('seasons-wrapper');
    wrapper.innerHTML = "";
    data.seasons.forEach(s => {
        if(s.season_number === 0 || s.episode_count === 0) return;
        let currentSID = addNewSeason(s.season_number);
        for(let i=1; i<=s.episode_count; i++) {
            addNewEpisode(currentSID);
        }
    });
}

function renderGallery(backdrops) {
    let placeholder = document.getElementById('gallery-placeholder');
    placeholder.innerHTML = `
        <div style="background:rgba(212, 255, 0, 0.02); padding:20px; border-radius:12px; margin-top:25px; border:1px dashed #333;">
            <h3 style="color:var(--main-color); font-size:14px; margin-bottom:15px;">🖼️ خلفيات متاحة (اضغط للاختيار):</h3>
            <div class="gallery-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap:12px;"></div>
        </div>`;
    let grid = placeholder.querySelector('.gallery-grid');
    backdrops.slice(0, 10).forEach(img => {
        let thumb = `https://image.tmdb.org/t/p/w300${img.file_path}`;
        let full = `https://image.tmdb.org/t/p/original${img.file_path}`;
        let imgTag = document.createElement('img');
        imgTag.src = thumb;
        imgTag.onclick = function() {
            document.getElementById('backdrop_path').value = full;
            document.getElementById('backdrop_preview').src = thumb;
            document.querySelectorAll('.gallery-grid img').forEach(i => i.style.borderColor = 'transparent');
            this.style.borderColor = 'var(--main-color)';
        };
        grid.appendChild(imgTag);
    });
}

function loadSubCats(val) {
    if(val == "") { document.getElementById('sub_cat_div').style.display = 'none'; return; }
    fetch(`get-subcats.php?parent_id=${val}`)
    .then(res => res.json())
    .then(data => {
        let options = '<option value="">اختر القسم الفرعي...</option>';
        data.forEach(sub => { options += `<option value="${sub.id}">${sub.name}</option>`; });
        let subDiv = document.createElement('div');
        subDiv.className = "form-group";
        subDiv.id = "sub_cat_area";
        subDiv.innerHTML = `<label>القسم الفرعي</label><select name="sub_cat_id" id="sub_cat" required>${options}</select>`;
        
        let target = document.querySelector('select[name="main_cat_id"]').parentElement;
        let old = document.getElementById('sub_cat_area');
        if(old) old.remove();
        target.after(subDiv);
    });
}

// التحقق من صحة النموذج قبل الإرسال
document.getElementById("seriesForm").addEventListener("submit", function(e){
    const seasons = document.querySelectorAll('.season-entry-box');
    if(seasons.length === 0) {
        if(!confirm("⚠️ لم تقم بإضافة أي مواسم، هل تريد الاستمرار بحفظ المسلسل فقط؟")) {
            e.preventDefault();
        }
    }
});
</script>

</body>
</html>
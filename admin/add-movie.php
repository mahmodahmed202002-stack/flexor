<?php
// تأكد أن session_start() موجودة في الملف الرئيسي للداشبورد
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if(!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include('../includes/db.php');

// جلب الأقسام الرئيسية
$main_cats = $conn->query("SELECT * FROM categories WHERE parent_id = 0 ORDER BY sort_order ASC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
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
            margin: 0;
            padding: 25px;
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-dark);
            color: #fff;
        }

        h1 { font-size: 24px; font-weight: 900; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; }
        
        /* Search Box */
        .tmdb-search-box {
            position: relative;
            margin-bottom: 30px;
            background: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
        }
        
        .tmdb-search-box label { display: block; margin-bottom: 12px; color: var(--text-muted); font-size: 14px; font-weight: 600; }
        
        #tmdb-search {
            width: 100%;
            padding: 15px 20px;
            background: var(--input-bg);
            border: 1px solid #333;
            color: #fff;
            border-radius: 10px;
            font-family: 'Cairo';
            font-size: 16px;
            transition: var(--transition);
            box-sizing: border-box;
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

        .tmdb-item:hover { background: #222; }
        .tmdb-item img { width: 40px; height: 55px; margin-left: 15px; border-radius: 5px; object-fit: cover; }
        .tmdb-item span { font-weight: 600; font-size: 14px; }

        /* Main Form Containers */
        .admin-card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 18px;
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
        }

        .form-section-title {
            color: var(--main-color);
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .form-group { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
        .form-group label { color: #ccc; font-weight: 600; font-size: 13px; }

        input, select, textarea {
            padding: 12px 15px;
            background: var(--input-bg);
            border: 1px solid #222;
            color: #fff;
            border-radius: 8px;
            font-family: 'Cairo';
            transition: var(--transition);
        }

        input:focus, select:focus, textarea:focus { border-color: var(--main-color); outline: none; }

        /* Custom UI Components */
        .hero-option {
            background: rgba(212, 255, 0, 0.03);
            padding: 18px 25px;
            border-radius: 12px;
            border: 1px dashed rgba(212, 255, 0, 0.2);
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 25px;
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

        /* --- MODIFIED POSTER AREA (العالمي) --- */
        .visual-assets-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
            align-items: start;
        }

        .poster-main-wrapper {
            position: relative;
            background: #000;
            border-radius: 15px;
            overflow: hidden;
            border: 2px solid #222;
            aspect-ratio: 2/3;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            transition: var(--transition);
        }

        .poster-main-wrapper:hover { border-color: var(--main-color); }

        .poster-main-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .poster-label {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.9));
            padding: 15px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            color: var(--main-color);
        }

        .backdrop-wide-wrapper {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .backdrop-preview-box {
            width: 100%;
            height: 250px;
            border-radius: 15px;
            background: #000;
            border: 2px solid #222;
            overflow: hidden;
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .backdrop-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.7;
        }

        .asset-input-group {
            background: rgba(255,255,255,0.03);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #222;
        }

        .asset-input-group label {
            display: block;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 600;
        }

        .asset-input-group input {
            width: 100%;
            background: #000 !important;
            font-size: 12px;
            border-color: #333;
            box-sizing: border-box;
        }
        /* --- END MODIFIED AREA --- */

        .btn-submit {
            margin-top: 20px; width: 100%; background: var(--main-color);
            color: #000; padding: 18px; border: none; border-radius: 12px;
            font-weight: 900; cursor: pointer; font-size: 18px;
            transition: var(--transition);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(212, 255, 0, 0.2); }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
    </style>
</head>
<body>

<div class="main-content-inner">
    <h1>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="var(--main-color)"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
        إضافة محتوى جديد (Smart Fetch)
    </h1>
    
    <div class="tmdb-search-box">
        <label>البحث التلقائي في TMDB (اكتب الاسم بالإنجليزي لجلب كافة التفاصيل)</label>
        <input type="text" id="tmdb-search" placeholder="مثلاً: Interstellar, Batman, The Crown..." autocomplete="off">
        <div id="tmdb-results"></div>
    </div>

    <form action="save-movie.php" method="POST">
        <input type="hidden" id="tmdb_id" name="tmdb_id">
        <input type="hidden" id="slug" name="slug">
        
        <div class="hero-option">
            <div style="display:flex; align-items:center; gap:12px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="var(--main-color)"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                <span style="font-weight:700;">عرض الفيلم في السلايدر الرئيسي (Hero Section)</span>
            </div>
            <label class="switch">
                <input type="checkbox" name="is_hero" value="1">
                <span class="slider"></span>
            </label>
        </div>

        <div class="admin-card">
            <div class="form-section-title">📦 المعلومات الأساسية</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>الاسم باللغة العربية</label>
                    <input type="text" id="title_ar" name="title_ar" placeholder="عنوان الفيلم بالعربي" required>
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
                    <label>تقييم IMDB</label>
                    <input type="text" id="rating" name="rating" placeholder="8.5">
                </div>
                <div class="form-group">
                    <label>مدة العرض</label>
                    <input type="text" id="duration" name="duration" placeholder="120 دقيقة">
                </div>
            </div>

            <div class="form-group">
                <label>التصنيفات (Genres)</label>
                <input type="text" id="genres" name="genres" placeholder="Action, Drama, Sci-Fi">
            </div>

            <div class="form-group">
                <label>قصة المحتوى</label>
                <textarea id="overview" name="overview" rows="4" placeholder="اكتب ملخص القصة هنا..."></textarea>
            </div>
        </div>

        <div class="admin-card">
            <div class="form-section-title">🔗 الروابط والتصنيف</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>نوع المحتوى</label>
                    <select name="content_type" id="content_type" required>
                        <option value="movie">🎬 فيلم سينمائي</option>
                        <option value="series">📺 مسلسل تلفزيوني</option>
                        <option value="documentary">🎥 وثائقي</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>القسم الرئيسي</label>
                    <select name="main_cat_id" onchange="loadSubCats(this.value)" required>
                        <option value="">اختر القسم...</option>
                        <?php 
                        $main_cats->data_seek(0);
                        while($c = $main_cats->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div id="sub_cat_div" class="form-group" style="display:none;">
                <label>القسم الفرعي</label>
                <select name="sub_cat_id" id="sub_cat"></select>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Youtube Trailer ID</label>
                    <input type="text" id="trailer_url" name="trailer_url" placeholder="مثلاً: d9MyW72ELq0">
                </div>
                <div class="form-group">
                    <label>سيرفر المشاهدة (Embed / Link)</label>
                    <input type="text" name="watch_link" placeholder="رابط iframe أو رابط مباشر">
                </div>
            </div>

            <div class="form-group">
                <label>روابط التحميل (فصل بينها بـ | )</label>
                <textarea name="download_links" rows="2" placeholder="1080p: Link | 720p: Link"></textarea>
            </div>
        </div>

        <div class="admin-card">
            <div class="form-section-title">🖼️ الصور والبوسترات الاحترافية</div>
            
            <div class="visual-assets-container">
                <div class="poster-main-wrapper">
                    <img id="poster_preview" src="img/no-image.png" alt="Poster">
                    <div class="poster-label">بوستر المحتوى الرئيسي</div>
                </div>

                <div class="backdrop-wide-wrapper">
                    <div class="backdrop-preview-box">
                        <img id="backdrop_preview" src="img/no-image.png" alt="Backdrop">
                    </div>

                    <div class="form-grid" style="gap: 15px;">
                        <div class="asset-input-group">
                            <label>رابط البوستر (Poster URL)</label>
                            <input type="text" id="poster_path" name="poster_path" placeholder="URL..." oninput="document.getElementById('poster_preview').src = this.value">
                        </div>
                        <div class="asset-input-group">
                            <label>رابط الخلفية (Backdrop URL)</label>
                            <input type="text" id="backdrop_path" name="backdrop_path" placeholder="URL..." oninput="document.getElementById('backdrop_preview').src = this.value">
                        </div>
                    </div>

                    <div id="gallery-placeholder"></div>
                </div>
            </div>
        </div>
        <div class="admin-card">
             <div class="form-section-title">👤 طاقم العمل</div>
            <div class="form-group">
                <label>أبرز الممثلين</label>
                <textarea id="cast_members" name="cast_members" rows="2" placeholder="الممثلين الرئيسيين..."></textarea>
            </div>
        </div>

        <button type="submit" class="btn-submit">🚀 تأكيد ونشر المحتوى في الموقع</button>
    </form>
</div>

<script>
const apiKey = '848951d3bbec3a919bf8bb3738a60628';

document.getElementById('tmdb-search').addEventListener('input', function() {
    let query = this.value;
    if(query.length > 2) {
        fetch(`https://api.themoviedb.org/3/search/movie?api_key=${apiKey}&query=${query}&language=ar-SA`)
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.results.forEach(movie => {
                let year = movie.release_date ? movie.release_date.split('-')[0] : 'N/A';
                let poster = movie.poster_path ? `https://image.tmdb.org/t/p/w92${movie.poster_path}` : 'img/no-image.png';
                html += `
                <div class="tmdb-item" onclick="getMovieDetails(${movie.id})">
                    <img src="${poster}">
                    <span>${movie.title} (${year})</span>
                </div>`;
            });
            document.getElementById('tmdb-results').innerHTML = html;
            document.getElementById('tmdb-results').style.display = 'block';
        });
    } else {
        document.getElementById('tmdb-results').style.display = 'none';
    }
});

function getMovieDetails(id) {
    document.getElementById('tmdb-results').style.display = 'none';
    document.getElementById('tmdb-search').value = "⏳ جاري سحب البيانات...";

    fetch(`https://api.themoviedb.org/3/movie/${id}?api_key=${apiKey}&append_to_response=videos,images,credits&language=ar-SA&include_image_language=en,null`)
    .then(res => res.json())
    .then(data => {
        fillForm(data);
        
        // جلب التريلر
        let trailer = data.videos.results.find(v => v.type === 'Trailer' && v.site === 'YouTube');
        if(!trailer) {
            fetch(`https://api.themoviedb.org/3/movie/${id}/videos?api_key=${apiKey}`)
            .then(res => res.json())
            .then(engData => {
                let engTrailer = engData.results.find(v => v.type === 'Trailer' && v.site === 'YouTube');
                if(engTrailer) document.getElementById('trailer_url').value = engTrailer.key;
            });
        } else {
            document.getElementById('trailer_url').value = trailer.key;
        }

        if(data.images && data.images.backdrops.length > 0) {
            renderGallery(data.images.backdrops);
        }
    });
}

function fillForm(data) {
    document.getElementById('tmdb_id').value = data.id;
    document.getElementById('title_ar').value = data.title;
    document.getElementById('title_en').value = data.original_title;
    document.getElementById('slug').value = generateSlug(data.original_title);
    document.getElementById('release_year').value = data.release_date ? data.release_date.split('-')[0] : '';
    document.getElementById('rating').value = data.vote_average.toFixed(1);
    document.getElementById('overview').value = data.overview;
    document.getElementById('duration').value = data.runtime ? data.runtime + " دقيقة" : '';
    document.getElementById('genres').value = data.genres.map(g => g.name).join(', ');
    document.getElementById('cast_members').value = data.credits.cast.slice(0, 8).map(c => c.name).join(', ');

    // --- تعديل جلب البوستر الأصلي ---
    // سنحاول البحث عن البوستر الذي لا يحتوي على لغة (null) أو اللغة الإنجليزية أولاً
    let originalPoster = data.poster_path; // الافتراضي
    
    if (data.images && data.images.posters.length > 0) {
        // البحث عن أول بوستر لغته ليست العربية (يفضل null أو en)
        let bestPoster = data.images.posters.find(p => p.iso_639_1 !== 'ar') || data.images.posters[0];
        originalPoster = bestPoster.file_path;
    }

    let pUrl = `https://image.tmdb.org/t/p/w500${originalPoster}`;
    let bUrl = `https://image.tmdb.org/t/p/original${data.backdrop_path}`;
    
    document.getElementById('poster_path').value = pUrl;
    document.getElementById('poster_preview').src = pUrl;
    document.getElementById('backdrop_path').value = bUrl;
    document.getElementById('backdrop_preview').src = bUrl;
    
    document.getElementById('tmdb-search').value = data.title;
}

function renderGallery(backdrops) {
    let placeholder = document.getElementById('gallery-placeholder');
    placeholder.innerHTML = `
        <div style="background:rgba(212, 255, 0, 0.02); padding:20px; border-radius:12px; margin-top:25px; border:1px dashed #333;">
            <h3 style="color:var(--main-color); font-size:14px; margin-bottom:15px;">🖼️ خلفيات متاحة من الفيلم (اضغط للاختيار):</h3>
            <div id="gallery-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap:12px;"></div>
        </div>
    `;
    let grid = document.getElementById('gallery-grid');
    backdrops.slice(0, 10).forEach(img => {
        let thumb = `https://image.tmdb.org/t/p/w300${img.file_path}`;
        let full = `https://image.tmdb.org/t/p/original${img.file_path}`;
        let imgTag = document.createElement('img');
        imgTag.src = thumb;
        imgTag.style = "width:100%; height:75px; object-fit:cover; border-radius:8px; cursor:pointer; border:2px solid transparent; transition:0.2s;";
        imgTag.onclick = function() {
            document.getElementById('backdrop_path').value = full;
            document.getElementById('backdrop_preview').src = thumb;
            document.querySelectorAll('#gallery-grid img').forEach(i => i.style.borderColor = 'transparent');
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
        document.getElementById('sub_cat').innerHTML = options;
        document.getElementById('sub_cat_div').style.display = 'block';
    });
}
    
    
    function generateSlug(text) {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

// لما يكتب اسم الفيلم
document.getElementById('title_en').addEventListener('input', function() {
    document.getElementById('slug').value = generateSlug(this.value);
});
</script>

</body>
</html>
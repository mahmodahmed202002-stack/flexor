<?php
function renderMovieCard($data, $force_fav = false) {

    // =========================
    // 🎯 تحديد نوع المحتوى
    // =========================
    $main_cat = intval($data['main_cat_id']);
    $content_type = isset($data['content_type']) && $data['content_type'] 
        ? $data['content_type'] 
        : (($main_cat == 2 || $main_cat == 11) ? 'series' : 'movie');

    if ($content_type === 'series' || $main_cat == 2 || $main_cat == 11) {
        $folder = "series";
    } elseif ($main_cat == 3) {
        $folder = "sports";
    } else {
        $folder = "movie";
    }

    // =========================
    // 🔥 الرابط الجديد (SLUG)
    // =========================
    $slug = $data['slug'] ?? '';

    if (!empty($slug)) {
        $watch_url = "/" . $folder . "/" . $slug;
    } else {
        // fallback لو slug مش موجود
        $watch_url = "/watch/" . $folder . "/index.php?id=" . $data['id'];
    }

    // =========================
    // 🖼️ الصورة
    // =========================
    $image_path = !empty($data['backdrop_path']) 
        ? $data['backdrop_path'] 
        : (!empty($data['poster_path']) ? $data['poster_path'] : '/img/no-backdrop.jpg');

    $is_fav_status = ($force_fav || (isset($data['is_favorite']) && $data['is_favorite'] > 0)) ? 'true' : 'false';

    // =========================
    // 🎬 العناوين
    // =========================
    $display_title_ar = $data['title_ar'] ?? ($data['name'] ?? 'بدون عنوان');
    $display_title_en = $data['title_en'] ?? ($data['title'] ?? '');

    // =========================
    // 🎥 التريلر
    // =========================
    $trailer_id = "";
    if(!empty($data['youtube_url'])) {
        parse_str(parse_url($data['youtube_url'], PHP_URL_QUERY), $yt_params);
        $trailer_id = $yt_params['v'] ?? "";
    }
?>

<div class="movie-card-parent" 
    onmouseenter="if(typeof startTrailerTimer === 'function') startTrailerTimer(this, '<?php echo $data['trailer_url'] ?? ''; ?>')" 
    onmouseleave="if(typeof stopTrailerTimer === 'function') stopTrailerTimer(this)"
    ontouchstart="if(typeof startTrailerTimer === 'function') startTrailerTimer(this, '<?php echo $data['trailer_url'] ?? ''; ?>')">

    <a href="<?php echo $watch_url; ?>" class="movie-card-link" style="text-decoration: none; color: inherit;">
        
        <div class="card-main-content">
            
            <div class="trailer-preview-layer"></div>

            <img src="<?php echo $image_path; ?>" alt="<?php echo $display_title_ar; ?>" class="main-img" loading="lazy">
            
            <div class="card-overlay">

                <!-- 🔝 TOP -->
                <div class="overlay-top-meta">
                    
                    <div class="meta-tags">
                        <span class="tag year-tag">
                            <svg viewBox="0 0 24 24" width="10" fill="currentColor">
                                <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/>
                            </svg>
                            <?php echo $data['release_year'] ?? ($data['year'] ?? 'N/A'); ?>
                        </span>

                        <span class="tag rating-tag">
                            <svg viewBox="0 0 24 24" width="10" fill="currentColor">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                            </svg>
                            <?php echo number_format($data['rating'] ?? 0, 1); ?>
                        </span>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button type="button" class="neon-fav-btn"
                            onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(this, <?php echo $data['id']; ?>, '<?php echo $content_type ?: 'movie'; ?>');"
                            data-fav="<?php echo $is_fav_status; ?>">
                            
                            <svg viewBox="0 0 24 24" class="svg-icon heart-icon">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </button>
                    <?php endif; ?>

                </div>

                <!-- 🎯 MID -->
                <div class="overlay-mid-info">
                    <h3 class="title-en"><?php echo $display_title_en; ?></h3>
                </div>

                <!-- 🔻 BOTTOM -->
                <div class="overlay-bottom-action">
                    <div class="watch-btn-glow">
                        <span class="btn-text">مشاهدة الآن</span>
                        <div class="btn-icon">
                            <svg viewBox="0 0 24 24" width="18" fill="currentColor">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 📛 TITLE -->
            <div class="static-title-box">
                <span><?php echo $display_title_ar; ?></span>
            </div>

        </div>
    </a>
</div>

<?php
}
?>
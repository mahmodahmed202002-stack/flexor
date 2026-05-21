<?php 
session_start();
include('includes/db.php'); 
$page_title = "Flexor - مشاهدة أفلام ومسلسلات وبث مباشر";
$page_desc  = "أفضل منصة عربية لمشاهدة الأفلام والمسلسلات والبث المباشر";
include('includes/header.php'); 

// استدعاء الكروت
$movie_card = 'includes/components/movie-card.php';
$yt_card = 'includes/components/youtube-card.php';

if (file_exists($movie_card)) include($movie_card);
if (file_exists($yt_card)) include($yt_card);

include('hero-slider.php');

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// جلب الأقسام واستثناء البث المباشر
$main_sections = $conn->query("SELECT id, name, slug FROM categories 
                               WHERE parent_id = 0 AND status = 'active' 
                               AND slug != 'بث-مباشر' ORDER BY sort_order ASC");
?>

<script type="application/ld+json">
{
 "@context": "https://schema.org",
 "@type": "WebSite",
 "name": "Flexor",
 "url": "https://flexor.gt.tc",
 "potentialAction": {
   "@type": "SearchAction",
   "target": "https://flexor.gt.tc/search?q={search_term_string}",
   "query-input": "required name=search_term_string"
 }
}
</script>

<div class="container main-layout">
    <?php 
    if($main_sections && $main_sections->num_rows > 0):
        while($section = $main_sections->fetch_assoc()):
            $current_cat_id = (int)$section['id'];
            $section_title = $section['name'];
            $cat_slug = $section['slug'];
            
            $items = null;
            $is_yt = ($current_cat_id == 18 || $cat_slug == 'يوتيوب');
            $view_all_link = $is_yt ? "youtube-platforms.php" : "category.php?slug=" . $cat_slug;

            if ($is_yt) {
                // استعلام اليوتيوب (جلب البيانات المطلوبة لكارت اليوتيوب)
                $items_query = "SELECT * FROM yt_channels ORDER BY id DESC LIMIT 8";
            } else {
                // استعلام الأفلام والمسلسلات
                $items_query = "SELECT m.*, 'movie' as content_type,
                                (SELECT COUNT(*) FROM favorites f WHERE f.movie_id = m.id AND f.user_id = $user_id) as is_favorite
                                FROM movies m WHERE m.main_cat_id = $current_cat_id 
                                OR m.sub_cat_id IN (SELECT id FROM categories WHERE parent_id = $current_cat_id)
                                ORDER BY m.id DESC LIMIT 8";
                
                $items = $conn->query($items_query);
                if(!$items || $items->num_rows == 0) {
                    $items_query = "SELECT s.*, 'series' as content_type,
                                    (SELECT COUNT(*) FROM favorites f WHERE f.series_id = s.id AND f.user_id = $user_id) as is_favorite
                                    FROM series s WHERE s.main_cat_id = $current_cat_id 
                                    OR s.sub_cat_id IN (SELECT id FROM categories WHERE parent_id = $current_cat_id)
                                    ORDER BY s.id DESC LIMIT 8";
                }
            }

            if ($items === null || ($items !== null && $items->num_rows == 0)) {
                $items = $conn->query($items_query);
            }

            if($items && $items->num_rows > 0): ?>
                <div class="section-container" style="margin-bottom: 40px;">
                    <div class="section-header-wrap" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-right: 4px solid #d4ff00; padding-right: 15px;">
                        <h2 class="section-title" style="margin: 0; font-size: 1.8rem; color: #fff;"><?php echo $section_title; ?></h2>
                        <a href="<?php echo $view_all_link; ?>" class="btn-view-all">عرض الكل</a>
                    </div>
                    
                    <div class="movie-grid">
                        <?php while($row = $items->fetch_assoc()): ?>
                            <?php 
                            if($is_yt) {
                                if (function_exists('renderYoutubeCard')) {
                                    renderYoutubeCard($row, $conn); 
                                }
                            } else {
                                if (function_exists('renderMovieCard')) {
                                    renderMovieCard($row); 
                                }
                            }
                            ?>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>
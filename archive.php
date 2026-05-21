<?php 
// 1. بدء الجلسة
session_start();

include('includes/db.php');
include('includes/header.php');
include('includes/components/movie-card.php');

// جلب رقم القسم الرئيسي من الرابط
$main_cat_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
// جلب معرف المستخدم من الجلسة (إذا كان مسجلاً)
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($main_cat_id == 0) {
    echo "<div class='container' style='margin-top:100px; text-align:center;'><h2>عذراً، القسم غير موجود.</h2></div>";
    include('includes/footer.php');
    exit;
}

$page_title = ($main_cat_id == 7) ? "مكتبة الأفلام" : "مكتبة المسلسلات";
$sub_sections = $conn->query("SELECT DISTINCT sub_cat_id FROM movies WHERE main_cat_id = $main_cat_id");

?>

<div class="container main-layout" style="margin-top: 50px;">
    <h1 class="archive-main-title"><?php echo $page_title; ?></h1>
    <hr class="title-underline">

    <?php 
    if($sub_sections && $sub_sections->num_rows > 0):
        while($sub = $sub_sections->fetch_assoc()):
            $current_sub_id = $sub['sub_cat_id'];

            // تسمية الأقسام الفرعية
            $sub_title = "تصنيفات متنوعة";
            switch($current_sub_id) {
                case 8: $sub_title = "أفلام أجنبية"; break;
                case 9: $sub_title = "أفلام عربية"; break;
                case 10: $sub_title = "أفلام هندي"; break;
            }

            // --- التعديل الجوهري هنا ---
            // نقوم بعمل LEFT JOIN مع جدول favorites لفحص وجود الفيلم لمستخدم معين
            $items_query = "
                SELECT m.*, 
                (CASE WHEN f.movie_id IS NOT NULL THEN 1 ELSE 0 END) AS is_favorite
                FROM movies m
                LEFT JOIN favorites f ON m.id = f.movie_id AND f.user_id = $user_id
                WHERE m.main_cat_id = $main_cat_id AND m.sub_cat_id = $current_sub_id 
                ORDER BY m.id DESC";
            
            $items = $conn->query($items_query);

            if($items && $items->num_rows > 0): ?>
                <div class="sub-category-block">
                    <h2 class="sub-section-title"><span>#</span> <?php echo $sub_title; ?></h2>
                    <div class="movie-grid">
                        <?php while($row = $items->fetch_assoc()): ?>
                            <?php 
                            if (function_exists('renderMovieCard')) {
                                // الآن المصفوفة $row تحتوي على مفتاح جديد اسمه is_favorite
                                renderMovieCard($row); 
                            }
                            ?>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endwhile; 
    else:
        echo "<p style='text-align:center; color:#888;'>لا يوجد محتوى متاح في هذا القسم حالياً.</p>";
    endif; 
    ?>
</div>

<?php 
include('includes/footer.php'); 
?>
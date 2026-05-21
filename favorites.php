<?php 
session_start();
include('includes/db.php'); 
include('includes/header.php'); 

// استدعاء ملف الكارت
$card_file = 'includes/components/movie-card.php';
if (file_exists($card_file)) { 
    include($card_file); 
}

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/auth/login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// 🎬 الأفلام
$movies_query = "
SELECT m.*, f.created_at as fav_date
FROM movies m 
JOIN favorites f ON m.id = f.movie_id 
WHERE f.user_id = ?
ORDER BY f.created_at DESC
";

$stmt1 = $conn->prepare($movies_query);
$stmt1->bind_param("i", $user_id);
$stmt1->execute();
$movies = $stmt1->get_result();


// 📺 المسلسلات
$series_query = "
SELECT s.*, f.created_at as fav_date
FROM series s
JOIN favorites f ON s.id = f.series_id
WHERE f.user_id = ?
ORDER BY f.created_at DESC
";

$stmt2 = $conn->prepare($series_query);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$series = $stmt2->get_result();
?>

<div class="container main-layout mt-5 pt-5">
    <div class="section-container">
        <div class="section-header-wrap">
            <h2 class="section-title">قائمة مفضلاتي</h2>
        </div>

        <?php if(
            ($movies && $movies->num_rows > 0) || 
            ($series && $series->num_rows > 0)
        ): ?>

            <?php if($movies && $movies->num_rows > 0): ?>
    
    <div class="section-header-wrap" style="margin-top:30px;">
        <h2 class="section-title">🎬 الأفلام</h2>
    </div>

    <div class="movie-grid">
        <?php while($row = $movies->fetch_assoc()): ?>
            <?php 
            if (function_exists('renderMovieCard')) {
                renderMovieCard($row, true); 
            }
            ?>
        <?php endwhile; ?>
    </div>

<?php endif; ?>


<?php if($series && $series->num_rows > 0): ?>
    
    <div class="section-header-wrap" style="margin-top:40px;">
        <h2 class="section-title">📺 المسلسلات</h2>
    </div>

    <div class="movie-grid">
        <?php while($row = $series->fetch_assoc()): ?>
            <?php 
            if (function_exists('renderMovieCard')) {
                renderMovieCard($row, true); 
            }
            ?>
        <?php endwhile; ?>
    </div>

<?php endif; ?>

        <?php else: ?>
            <div class="empty-favorites-wrapper">
                <div class="empty-content">
                    <i class="fas fa-heart-broken fa-4x mb-3"></i>
                    <p>قائمة المفضلات فارغة حالياً.</p>
                    <a href="index.php" class="btn-view-all">تصفح الأفلام</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
include('footer.php'); 
?>
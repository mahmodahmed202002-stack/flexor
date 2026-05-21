<?php
// admin/update-movie.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include('../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // استلام البيانات من النموذج وتجهيزها
    $id = intval($_POST['movie_id']);
    $tmdb_id = !empty($_POST['tmdb_id']) ? intval($_POST['tmdb_id']) : null;
    $title_ar = $_POST['title_ar'];
    $title_en = $_POST['title_en'];
    $release_year = !empty($_POST['release_year']) ? intval($_POST['release_year']) : null;
    $rating = !empty($_POST['rating']) ? floatval($_POST['rating']) : 0.0;
    $genres = $_POST['genres'];
    $overview = $_POST['overview'];
    $trailer_url = $_POST['trailer_url'];
    $duration = $_POST['duration'];
    $cast_members = $_POST['cast_members'];
    $main_cat_id = !empty($_POST['main_cat_id']) ? intval($_POST['main_cat_id']) : null;
    $content_type = $_POST['content_type']; // الافتراضي في الجدول هو movie
    $sub_cat_id = !empty($_POST['sub_cat_id']) ? intval($_POST['sub_cat_id']) : null;
    $poster_path = $_POST['poster_path'];
    $backdrop_path = $_POST['backdrop_path'];
    $watch_link = $_POST['watch_link'];
    $download_links = $_POST['download_links'];
    $is_hero = isset($_POST['is_hero']) ? 1 : 0; // tinyint(1)

    // بناء استعلام التحديث بناءً على أسماء الأعمدة في الصورة
    $sql = "UPDATE movies SET 
            tmdb_id = ?, 
            title_ar = ?, 
            title_en = ?, 
            release_year = ?, 
            rating = ?, 
            genres = ?, 
            overview = ?, 
            trailer_url = ?, 
            duration = ?, 
            cast_members = ?, 
            main_cat_id = ?, 
            content_type = ?, 
            sub_cat_id = ?, 
            poster_path = ?, 
            backdrop_path = ?, 
            watch_link = ?, 
            download_links = ?, 
            is_hero = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    // ربط المتغيرات (i للـ integer، s للـ string، d للـ decimal/double)
    // الترتيب: 1.tmdb_id(i), 2.title_ar(s), 3.title_en(s), 4.release_year(i), 5.rating(d), 
    // 6.genres(s), 7.overview(s), 8.trailer_url(s), 9.duration(s), 10.cast_members(s), 
    // 11.main_cat_id(i), 12.content_type(s), 13.sub_cat_id(i), 14.poster_path(s), 
    // 15.backdrop_path(s), 16.watch_link(s), 17.download_links(s), 18.is_hero(i), 19.id(i)
    
    $stmt->bind_param("issiisssssisissssii", 
        $tmdb_id, 
        $title_ar, 
        $title_en, 
        $release_year, 
        $rating, 
        $genres, 
        $overview, 
        $trailer_url, 
        $duration, 
        $cast_members, 
        $main_cat_id, 
        $content_type, 
        $sub_cat_id, 
        $poster_path, 
        $backdrop_path, 
        $watch_link, 
        $download_links, 
        $is_hero, 
        $id
    );

    if ($stmt->execute()) {
        // العودة لصفحة الأفلام مع إشعار بالنجاح
        header("Location: manage-movies.php?status=success");
        exit();
    } else {
        die("خطأ أثناء التحديث: " . $conn->error);
    }
} else {
    header("Location: manage-movies.php");
    exit();
}
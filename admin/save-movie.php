<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) { 
    exit("غير مصرح بالدخول"); 
}
include('../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. استقبال البيانات وتأمينها ضد SQL Injection
    $content_type   = $conn->real_escape_string($_POST['content_type']); 
    $tmdb_id        = intval($_POST['tmdb_id']);
    $title_ar       = $conn->real_escape_string($_POST['title_ar']);
    $title_en       = $conn->real_escape_string($_POST['title_en']);
    $release_year   = intval($_POST['release_year']);
    $rating         = $conn->real_escape_string($_POST['rating']);
    $genres         = $conn->real_escape_string($_POST['genres']);
    $overview       = $conn->real_escape_string($_POST['overview']);
    $trailer_url    = $conn->real_escape_string($_POST['trailer_url']);
    $duration       = $conn->real_escape_string($_POST['duration']);
    $cast_members   = $conn->real_escape_string($_POST['cast_members']);
    $main_cat_id    = intval($_POST['main_cat_id']);
    $sub_cat_id     = isset($_POST['sub_cat_id']) ? intval($_POST['sub_cat_id']) : 0;
    $is_hero        = isset($_POST['is_hero']) ? intval($_POST['is_hero']) : 0;
    $watch_link     = $conn->real_escape_string($_POST['watch_link']);
    $download_links = $conn->real_escape_string($_POST['download_links']);

    $slug = $conn->real_escape_string($_POST['slug']);

// لو فاضي نولده تلقائي
if (empty($slug)) {
    $slug = strtolower(trim($title_en));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');
}
    
    $original_slug = $slug;
$counter = 1;

while (true) {
    $check = $conn->query("SELECT id FROM movies WHERE slug = '$slug' LIMIT 1");
    if ($check->num_rows == 0) break;

    $slug = $original_slug . '-' . $counter;
    $counter++;
}
    
    // 2. معالجة الصور: حفظ الروابط المباشرة (Direct Links) كما جاءت من TMDB
    // تم إلغاء دالة saveImageLocally لتوفير مساحة السيرفر
    $poster_path    = $conn->real_escape_string($_POST['poster_path']);
    $backdrop_path  = $conn->real_escape_string($_POST['backdrop_path']);

    // 3. جملة الاستعلام لإضافة البيانات
    $sql = "INSERT INTO movies (
    tmdb_id, title_ar, title_en, slug, release_year, rating, 
    genres, overview, trailer_url, duration, cast_members, 
    main_cat_id, content_type, sub_cat_id, poster_path, backdrop_path, 
    watch_link, download_links, is_hero
)
            VALUES (
                '$tmdb_id', '$title_ar', '$title_en', '$slug', '$release_year', '$rating', 
                '$genres', '$overview', '$trailer_url', '$duration', '$cast_members', 
                '$main_cat_id', '$content_type', '$sub_cat_id', '$poster_path', '$backdrop_path', 
                '$watch_link', '$download_links', '$is_hero'
            )";

    if ($conn->query($sql)) {
        // النجاح والتوجه لصفحة إدارة الأفلام
        header("Location: manage-movies.php?success=1");
        exit();
    } else {
        // عرض الخطأ في حالة فشل الاستعلام
        echo "خطأ في قاعدة البيانات: " . $conn->error;
    }
}
?>
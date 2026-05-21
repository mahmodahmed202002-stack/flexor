<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include('../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['series_id'])) {
    
    $series_id  = intval($_POST['series_id']);
    $title_ar   = $conn->real_escape_string($_POST['title_ar']);
    $title_en   = $conn->real_escape_string($_POST['title_en']);
    $tmdb_id    = $conn->real_escape_string($_POST['tmdb_id']);
    $year       = $conn->real_escape_string($_POST['release_year']);
    $rating     = $conn->real_escape_string($_POST['rating']);
    $main_cat   = intval($_POST['main_cat_id']);
    $sub_cat    = isset($_POST['sub_cat_id']) ? intval($_POST['sub_cat_id']) : 0;
    $genres     = $conn->real_escape_string($_POST['genres']);
    $overview   = $conn->real_escape_string($_POST['overview']);
    $poster     = $conn->real_escape_string($_POST['poster_path']);
    $backdrop   = $conn->real_escape_string($_POST['backdrop_path']);
    $trailer    = $conn->real_escape_string($_POST['trailer_url']);
    $cast       = $conn->real_escape_string($_POST['cast_members']);
    $is_hero    = isset($_POST['is_hero']) ? 1 : 0;

    $conn->begin_transaction();

    try {
        // 1. تحديث الجدول الأساسي (series)
        $update_query = "UPDATE series SET 
            title_ar = '$title_ar', 
            title_en = '$title_en', 
            tmdb_id = '$tmdb_id', 
            release_year = '$year', 
            rating = '$rating', 
            main_cat_id = $main_cat, 
            sub_cat_id = $sub_cat, 
            genres = '$genres', 
            overview = '$overview', 
            poster_path = '$poster', 
            backdrop_path = '$backdrop', 
            trailer_url = '$trailer', 
            cast_members = '$cast', 
            is_hero = $is_hero 
            WHERE id = $series_id";

        $conn->query($update_query);

        // 2. مسح البيانات القديمة للمواسم والحلقات
        $conn->query("DELETE FROM episodes WHERE season_id IN (SELECT id FROM seasons WHERE series_id = $series_id)");
        $conn->query("DELETE FROM seasons WHERE series_id = $series_id");

        // 3. إضافة البيانات الجديدة
        if (isset($_POST['seasons']) && is_array($_POST['seasons'])) {
            foreach ($_POST['seasons'] as $s_key => $s_data) {
                $s_num = intval($s_data['number']);
                $conn->query("INSERT INTO seasons (series_id, season_number) VALUES ($series_id, $s_num)");
                $new_season_id = $conn->insert_id;

                if (isset($s_data['episodes']) && is_array($s_data['episodes'])) {
                    foreach ($s_data['episodes'] as $e_num => $e_data) {
                        $e_title = $conn->real_escape_string($e_data['title']);
                        $e_watch = $conn->real_escape_string($e_data['watch_link']);
                        $e_down  = $conn->real_escape_string($e_data['download_link']);
                        $e_dur   = $conn->real_escape_string($e_data['duration']);

                        // هنا التعديل الجوهري: تم تغيير اسم العمود لـ download_links ليطابق صورتك
                        $conn->query("INSERT INTO episodes (season_id, series_id, episode_number, title, watch_link, download_links, duration) 
                                      VALUES ($new_season_id, $series_id, $e_num, '$e_title', '$e_watch', '$e_down', '$e_dur')");
                    }
                }
            }
        }

        $conn->commit();
        echo "<script>alert('تم التحديث بنجاح طبقاً لقاعدة البيانات!'); window.location.href='manage-series.php';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "خطأ: " . $e->getMessage();
    }
}
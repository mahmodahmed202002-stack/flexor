<?php
session_start();
// التأكد من تسجيل الدخول
if(!isset($_SESSION['admin_logged_in'])) { 
    header("Location: login.php"); 
    exit(); 
}

include('../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. استلام بيانات المسلسل الأساسية وتطهيرها
    $tmdb_id        = !empty($_POST['tmdb_id']) ? $_POST['tmdb_id'] : 0;
    $title_ar       = $_POST['title_ar'];
    $title_en       = $_POST['title_en'];
    $release_year   = $_POST['release_year'];
    $rating         = $_POST['rating'];
    $genres         = $_POST['genres'];
    $overview       = $_POST['overview'];
    $cast_members   = $_POST['cast_members'];
    $main_cat_id    = $_POST['main_cat_id'];
    $sub_cat_id     = isset($_POST['sub_cat_id']) ? $_POST['sub_cat_id'] : 0;
    $trailer_url    = $_POST['trailer_url'];
    $poster_path    = $_POST['poster_path'];
    $backdrop_path  = $_POST['backdrop_path'];
    
    // البيانات الجديدة
    $content_type   = isset($_POST['content_type']) ? $_POST['content_type'] : 'series';
    $watch_link     = isset($_POST['watch_link']) ? $_POST['watch_link'] : NULL;
    $download_links = isset($_POST['download_links']) ? $_POST['download_links'] : NULL;
    $is_hero        = isset($_POST['is_hero']) ? 1 : 0;

    // ✅ التعديل الوحيد (منع الحفظ بدون sub category)
    if(empty($sub_cat_id)){
        echo "<script>alert('⚠️ لازم تختار القسم الفرعي قبل الحفظ'); window.history.back();</script>";
        exit();
    }

    // بدء Transaction
    $conn->begin_transaction();

    try {
        // إدخال المسلسل
        $sql = "INSERT INTO series (
                    tmdb_id, title_ar, title_en, release_year, rating, genres, 
                    overview, cast_members, main_cat_id, sub_cat_id, trailer_url, 
                    poster_path, backdrop_path, content_type, watch_link, 
                    download_links, is_hero, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param("isssssssiissssssi", 
            $tmdb_id, 
            $title_ar, 
            $title_en, 
            $release_year, 
            $rating, 
            $genres, 
            $overview, 
            $cast_members, 
            $main_cat_id, 
            $sub_cat_id, 
            $trailer_url, 
            $poster_path, 
            $backdrop_path,
            $content_type,
            $watch_link,
            $download_links,
            $is_hero
        );
        
        if (!$stmt->execute()) {
            throw new Exception("خطأ في حفظ بيانات المسلسل الأساسية: " . $stmt->error);
        }
        
        $series_id = $conn->insert_id;

        // المواسم والحلقات
        if (isset($_POST['seasons']) && is_array($_POST['seasons'])) {
            foreach ($_POST['seasons'] as $sIndex => $seasonData) {
                $season_number = $seasonData['number'];
                
                $stmtS = $conn->prepare("INSERT INTO seasons (series_id, season_number) VALUES (?, ?)");
                $stmtS->bind_param("ii", $series_id, $season_number);
                $stmtS->execute();
                $season_id = $conn->insert_id;

                if (isset($seasonData['episodes']) && is_array($seasonData['episodes'])) {
                    foreach ($seasonData['episodes'] as $eIndex => $episodeData) {
                        $ep_title      = $episodeData['title'];
                        $ep_duration   = $episodeData['duration']; 
                        $ep_watch_link = $episodeData['watch_link'];
                        $ep_number     = $eIndex;

                        $stmtE = $conn->prepare("INSERT INTO episodes (season_id, series_id, episode_number, title, duration, watch_link) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmtE->bind_param("iiisss", $season_id, $series_id, $ep_number, $ep_title, $ep_duration, $ep_watch_link);
                        $stmtE->execute();
                    }
                }
            }
        }

        $conn->commit();
        echo "<script>alert('تم حفظ المسلسل بنجاح!'); window.location.href='manage-series.php';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<div style='color:red; background:#fff; padding:20px; border:2px solid red; margin:20px; font-family:Cairo; direction:rtl; text-align:right;'>";
        echo "<h3>❌ حدث خطأ أثناء الحفظ:</h3>";
        echo "الرسالة: " . $e->getMessage();
        echo "<br><br><a href='add-series.php' style='color:blue;'>العودة للمحاولة مرة أخرى</a>";
        echo "</div>";
    }
}
?>
<?php
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Cairo');

include('includes/db.php');
include('includes/header.php');

// استدعاء الكروت الموحدة
$movie_card_file = 'includes/components/movie-card.php';

if (file_exists($movie_card_file)) {
    include($movie_card_file);
}

$slug = isset($_GET['slug'])
    ? trim(urldecode($_GET['slug']))
    : '';

$slug = $conn->real_escape_string($slug);

$user_id = isset($_SESSION['user_id'])
    ? (int)$_SESSION['user_id']
    : 0;

if (empty($slug)) {

    echo "<div style='margin-top:150px; text-align:center; color:#fff;'>
            <h2>الرابط غير صحيح</h2>
          </div>";

    include('footer.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GET CATEGORY
|--------------------------------------------------------------------------
*/

$cat_sql = "
    SELECT *
    FROM categories
    WHERE slug = '$slug'
    AND status = 'active'
    LIMIT 1
";

$cat_query = $conn->query($cat_sql);

$category = $cat_query
    ? $cat_query->fetch_assoc()
    : null;

if (!$category) {

    echo "<div style='margin-top:150px; text-align:center; color:#fff;'>
            <h2>القسم غير موجود!</h2>
          </div>";

    include('footer.php');
    exit;
}

$cat_id   = (int)$category['id'];
$cat_name = $category['name'];

/*
|--------------------------------------------------------------------------
| CONTENT TYPE
|--------------------------------------------------------------------------
*/

$search_pool = mb_strtolower($cat_name . ' ' . $slug);

$is_series = (
    mb_strpos($search_pool, 'مسلسل') !== false
    ||
    mb_strpos($search_pool, 'series') !== false
);

$table   = $is_series ? "series" : "movies";
$fav_col = $is_series ? "series_id" : "movie_id";

/*
|--------------------------------------------------------------------------
| SUB CATEGORIES
|--------------------------------------------------------------------------
*/

$sub_categories = $conn->query("
    SELECT *
    FROM categories
    WHERE parent_id = $cat_id
    AND status = 'active'
    ORDER BY sort_order ASC
");

?>

<div class="container main-layout"
     style="padding-top:120px; min-height:80vh; padding-bottom:50px;">

    <div class="main-cat-title"
         style="border-right:5px solid #d4ff00;
                padding-right:15px;
                margin-bottom:50px;">

        <h1 style="color:#fff; font-size:2.2rem; margin:0;">

            تصفح قسم

            <span style="color:#d4ff00;">
                <?php echo $cat_name; ?>
            </span>

        </h1>

    </div>

    <?php

    /*
    |--------------------------------------------------------------------------
    | NORMAL CATEGORY MODE
    |--------------------------------------------------------------------------
    */

    // الحالة الأولى: لو القسم رئيسي وفيه أقسام فرعية
    if ($sub_categories && $sub_categories->num_rows > 0):

        while ($sub = $sub_categories->fetch_assoc()):

            $sub_id   = $sub['id'];
            $sub_name = $sub['name'];

            $items = $conn->query("
                SELECT *,
                '$table' as content_type
                FROM $table
                WHERE sub_cat_id = $sub_id
                ORDER BY id DESC
                LIMIT 12
            ");

            if ($items && $items->num_rows > 0):
    ?>

                <div class="section-container"
                     style="margin-bottom:50px;">

                    <div class="section-header-wrap"
                         style="display:flex;
                                justify-content:space-between;
                                align-items:center;
                                margin-bottom:25px;
                                border-right:4px solid #d4ff00;
                                padding-right:15px;">

                        <h2 class="section-title"
                            style="margin:0;
                                   font-size:1.6rem;
                                   color:#fff;">

                            <?php echo $sub_name; ?>

                        </h2>

                    </div>

                    <div class="movie-grid">

                        <?php while ($row = $items->fetch_assoc()):

                            $row['id'] = $row['id']
                                ?? ($row['series_id'] ?? null);

                            $item_id = $row['id'];

                            $fav_check = $conn->query("
                                SELECT id
                                FROM favorites
                                WHERE $fav_col = $item_id
                                AND user_id = $user_id
                            ");

                            $row['is_favorite'] = (
                                $fav_check
                                &&
                                $fav_check->num_rows > 0
                            ) ? 1 : 0;

                            if (function_exists('renderMovieCard')) {
                                renderMovieCard($row);
                            }

                        endwhile; ?>

                    </div>

                </div>

    <?php

            endif;

        endwhile;

    // الحالة الثانية: قسم عادي
    else:

        $items = $conn->query("
            SELECT *,
            '$table' as content_type
            FROM $table
            WHERE sub_cat_id = $cat_id
            OR main_cat_id = $cat_id
            ORDER BY id DESC
        ");

        if ($items && $items->num_rows > 0):
    ?>

            <div class="movie-grid">

                <?php while ($row = $items->fetch_assoc()):

                    $row['id'] = $row['id']
                        ?? ($row['series_id'] ?? null);

                    $item_id = $row['id'];

                    $fav_check = $conn->query("
                        SELECT id
                        FROM favorites
                        WHERE $fav_col = $item_id
                        AND user_id = $user_id
                    ");

                    $row['is_favorite'] = (
                        $fav_check
                        &&
                        $fav_check->num_rows > 0
                    ) ? 1 : 0;

                    if (function_exists('renderMovieCard')) {
                        renderMovieCard($row);
                    }

                endwhile; ?>

            </div>

        <?php else: ?>

            <div style="text-align:center; color:#666; padding:100px;">

                <h3 style="color:#fff;">
                    لا يوجد محتوى متاح حالياً.
                </h3>

            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

<?php include('footer.php'); ?>
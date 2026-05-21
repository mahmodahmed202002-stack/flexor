<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');

// ✅ التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ✅ استقبال البيانات بشكل آمن
$movie_id  = isset($_REQUEST['movie_id']) && $_REQUEST['movie_id'] != '' ? (int)$_REQUEST['movie_id'] : null;
$series_id = isset($_REQUEST['series_id']) && $_REQUEST['series_id'] != '' ? (int)$_REQUEST['series_id'] : null;

// ✅ تحديد النوع
if (!empty($series_id)) {
    $column = 'series_id';
    $item_id = $series_id;
} elseif (!empty($movie_id)) {
    $column = 'movie_id';
    $item_id = $movie_id;
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID غير صحيح']);
    exit;
}

try {

    // 🔒 تأمين العملية (مهم جداً)
    $conn->begin_transaction();

    // 🔍 هل موجود؟
    $check = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND $column = ? LIMIT 1");
    $check->bind_param("ii", $user_id, $item_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {

        // ❌ حذف
        $del = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND $column = ?");
        $del->bind_param("ii", $user_id, $item_id);
        $del->execute();

        $conn->commit();

        echo json_encode(['status' => 'removed']);

    } else {

        // ✅ إضافة بدون تعارض
        if ($column === 'series_id') {

            $ins = $conn->prepare("
                INSERT INTO favorites (user_id, series_id, movie_id)
                VALUES (?, ?, NULL)
            ");
            $ins->bind_param("ii", $user_id, $item_id);

        } else {

            $ins = $conn->prepare("
                INSERT INTO favorites (user_id, movie_id, series_id)
                VALUES (?, ?, NULL)
            ");
            $ins->bind_param("ii", $user_id, $item_id);
        }

        $ins->execute();

        $conn->commit();

        echo json_encode(['status' => 'added']);
    }

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
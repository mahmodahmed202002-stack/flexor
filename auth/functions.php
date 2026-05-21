<?php
// ابدأ الجلسة فقط إذا لم تكن قد بدأت بالفعل
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * دالة للتحقق هل المستخدم سجل دخوله أم لا
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * دالة تجبر المستخدم على تسجيل الدخول لرؤية الصفحة
 * تستخدم في صفحات مثل (المفضلة، الملف الشخصي)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * دالة لجلب بيانات المستخدم الحالي من قاعدة البيانات
 * @param mysqli $conn اتصال قاعدة البيانات
 */
function getCurrentUser($conn) {
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    return null;
}

/**
 * دالة لتنظيف النصوص من أي محاولة اختراق (XSS)
 */
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * دالة للتحقق هل الفيلم موجود في مفضلة المستخدم أم لا
 * سنحتاج هذه الدالة لضبط شكل قلب المفضلة في السلايدر
 */
function isFavorite($conn, $user_id, $movie_id) {
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND movie_id = ?");
    $stmt->bind_param("ii", $user_id, $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}
?>
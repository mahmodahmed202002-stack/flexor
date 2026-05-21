<?php
session_start();
// إذا كان المدير مسجل دخوله بالفعل، يوجهه للوحة التحكم
if(isset($_SESSION['admin_logged_in'])) {
    header("Location: dashboard.php");
    exit();
} else {
    // إذا لم يكن مسجلاً، يوجهه لصفحة تسجيل الدخول
    header("Location: login.php");
    exit();
}
?>
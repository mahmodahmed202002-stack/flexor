<?php
$host = "sql113.infinityfree.com"; // استبدل xxx بالرقم الخاص بك
$user = "if0_41681821";             // اسم مستخدم قاعدة البيانات
$pass = "L0Ct88AnzaM0uEg";           // كلمة المرور
$dbname = "if0_41681821_flexor";   // اسم قاعدة البيانات

$conn = new mysqli($host, $user, $pass, $dbname);

// ضبط الترميز ليدعم اللغة العربية
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}
?>
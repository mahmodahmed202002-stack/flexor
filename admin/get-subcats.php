<?php
// admin/get-subcats.php
include('../includes/db.php');

if (isset($_GET['parent_id'])) {
    $parent_id = intval($_GET['parent_id']);
    
    // جلب الأقسام التي تتبع القسم الرئيسي المختار وحالتها مفعلة
    $query = "SELECT id, name FROM categories WHERE parent_id = $parent_id AND status = 'active' ORDER BY sort_order ASC";
    $result = $conn->query($query);
    
    $sub_categories = [];
    while ($row = $result->fetch_assoc()) {
        $sub_categories[] = $row;
    }

    // إرسال البيانات كـ JSON ليفهمها الـ JavaScript
    header('Content-Type: application/json');
    echo json_encode($sub_categories);
}
<?php
include('../includes/db.php');

if(isset($_POST['order'])) {
    foreach($_POST['order'] as $id => $order_val) {
        $id = intval($id);
        $order_val = intval($order_val);
        // التحديث في جدول live_channels وعمود sort_order كما في الصورة
        $conn->query("UPDATE live_channels SET sort_order = $order_val WHERE id = $id");
    }
    echo "success";
}
?>
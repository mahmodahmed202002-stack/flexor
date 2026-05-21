<?php
include('../includes/db.php');
if(isset($_POST['order'])) {
    $order = JSON_decode($_POST['order'], true);
    foreach($order as $id => $val) {
        $conn->query("UPDATE yt_channels SET sort_order = ".intval($val)." WHERE id = ".intval($id));
    }
}
?>
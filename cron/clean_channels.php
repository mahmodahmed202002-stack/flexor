<?php
    if (!isset($_GET['manual'])) {
    exit("Forbidden");
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

$conn->query("DELETE FROM live_channels WHERE status='inactive'");

echo "cleaned";
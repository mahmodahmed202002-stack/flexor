<?php
session_start();
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/db.php';

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"not_logged"]);
    exit;
}

$user_id = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $channel_id = intval($_POST['channel_id']);

    // check exist
    $check = $conn->query("SELECT id FROM favorite_channels WHERE user_id=$user_id AND channel_id=$channel_id");

    if($check->num_rows > 0){
        $conn->query("DELETE FROM favorite_channels WHERE user_id=$user_id AND channel_id=$channel_id");
        echo json_encode(["status"=>"removed"]);
    }else{
        $conn->query("INSERT INTO favorite_channels (user_id, channel_id) VALUES ($user_id,$channel_id)");
        echo json_encode(["status"=>"added"]);
    }

    exit;
}

// GET favorites
$res = $conn->query("
SELECT lc.* 
FROM live_channels lc
JOIN favorite_channels f ON f.channel_id = lc.id
WHERE f.user_id = $user_id
");

$data = [];

while($row = $res->fetch_assoc()){
    $data[] = $row;
}

echo json_encode(["status"=>"success","channels"=>$data]);
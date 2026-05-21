<?php

$id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($id <= 0) {
    http_response_code(404);
    exit;
}

$image = file_get_contents(
    'https://api.sofascore.app/api/v1/team/' . $id . '/image'
);

if (!$image) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/png');

echo $image;
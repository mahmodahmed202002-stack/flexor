<?php
require_once 'includes/db.php';

function createSlug($text){
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/* ========= MOVIES ========= */
$res = mysqli_query($conn, "SELECT id, title_en FROM movies");

while($row = mysqli_fetch_assoc($res)){
    $slug = createSlug($row['title_en']);

    mysqli_query($conn, "
        UPDATE movies 
        SET slug = '$slug' 
        WHERE id = ".$row['id']."
    ");
}

/* ========= SERIES ========= */
$res2 = mysqli_query($conn, "SELECT id, title_en FROM series");

while($row = mysqli_fetch_assoc($res2)){
    $slug = createSlug($row['title_en']);

    mysqli_query($conn, "
        UPDATE series 
        SET slug = '$slug' 
        WHERE id = ".$row['id']."
    ");
}

echo "DONE";
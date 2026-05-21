<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$api_key = '03d57ba3-54ae-47db-a624-dc86f3442ceb';

$url = "https://app.highlightly.net/api/football/leagues";

$ch = curl_init();

curl_setopt_array($ch, [

    CURLOPT_URL => $url,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_FOLLOWLOCATION => true,

    CURLOPT_SSL_VERIFYPEER => false,

    CURLOPT_SSL_VERIFYHOST => false,

    CURLOPT_TIMEOUT => 30,

    CURLOPT_HTTPHEADER => [

        "Authorization: Bearer " . $api_key,
        "Accept: application/json",
        "User-Agent: Mozilla/5.0"

    ]

]);

$response = curl_exec($ch);

$error = curl_error($ch);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

echo "<h1>HTTP CODE: {$http_code}</h1>";

echo "<h3>CURL ERROR:</h3>";

echo "<pre>";
print_r($error);
echo "</pre>";

echo "<h3>RESPONSE:</h3>";

echo "<pre>";
print_r($response);
echo "</pre>";
<?php

error_reporting(E_ALL);

ini_set('display_errors', 1);

$url = "https://webws.365scores.com/web/competitions/?appTypeId=5&langId=31";

$response = file_get_contents($url);

if (!$response) {

    die('Failed To Load API');

}

$data = json_decode($response, true);

echo '<pre>';

if (!empty($data['competitions'])) {

    foreach ($data['competitions'] as $competition) {

        $id = $competition['id'] ?? '---';

        $name = $competition['name'] ?? '---';

        $country = $competition['countryName'] ?? '---';

        echo "ID: " . $id;

        echo " | ";

        echo "COUNTRY: " . $country;

        echo " | ";

        echo "NAME: " . $name;

        echo "\n";

    }

} else {

    echo "NO COMPETITIONS FOUND";

    echo "\n\n";

    print_r($data);

}

echo '</pre>';
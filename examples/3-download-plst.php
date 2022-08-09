<?php

require_once '../vendor/autoload.php';
require_once '../src/Ytdl.php';
require_once '../src/Options.php';

use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Ytdl;


// download a playlist
$webpage_url = 'https://soundcloud.com/lg2-3/sets/amiral-prose-monthly-radio';

$ytdl_options = new Options();
$ytdl_options->addOptions(['-I' => '1,3']);

$ytdl = new Ytdl($ytdl_options);
$ytdl->setCache(['directory' => 'cache']);

// this can not use directly the cache informations :-(
// see 4-download-plst-with-cache.php
$info_dict = $ytdl->download($webpage_url, 'data');
$errors = $ytdl->getErrors();


if (count($errors) !== 0){
    echo "<pre>" . implode(' ', $errors) . "</pre>";
} else {
    header('Content-Type: application/json');
    $json_string = json_encode($info_dict, JSON_PRETTY_PRINT);
    echo $json_string;
}

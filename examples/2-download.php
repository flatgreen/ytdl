<?php

require_once '../vendor/autoload.php';
require_once '../src/Ytdl.php';
require_once '../src/Options.php';

use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Ytdl;

// 'cache and 'data' directories must exist and have write permissions

// download a video
$webpage_url = 'https://www.youtube.com/watch?v=BaW_jenozKc';
$folder_to_download = 'data';

$ytdl_options = new Options();
$ytdl_options->setOptions(['-f' => '18/worst']);

$ytdl = new Ytdl($ytdl_options);
$info_dict = $ytdl->download($webpage_url, null, $folder_to_download);
$errors = $ytdl->getErrors();


if (count($errors) !== 0){
    echo "<pre>" . implode(' ', $errors) . "</pre>";
} else {
    header('Content-Type: application/json');
    $json_string = json_encode($info_dict, JSON_PRETTY_PRINT);
    echo $json_string;
}

<?php

require_once '../vendor/autoload.php';

use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Ytdl;

$webpage_url = 'https://www.youtube.com/watch?v=BaW_jenozKc';

$ytdl_options = new Options();
$ytdl_options->setOptions(['-f' => '18/worst']);

$ytdl = new Ytdl($ytdl_options);
// 'cache' directory with read|write permissions
$ytdl->setCache(['directory' => 'cache']);

$info_dict = $ytdl->extractInfos($webpage_url);
$errors = $ytdl->getErrors();

if (count($errors) !== 0){
    echo "<pre>" . implode(' ', $ytdl->getErrors()) . "</pre>";
} else {
    header('Content-Type: application/json');
    $json_string = json_encode($info_dict, JSON_PRETTY_PRINT);
    echo $json_string;
}

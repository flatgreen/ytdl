<?php

require_once '../vendor/autoload.php';
require_once '../src/Ytdl.php';
require_once '../src/Options.php';

use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Ytdl;

// 'cache and 'data' directories must exist and have write permissions

// download a playlist
$webpage_url = 'https://soundcloud.com/lg2-3/sets/amiral-prose-monthly-radio';

$ytdl_options = new Options();
// Write video metadata to a .info.json file
$ytdl_options->addOptions(['--write-info-json']);

$ytdl = new Ytdl($ytdl_options);
$ytdl->setCache(['directory' => 'cache']);

// extract all the playlist
$info_dict = $ytdl->extractInfos($webpage_url);

// download the items 1 and 3 - use the cache system
// $yt_pl_1 with only the item 1 informations
$yt_pl_1 = $ytdl->download($webpage_url, 'data', $info_dict['entries'][0]);
$yt_pl_3 = $ytdl->download($webpage_url, 'data', $info_dict['entries'][2]);
$errors = $ytdl->getErrors();


if (count($errors) !== 0){
    echo "<pre>" . implode(' ', $errors) . "</pre>";
} else {
    header('Content-Type: application/json');
    $json_string = json_encode($info_dict, JSON_PRETTY_PRINT);
    echo $json_string;
}

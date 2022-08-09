<?php

require_once '../vendor/autoload.php';
require_once '../src/Ytdl.php';
require_once '../src/Options.php';

use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Ytdl;

// 'cache and 'data' directories must exist and have write permissions

$webpage_url = 'https://www.youtube.com/watch?v=BaW_jenozKc';

$ytdl_options = new Options();
$ytdl_options->addOptions(['-f' => '18']);

$ytdl = new Ytdl($ytdl_options);
$info_dict = $ytdl->extractInfos($webpage_url);

// Change the options (use the cache)
$ytdl_options->addOptions(['-f' => '22', '-o' => 'data/%(title)s -- %(format_id)s [%(id)s].%(ext)s']);
$ytdl->setOptions($ytdl_options);

$info_dict = $ytdl->download($webpage_url, 'data', $info_dict);
$errors = $ytdl->getErrors();

if (count($errors) !== 0){
    echo "<pre>" . implode(' ', $errors) . "</pre>";
} else {
    header('Content-Type: application/json');
    $json_string = json_encode($info_dict, JSON_PRETTY_PRINT);
    echo $json_string;
}

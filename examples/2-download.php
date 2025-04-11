<?php

require_once '../vendor/autoload.php';
require_once '../src/Ytdl.php';
require_once '../src/Options.php';

use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Ytdl;

// 'cache and 'data' directories must exist and have write permissions

// download a video
$webpage_url = 'https://www.youtube.com/watch?v=DTi8wZ1a1TA';
$download_dir = 'data';

$ytdl_options = new Options();
$ytdl_options->addOptions(['-f' => '18/worst']);

$ytdl = new Ytdl($ytdl_options);
$ytdl->setCache(['directory' => 'cache']);
$info_dict = $ytdl->download($webpage_url, $download_dir);
$errors = $ytdl->getErrors();

if (count($errors) !== 0) {
    echo "<pre>" . implode(' ', $errors) . "</pre>";
} else {
    header('Content-Type: application/json');
    $json_string = json_encode($info_dict, JSON_PRETTY_PRINT);
    echo $json_string;
}

<?php

require_once '../vendor/autoload.php';
require_once '../src/Ytdl.php';
require_once '../src/Options.php';

use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Ytdl;


// extract a playlist
// full 4 videos
$webpage_url = 'https://www.youtube.com/playlist?list=PLm5uVy7nNXqiA3Ykbj9pAouApqBOUCYHd';

$ytdl_options = new Options();
$ytdl_options->setOptions(['-f' => '18/worst']);

$ytdl = new Ytdl($ytdl_options);
$info_dict = $ytdl->extractInfos($webpage_url);

$errors = $ytdl->getErrors();
if (count($errors) !== 0){
    echo "<pre>" . implode(' ', $ytdl->getErrors()) . "</pre>";
} else {
    header('Content-Type: application/json');
    $json_string = json_encode($info_dict, JSON_PRETTY_PRINT);
    echo $json_string;
}


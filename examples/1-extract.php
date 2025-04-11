<?php

require_once '../vendor/autoload.php';

use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Ytdl;

// old yyoutube-dl example, error now
// $webpage_url = 'https://www.youtube.com/watch?v=BaW_jenozKc';
$webpage_url = 'https://www.youtube.com/watch?v=DTi8wZ1a1TA';

$ytdl_options = new Options();
$ytdl_options->addOptions(['-f' => '18/worst']);
// with the format option, we can have an $info_dict['url'].

$ytdl = new Ytdl($ytdl_options);
$info_dict = $ytdl->extractInfos($webpage_url);

$errors = $ytdl->getErrors();

if (count($errors) !== 0) {
    echo "<pre>" . implode(' ', $ytdl->getErrors()) . "</pre>";
} else {
    header('Content-Type: application/json');
    $json_string = json_encode($info_dict, JSON_PRETTY_PRINT);
    echo $json_string;
}

// try this script once again and enjoy the cache system !

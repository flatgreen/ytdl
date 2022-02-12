<?php

require_once '../vendor/autoload.php';

use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Ytdl;

// options : command line options
$ytdl_options = new Options();
$ytdl_options->setOptions(['--version']);

$ytdl = new Ytdl($ytdl_options);
echo($ytdl->run());
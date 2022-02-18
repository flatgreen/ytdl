# Ytdl

PHP wrapper for youtube-dl.

[youtube-dl](https://github.com/ytdl-org/youtube-dl) may be dead but still works on many sites. 

 Two goals:
 - be close to the command line of youtube-dl
 - minimize requests, while having the maximum amount of information

## Prerequisites
- php >= 7.3 | 8.0 (not tested on 8.1)
- mbstring extension for phpunit
- [youtube-dl !](https://github.com/ytdl-org/youtube-dl#installation)

## Installation
- Use the package manager composer to install Ytdl.
```bash
composer require flatgreen/ytdl
```
- Create a 'cache' directory (with read|write permissions), because we want a cache system.

## Usage
Load the two classes:

```php
require_once 'vendor/autoload.php';
use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Ytdl;
```

Define the options:
All the [options for youtube-dl](https://github.com/ytdl-org/youtube-dl#options) write in array manner. No URL here.

```php
ytdl_options = new Options();
// merge with default options
$ytdl_options->addOptions(['-f' => '18/worst']);
// or impose (without defaults) options
// $ytdl_options->setOptions(['-f' => '18/worst']);
```

Instantiate the class, define a video url;
```php
$ytdl = new Ytdl($ytdl_options);

// optional, change cache options (default 'cache' directory and 86400 sec.):
$ytdl->setCache(['directory' => 'cache', 'duration' => 3600])

$webpage_url = 'https://www.youtube.com/watch?v=BaW_jenozKc';
```

Optional: read the video informations
```php
$info_dict = $ytdl->extractInfos($webpage_url);
$errors = $ytdl->getErrors();
```

Download the video (if there are some extrated informations, use the cache and limit the number of requests). The download function can be use in another script.

```php
$info_dict = $ytdl->download($webpage_url);
$errors = $ytdl->getErrors();
```

## Examples
- [example-0](/examples/0-version.php) with just a 'version' option
- [example-1](/examples/1-extract.php) extract example
- [example-2](/examples/2-download.php) download a video
- [example-2-2](/examples/2-extract-download.php) download with a change of options and explicit use of the cache
- more to come, with playlist and indexes...

## Todo
- explore the [yt-dlp](https://github.com/yt-dlp/yt-dlp) command line
- fix somme issues with playlist indexes
- complete the test suit

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License
ytdl is licensed under the MIT License (MIT). Please see the [license file](/LICENSE) for more information.






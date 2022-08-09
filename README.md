# Ytdl

PHP wrapper for youtube-dl or yt-dlp.

Two goals:
- be close to the command line of youtube-dl
- minimize requests, while having the maximum amount of information

## Prerequisites
- php >= 7.4
- [youtube-dl](https://github.com/ytdl-org/youtube-dl#installation) or [yt-dlp](https://github.com/yt-dlp/yt-dlp#installation)

## Installation
- Use the package manager [composer](https://getcomposer.org/) to install Ytdl.
```bash
composer require flatgreen/ytdl
```
- Optional: Create a 'cache' directory (with read|write permissions), by default the cache directory is inside the system temporary directory.

## Usage
### Load the two classes:

```php
require_once 'vendor/autoload.php';
use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Ytdl;
```

### Define the options:

All in array manner. No URL here.

see : [options for youtube-dl](https://github.com/ytdl-org/youtube-dl#options) or [options for yt-dlp](https://github.com/yt-dlp/yt-dlp#usage-and-options)

```php
$ytdl_options = new Options();
// merge with default options
$ytdl_options->addOptions(['-f' => '18/worst']);
```

Instantiate the class, define a video url;
```php
$ytdl = new Ytdl($ytdl_options);
// optional, change cache options
// default temporary cache directory and duration 3600 sec.
// no cache with ['duration' => 0]
$ytdl->setCache(['directory' => 'cache', 'duration' => 7200])
$webpage_url = 'https://www.youtube.com/watch?v=BaW_jenozKc';
```

Read the video informations
```php
$info_dict = $ytdl->extractInfos($webpage_url);
$errors = $ytdl->getErrors();
```
If you want the url of the media ($info_dict['url']), you must pass a format ($ytdl_options->addOptions(['f' => 'some_format'])).

### Download a video

```php
$info_dict = $ytdl->download($webpage_url);
// with a download directory
// $info_dict = $ytdl->download($webpage_url, $directory_to_download);
// with an explicit $info_dict
// $new_info_dict = $ytdl->download($webpage_url, $directory_to_download, $info_dict);
$errors = $ytdl->getErrors();
```

## Examples
- [example-0](/examples/0-version.php) with just '--version' option
- [example-1](/examples/1-extract.php) extract example
- [example-1-1](/examples/1-1-extract-plst.php) extract from a playlist
- [example-2](/examples/2-download.php) download a video (cache and download folders)
- [example-2-2](/examples/2-2-download.php) download with a change of options
- [example-3](/examples/3-download-plst.php) download a playlist (no cache, not optimal)
- [example-4](/examples/4-download-plst-with-cache.php) extract all informations from a playlist and download some videos with cache system.

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License
ytdl is licensed under the MIT License (MIT). Please see the [license file](/LICENSE) for more information.
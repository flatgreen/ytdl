# Ytdl

PHP wrapper for youtube-dl or yt-dlp.

Two goals:
- be close to the command line of youtube-dl
- minimize requests, while having the maximum amount of information

## Prerequisites
- php >= 7.4
- [youtube-dl](https://github.com/ytdl-org/youtube-dl#installation) or [yt-dlp](https://github.com/yt-dlp/yt-dlp#installation)
- Strongly recommended: ffmpeg. See [yt-dlp dependencies](https://github.com/yt-dlp/yt-dlp?tab=readme-ov-file#dependencies)

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

// with a direct string commnd line
$ytdl_options->addRawOptions('--one_alone --with_value value --second_alone -t');
```

### Instantiate and extract information:
Instantiate the class:
```php
$ytdl = new Ytdl($ytdl_options);
// optional, change cache options
// default temporary cache directory and duration 3600 sec.
// no cache with ['duration' => 0]
$ytdl->setCache(['directory' => 'cache', 'duration' => 7200])
```

If you want to set the ytdl executable path, you need to pass the value like below. This will skip the automatic scan (in PATH) of the executable. It is your responsibility to set this path correctly.
```php
$ytdl = new Ytdl($ytdl_options, null, 'usr/share/local/yt-dlp');
```

Define a video url:
```php
$webpage_url = 'https://www.youtube.com/watch?v=DTi8wZ1a1TA';
```

Read the video informations
```php
$info_dict = $ytdl->extractInfos($webpage_url);
$errors = $ytdl->getErrors();
```
With some format (like ['-f' => 'bv*+ba']), there is no $info_dict['url'], but the media can be downloaded with this $info_dict.

At this moment, $info_dict is in the cache.

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

## Versions
See [CHANGELOG](/CHANGELOG.md)

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License
ytdl is licensed under the MIT License (MIT). Please see the [license file](/LICENSE) for more information.
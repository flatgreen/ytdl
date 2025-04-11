<?php
/*
 * (c) flatgreen <flatgreen@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flatgreen\Ytdl;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Utils;

/**
 * ytdl class, a wrapper for youtube-dl or yt-dlp.
 *
 * youtube-dl (python) https://github.com/rg3/youtube-dl
 * yt-dlp (python) https://github.com/yt-dlp/yt-dlp
 *
 * All usefull informations is in (array) $info_dict
 * (the same structure than youtube-dl)
 *
 */
class Ytdl
{
    /**
     * ytdl_exec hold the executable path for youtube-dl.
     * @var string|null
     */
    private $ytdl_exec;

    /**
     * info_dict
     *
     * Content for a single entry:
     * - id (youtube-dl id) (require for real download)
     * - title (require)
     * - webpage_url (optional, but good if a prob occure) = page link/url
     * - url (require for real media download, if 'formats' not present)
     * - ext (media extension)
     * - format (require for real download)
     * - _filename is the downloaded media filename (not in info_dict extracted), see '--no-clean-info-json' with yt-dlp
     *
     * - and much more ... (but not require for dl)
     *
     * if info_dict is a playlist:
     * info_dict = ['_type' => 'playlist', 'entries' => []] (perhaps not in yt-dlp...)
     *
     * @var mixed[]
     */
    private $info_dict;

    /**
     * psr3 compatible logger
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * errors
     * @var string[]
     */
    private array $errors = [];

    /**
     * cache_options.
     *
     * 'directory' cache directory, 'duration' in seconde
     *
     * Default ['directory' => null, 'duration' => 3600]
     *
     * @var mixed[]
     */
    private array $cache_options = ['directory' => null, 'duration' => 3600];

    /**
     * @see Options.php
     * @var Options
     */
    private Options $options;

    /**
     * @param Options $options
     * @param LoggerInterface|null $logger
     * @param string|null $ytdl_exec absolute path, set this path correctly !
     */
    public function __construct(Options $options, LoggerInterface $logger = null, string $ytdl_exec = null)
    {
        if (null === $logger) {
            $logger = new NullLogger();
        }
        $this->logger = $logger;
        $this->options = $options;

        if($ytdl_exec) {
            $this->ytdl_exec = $ytdl_exec;
        }

        if(null === $ytdl_exec) {
            $ytdl_finder = new ExecutableFinder();
            // try first yt-dlp
            $this->ytdl_exec = $ytdl_finder->find('yt-dlp');
            if (null == $this->ytdl_exec) {
                $this->ytdl_exec = $ytdl_finder->find('youtube-dl');
            }
        }
        $this->logger->debug('ytdl executable: ' . $this->ytdl_exec);
        $this->errors = [];
    }

    /**
     * setOptions, pass new options (without using the default ones)
     *
     * @param  Options $options
     * @return void
     */
    public function setOptions(Options $options): void
    {
        $this->options = $options;
    }

    /**
     * setCache.
     * Override the default cache setting
     *
     * @param  mixed[] $directory_duration = ['directory' => 'path'|null, 'duration' => int (sec.)]
     * @return void
     */
    public function setCache(array $directory_duration = []): void
    {
        if (!empty($directory_duration)) {
            $this->cache_options = array_merge($this->cache_options, $directory_duration);
        }
    }

    /**
     * setYtdlPath.
     * Override the automatic find ytdl executable path.
     *
     * @param  string $ytdl
     * @return void
     */
    public function setYtdlExecPath(string $ytdl): void
    {
        $this->ytdl_exec = $ytdl;
        $this->logger->debug('ytdl executable: ' . $this->ytdl_exec);
    }

    /**
     * Get the absolute executable path
     *
     * @return string
     */
    public function getYtdlExecPath(): string
    {
        return ($this->ytdl_exec) ?? '';
    }

    /**
     * getYtdlExecName.
     * @return string
     */
    public function getYtdlExecName(): string
    {
        return (is_null($this->ytdl_exec)) ? '' : pathinfo($this->ytdl_exec, PATHINFO_FILENAME);
    }

    /**
     * createProcess
     *
     * @param  string[] $arguments for ytdl exec (real) process
     * @return Process $process
     * @throws \Exception if no executable path
     */
    private function createProcess(array $arguments, int $time_out = 0): Process
    {
        if ($this->ytdl_exec === null) {
            $msg = 'No ytdl executable';
            $this->logger->debug($msg);
            throw new \Exception($msg);
        }
        // TODO $time_out is never accessible in public functions
        set_time_limit($time_out);
        $process = new Process(array_merge([$this->ytdl_exec], $arguments));
        $process->setTimeout($time_out);
        $this->logger->debug(__FUNCTION__ . ' cmdline: ' . $process->getCommandLine());
        return $process;
    }

    /**
     * run.
     * Simple launcher for ytdl exec
     *
     * @throws \Exception if no process success
     * @return string normal output
     */
    public function run(): string
    {
        $process = $this->createProcess($this->options->getOptions());
        $process->run();
        if (!$process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput());
            $exitCode = $process->getExitCode();
            $msg = __FUNCTION__ . ' ExitCode: ' . $exitCode . ' -- ' . $errorOutput;
            $this->logger->error($msg);
            throw new \Exception($msg);
        }
        return trim($process->getOutput());
    }

    /**
     * isPlaylist
     *
     * Detect 'playlist' in $info_dict
     *
     * @param  mixed[] $info_dict
     * @return bool
     */
    public function isPlaylist(array $info_dict = null)
    {
        return (($info_dict['_type'] ?? null) === 'playlist');
    }

    /**
     * outError
     * Collect errors messages for the class
     *
     * @param  string $message
     * @return void
     */
    private function outError(string ...$message): void
    {
        $output = implode(' ', $message);
        $this->logger->error($output);
        $this->errors[] = $output;
    }

    /**
     * extractInfos.
     *
     * Fill info_dict and errors. info_dict is cached (even with playlist).
     *
     * To see ytdl errors: getErrors(),
     *
     * @param string $url like webpage_url in ytdl info_dict
     * @return mixed[] $info_dict or [] if errors
     */
    public function extractInfos(string $url): array
    {
        $this->info_dict = [];

        $arguments = $this->options->getOptions();
        $arguments[] = '--dump-single-json'; // no dl & quiet ('--print-json'; // dl & quiet)
        $arguments[] = $url;

        // cache system
        $cache = new FilesystemAdapter('ytdl', $this->cache_options['duration'], $this->cache_options['directory']);
        $this->logger->debug('Cache: directory `' . ($this->cache_options['directory'] ?? 'temporary') . '` duration `' . (string) $this->cache_options['duration'] . '`');
        // with cache
        $info_dict_cached = $cache->getItem(md5($url));
        if ($info_dict_cached->isHit()) {
            $this->logger->debug('load from cache: ' . $url);
            return $this->info_dict = $info_dict_cached->get();
        }

        // without cache
        $process = $this->createProcess($arguments);
        $this->logger->debug(__FUNCTION__ . ' ' . $process->getCommandLine());
        // write error during process
        $funct_name = __FUNCTION__;
        $process->run(function ($type, $buffer) use ($funct_name) {
            if (Process::ERR === $type) {
                $this->outError($funct_name, $buffer);
            }
        });

        $normalOutput = trim($process->getOutput());
        if (!empty($normalOutput) && ($normalOutput != 'null')) {
            $this->info_dict = json_decode($normalOutput, true);
            $this->info_dict = $this->sanitize($this->info_dict);
            $info_dict_cached->set($this->info_dict);
            $cache->save($info_dict_cached);
            $this->logger->debug('write to cache for url: ' . $url);
        }

        return $this->info_dict;
    }

    /**
     * realDownload.
     *
     * Do the real download, with an $info_dict,
     * function use by 'download'
     *
     * @param  string[] $arguments arguments for Process
     * @param  mixed[]  $info_dict
     * @param  string   $data_folder directory path to download with final '/'
     * @return mixed[]  $info_dict new one if download, else []
     */
    private function realDownload(array $arguments, array $info_dict, string $data_folder): array
    {
        $info_dict_return = [];
        $is_playlist = $this->isPlaylist($info_dict);
        // if this is a playlist, no cache system
        if (!$is_playlist) {
            // info_dict as file
            $arguments[] = '--load-info-json';
            $tmp = tempnam(sys_get_temp_dir(), 'ytdl');
            if ($tmp === false) {
                $this->outError(__FUNCTION__, ' No tmp file');
                return $info_dict;
            }
            if (false === file_put_contents($tmp, json_encode($info_dict))) {
                $this->outError(__FUNCTION__, 'Write error, no info_dict in tmp file');
                return $info_dict;
            }
            $arguments[] = $tmp;
        }

        // we will have a nice fresh info_dict
        $arguments[] = '--print-json'; // not recommanded by yt-dlp, but work

        // output template: '-o' (or '--output') user has priority over $data_folder
        if (empty(array_intersect(['-o', '--output'], $arguments))) {
            $arguments[] = '-o';
            $arguments[] = $data_folder . '%(title)s [%(id)s].%(ext)s';
        }
        $arguments[] = $info_dict['webpage_url'];

        $process = $this->createProcess($arguments);
        $process->run();
        $output = trim($process->getOutput());

        // ytdl return for each dl each $info_dict in one output
        if ($is_playlist) {
            $info_dict['entries'] = [];
            $arr_output = preg_split('/\r\n|\r|\n/', $output);
            if ($arr_output !== false) {
                foreach ($arr_output as $a_output) {
                    $info_dict['entries'][] = json_decode($a_output, true);
                }
                $info_dict_return = $info_dict;
            }
        } else {
            // one entrie
            $info_dict_return = json_decode($output, true);
        }

        if (!$process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput());
            if (!empty($errorOutput)) {
                $this->outError(__FUNCTION__, $errorOutput);
            }
            if (isset($info_dict_return['_filename'])) {
                unset($info_dict_return['_filename']);
            }
        }

        if (isset($tmp)) {
            unlink($tmp);
        }
        return $info_dict_return;
    }


    /**
     * download.
     *
     * From an $info_dict download the video, else run ytdl with extractInfos.
     *
     * '-o' or '--output' has priority over $data_folder
     *
     * it's a good idea to read errors (getErrors()).
     *
     * @param  string $link (same as 'webpage_url')
     * @param  mixed[]|null $info_dict
     * @param  string|'' $data_folder directory path to download (final '/' or not). Not use if '-o' option.
     * @return mixed[] $info_dict fresh one (or empty)
     */
    public function download(string $link, string $data_folder = '', array $info_dict = null): array
    {
        $arguments = $this->options->getOptions();
        // if not clear : $data_folder = ./
        $data_folder = ($data_folder != '') ? (($data_folder == '/') ? './' : rtrim($data_folder, '\/') . '/') : $data_folder;

        // priority : 1- $info_dict, 2- $this->info_dict, 3- $this->extractInfos();
        if (!empty($info_dict)) {
            $this->info_dict = $info_dict;
        } elseif (empty($this->info_dict)) {
            $this->extractInfos($link);
        } // now we have : $this->info_dict;
        if (empty($this->info_dict)) {
            return [];
        }

        $this->info_dict = $this->realDownload($arguments, $this->info_dict, $data_folder);
        return $this->info_dict;
    }

    /**
     * sanitize
     *
     * Only for playlist.
     * Remove bad/null item in $info_dict.
     * Detect and rename duplicate for 'title'.
     *
     * @param  mixed[] $info_dict
     * @return mixed[] $info_dict
     */
    private function sanitize(array $info_dict)
    {
        if ($this->isPlaylist($info_dict)) {
            foreach ($info_dict['entries'] as $k => $entry) {
                if (empty($entry) or empty($entry['title'])) {
                    unset($info_dict['entries'][$k]);
                    $this->logger->debug('remove null entry: ' . $k);
                }
            }
            $info_dict['entries'] = Utils::changeArrayWithUniqueValueFor($info_dict['entries'], 'title');
        }
        return $info_dict;
    }

    /**
     * getInfoDict
     *
     * @return mixed[]
     */
    public function getInfoDict(): array
    {
        return $this->info_dict;
    }

    /**
     * getErrors
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

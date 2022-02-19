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
use Flatgreen\Ytdl\FileCache;
use Flatgreen\Ytdl\Utils;


/**
 * ytdl class, a wrapper for youtube-dl.
 * 
 * Two goals:
 * - be close to the command line of youtube-dl (see: Options)
 * - minimize requests, while having the maximum amount of information (with a cache)
 * 
 * the usefull informations is in (array) $info_dict
 * (the same structure than youtube-dl)
 * 
 * require: youtube-dl (python) https://github.com/rg3/youtube-dl
 */


class Ytdl {

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
     * - url (require for real media download)
     * - ext (media extension)
     * - format (require for real download)
     * - _filename is the downloaded media filename (not in info_dict extracted)
     * 
     * - and much more ... (but not require for dl)
     * 
     * if info_dict is a playlist:
     * info_dict = ['_type' => 'playlist', 'entries' => []]
     * 
     * @var mixed[]
     */
    private $info_dict;

    /**
     * psr3 compatible logger
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * errors
     * @var string[]
     */
    private $errors;

    /**
     * cache_options.
     * 
     * - 'directory' cache directory,
     * - 'duration' in seconde
     * 
     * Default in __construct : ['directory' => null, 'duration' => 3600]
     * 
     * @var mixed[]
     */
    private $cache_options;
        
    /**
     * @see Options.php
     * @var Options
     */
    private $options;


    public function __construct(Options $options, LoggerInterface $logger = null){
        if (null === $logger) {
            $logger = new NullLogger();
        }
        $this->logger = $logger;
        $this->options = $options;

        // ytdl format option ('-f' or '--format') must be set to have $info_dict['url']
        // For downloading with info_dict in cache (else take the 'best')
        if (empty(array_intersect(['-f', '--format'], $this->options->getOptions()))){
            $this->options->addOptions(['-f' => 'best']);
        }

        $ytdl_finder = new ExecutableFinder;
        // $this->ytdl_exec = '/usr/bin/youtube-dl'; 
        // or $this->ytdl_exec = '/usr/local/bin/youtube-dl'
        // TODO see for ytdl-dlp
        $this->ytdl_exec = $ytdl_finder->find('youtube-dl');
        if ($this->ytdl_exec === null){
            $msg = 'No youtube-dl executable - see: https://github.com/ytdl-org/youtube-dl#installation';
            $this->logger->debug($msg);
            throw new \Exception($msg);
        }
        $this->logger->debug('youtube-dl executable: ' . $this->ytdl_exec);
        $this->cache_options = ['directory' => null, 'duration' => 3600];
        $this->errors = [];
    }


    /**
     * setOptions, can pass the options (once or new ones)
     *
     * @param  Options $options
     * @return void
     */
    public function setOptions(Options $options){
        $this->options = $options;
    }

    
    /**
     * setCache
     * 
     * Override the default cache setting
     *
     * @param  mixed[] ['directory' => default null or 'path/to/cache', 'duration' => int default 3600 sec]
     * @return void
     */
    public function setCache(array $directory_duration = []){
        if (!empty($directory_duration)){
            $this->cache_options = array_merge($this->cache_options, $directory_duration);
        }
    }


    /**
     * setYtdlPath.
     * Override the automatic find youtube-dl executable path.
     *
     * @param  string $ytdl
     * @return void
     */
    public function setYtdlExecPath(string $ytdl){
        $this->ytdl_exec = $ytdl;
    }


    /**
     * createProcess
     *
     * @param  string[] $arguments for youtube-dl (real) process
     * @return Process $process
     */
    private function createProcess(array $arguments, int $time_out = 0){
        set_time_limit($time_out);
        $process = new Process(array_merge([$this->ytdl_exec], $arguments));
        $process->setTimeout($time_out);
        return $process;
    }


    /**
     * run
     * 
     * Simple launcher for youtube-dl with $options
     * 
     * @throws \Exception if no process success
     * @return string normal output
     */
    public function run(){
        $process = $this->createProcess($this->options->getOptions());
        $this->logger->debug(__FUNCTION__ . ' ' . $process->getCommandLine());
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
    public function isPlaylist(array $info_dict = null){
        return (($info_dict['_type'] ?? null) === 'playlist');
    }

    
    /**
     * outError
     * Collect errors messages for the class
     *
     * @param  string $message
     * @return void
     */
    private function outError(string ...$message){
        $output = implode(' ', $message);
        $this->logger->error($output);
        $this->errors[] = $output;
    }


    /**
     * extractInfos
     * 
     * Fill info_dict and errors. info_dict is cached.
     * 
     * If there is a playlist, all the playlist infos are extracted (and cached).
     * 
     * If errors then return $info_dict=[], get them with getErrors(), 
     * 
     * @param string $link like webpage_url in ytdl info_dict
     * @return mixed[] $info_dict
     */
    public function extractInfos(string $link){
        $this->info_dict = [];

        // save and remove '--playlist-*'
        $playlist_cmd_save = [];
        foreach(['--playlist-start', '--playlist-end', '--playlist-items'] as $pl_cmd){
            $pl_opt = $this->options->getOption($pl_cmd);
            if (!empty($pl_opt)){
                $playlist_cmd_save[$pl_cmd] = $pl_opt;
                $this->options->removeOption($pl_cmd);
            }
        }

        $arguments = $this->options->getOptions();
        $arguments[] = '--dump-single-json'; // no dl & quiet ('--print-json'; // dl & quiet)
        $arguments[] = $link;

        // cache system
        $cache = new FilesystemAdapter('ytdl', $this->cache_options['duration'], $this->cache_options['directory']);

        // with cache
        $info_dict_cached = $cache->getItem(md5($link));
        if ($info_dict_cached->isHit()){
            $this->logger->debug('load from cache: ' . $link);
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
        if (!empty($normalOutput) && ($normalOutput != 'null')){
            $this->info_dict = json_decode($normalOutput, true);
            $this->info_dict = $this->sanitize($this->info_dict);
            // TODO Exception/Error ? 'Fatal error: Allowed memory size of'
            // save to cache
            $info_dict_cached->set($this->info_dict);
            $cache->save($info_dict_cached);
            $this->logger->debug('write to cache for url: ' . $link);
        }
        // restore playlist options
        $this->options->addOptions($playlist_cmd_save);
        
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
    private function sanitize(array $info_dict){
        if ($this->isPlaylist($info_dict)){
            foreach($info_dict['entries'] as $k => $entry){
                if (empty($entry) or empty($entry['title'])){
                    unset($info_dict['entries'][$k]);
                    $this->logger->debug('remove null entry: ' . $k);
                }
            }
            $info_dict['entries'] = Utils::changeArrayWithUniqueValueFor($info_dict['entries'], 'title');
        }
        return $info_dict;
    }


    /**
     * realDownload
     * 
     * Do the real download, with an $info_dict_single,
     * function use by 'download'
     *
     * @param  string[] $arguments arguments for Process
     * @param  mixed[]  $info_dict_single
     * @param  string   $data_folder directory path to download with final '/'
     * @return mixed[]  $info_dict new one if download, else $info_dict_single
     */
    private function realDownload(array $arguments, array $info_dict_single, string $data_folder){
        // info_dict as file
        $arguments[] = '--load-info-json';
        $tmp = tempnam(sys_get_temp_dir(), 'ytdl');
        if ($tmp === false) {
            $this->outError(__FUNCTION__, ' No tmp file');
            return $info_dict_single;
        }
        if (false === file_put_contents($tmp, json_encode($info_dict_single))){
            $this->outError(__FUNCTION__, 'No write of info_dict in tmp file');
            return $info_dict_single;
        }
        $arguments[] = $tmp;

        // si on voulait récupérer la sortie :
        // - pas de --print-json
        // - récupérer le $process->getOutput

        // we will have a nice fresh info_dict
        $arguments[] = '--print-json'; 

        // output template: '-o' (or '--output') user has priority over $data_folder
        // if no '-o' the filename is slugified
        if (empty(array_intersect(['-o', '--output'], $arguments))){
            $arguments[] = '-o';
            $arguments[] = $data_folder . Utils::slugify($info_dict_single['title']) . '.%(ext)s';
        }
        $arguments[] = $info_dict_single['webpage_url'];

        $process = $this->createProcess($arguments);
        $this->logger->debug(__FUNCTION__ . ' cmdline: ' . $process->getCommandLine());
        $process->run();
        unlink($tmp);
        $info_dict_return = json_decode($process->getOutput(), true);
        if (!$process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput());
            // $exitCode = $process->getExitCode();
            if (!empty($errorOutput)) {
                $this->outError(__FUNCTION__, $errorOutput);
            }
            if (isset($info_dict_return['_filename'])){
                unset($info_dict_return['_filename']); 
            }
        }
        return $info_dict_return;
    }


    /**
     * download
     * 
     * From an $info_dict download the video, else run youtube-dl with extractInfos.
     * 
     * '-o' or '--output' has priority over $data_folder
     * 
     * If some media are downloaded return a new info_dict (with _filename.ext for each entrie),
     * else return [] and it's a good idea to read errors (getErrors()).
     *
     * @param  string $link (same as 'webpage_url')
     * @param  mixed[]|null $info_dict
     * @param  string|'' $data_folder directory path to download (final '/' or not). Not use if '-o' option.
     * @return mixed[] $info_dict (empty or not)
     */
    public function download(string $link, array $info_dict = null, string $data_folder = ''){
        $arguments = $this->options->getOptions();
        // if not clear : $data_folder = ./
        $data_folder = ($data_folder != '')? (($data_folder == '/') ? './' : rtrim($data_folder, '\/') . '/') : $data_folder;

        // priority : 1- $info_dict, 2- $this->info_dict, 3- $this->extractInfos();
        if (!empty($info_dict)){
            $this->info_dict = $info_dict;
        } elseif (empty($this->info_dict)){
            $this->extractInfos($link);
        } // now we have : $this->info_dict;
        if (empty($this->info_dict)){
            return [];
        }
        
        // Playlist or not ?
        if ($this->isPlaylist($this->info_dict)){
            $this->logger->debug('download playlist: ' . $this->info_dict['title']);
            
            // TODO et si la playlist-item contient une list pas possible ?
            $all_indexes = $this->playlistIndexes();
            $new_info_dict = [];
            // dl each entry (follow playlist options) for a playlist
            foreach($all_indexes as $index){
                $new_info_dict[] = $this->realDownload($arguments, $this->info_dict['entries'][$index], $data_folder);
            }
            $this->info_dict['entries'] = $new_info_dict;

        // dl just for one
        } else {
            $this->info_dict = $this->realDownload($arguments, $this->info_dict, $data_folder);
        }

        return $this->info_dict;
    }

    // FIXME int vs string !!
    /**
     * playlistIndexes
     * 
     * Only for playlist
     * 
     * From 'options' return an array with all indexes. Like in ytdl.
     * Use '--playlist-*' see: ytdl command line.
     *
     * @return int[] array with indexes from options
     */
    public function playlistIndexes(){
        $playlist_items = [];
        $num_entries = count($this->info_dict['entries']);
        $playlist_start = (int)$this->options->getOption('--playlist-start', '1');
        $playlist_end = (int)$this->options->getOption('--playlist-end', (string)$num_entries);
        $playlist_items_str = $this->options->getOption('--playlist-items');
        
        if ($playlist_items_str !== ''){
            $indexes = explode(',', $playlist_items_str);
            foreach($indexes as $v){
                if (strpos($v, '-') !== false){
                    $range = explode('-', $v);
                    $playlist_items = array_merge($playlist_items, range((int)$range[0], (int)$range[1]));
                } else {
                    $playlist_items[] = (int)$v;
                }
            }
            // max value $num_entries
            foreach($playlist_items as $k=>$num_v){
                if ($num_v > $num_entries){
                    unset($playlist_items[$k]);
                }
            }
            $playlist_items = array_unique($playlist_items);
            sort($playlist_items);
        } else {
            $playlist_items = range($playlist_start, $playlist_end);
        }

        if ($this->options->isOption('--playlist-reverse')){
            rsort($playlist_items);
        }
        if ($this->options->isOption('--playlist-random')){
            shuffle($playlist_items);
        }
        // item number begin with 1, index with 0 !
        return array_map(function ($x) { return $x-1; }, $playlist_items);
    }


    /**
     * getInfoDict
     *
     * @return mixed[]
     */
    public function getInfoDict(){
        return $this->info_dict;
    }


    /**
     * getErrors
     *
     * @return string[]
     */
    public function getErrors(){
        return $this->errors;
    }

}

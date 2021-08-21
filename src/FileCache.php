<?php

namespace Flatgreen\Ytdl;

/**
 * FileCache
 * 
 * filesystem cache
 * 
 */
class FileCache
{
        
    /**
     * directory
     *
     * @var string|false $directory directory cache or 'false' to disable the cache system
     */
    private $directory;    
    /**
     * duration
     *
     * @var int $duration duration cache in seconde
     */
    private $duration;    
    /**
     * cache_enable
     *
     * @var bool
     */
    private $cache_enable;
    
    /**
     * name
     * 
     * cache filename with folder
     *
     * @var string 
     */
    public $name;
        
    /**
     * __construct
     *
     * @param  string $entry_name_to_cache a name used to create a FileCache
     * @param  string|false $directory directory cache or 'false' to disable the cache system
     * @param  int $duration duration cache in seconde
     * @return void
     */
    public function __construct(string $entry_name_to_cache, $directory, int $duration){
        $this->cache_enable = true;
        if (is_string($directory)) {
            $this->directory = $data_folder = ($directory == '') ? './' : rtrim($directory, '\/') . '/';
        } elseif (false === $directory) {
            $this->cache_enable = false;
        }
        $this->name = $this->directory . hash('sha256', $entry_name_to_cache) . '.json';
        $this->duration = $duration;
    }

    /**
     * isFresh
     * 
     * comparaison with $duration
     *
     * @return true|false
     */
    public function isFresh(){
        if (!@file_exists($this->name)){
            return false;
        } elseif ((time() - filemtime($this->name)) >= $this->duration){
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * write $content to $directory
     * 
     * @param  string $content
     * @throws \RuntimeException
     * @return bool
     */
    public function write(string $content){
        if ($this->cache_enable and !$this->isFresh()) {
            if (!is_dir($this->directory)) {
                if (false === @mkdir($this->directory)) {
                    if (!is_dir($this->directory)) {
                        throw new \RuntimeException(sprintf('Unable to create the cache directory (%s).', $this->directory));
                    }
                } elseif (!is_writable($this->directory)) {
                    throw new \RuntimeException(sprintf('Unable to write in the cache directory (%s).', $this->directory));
                }
            }


            if (@file_put_contents($this->name, $content) === false) {
                throw new \RuntimeException(sprintf('Failed to write cache file "%s".', $this->name));
            }
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * load
     * 
     * @throws \RuntimeException
     * @return string|null $content from a FileCache
     */
    public function load(){
        if ($this->cache_enable and $this->isFresh()) {
            if (false === $content = @file_get_contents($this->name)) {
                throw new \RuntimeException(sprintf('Failed to load cache file "%s".', $this->name));
            } else {
                return $content;
            }
        } else {
            return null;
        }
    }
    
}
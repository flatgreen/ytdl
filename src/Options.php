<?php

namespace Flatgreen\Ytdl;

use LogicException;

/**
 * Options
 * 
 * This is a class to help write options for youtube-dl command line.
 * 
 * All options are the same as youtube-dl and are passed with an array.
 * @link https://github.com/ytdl-org/youtube-dl#options
 * 
 * The class is not for 'URL' argument.
 * 
 * See the 'private $options for default options.
 * 
 * 
 * Basic usage:
 *      $opt = new Options;
 *      $opt->getOptions(); // default options
 * 
 * Usage with more options:
 *      $opt = new Options;
 *      $opt->addOptions(['-f' => '18']);
 *      $opt->getOptions();
 * 
 *      $opt = new Options;
 *      $opt->addOptions(['-o' => '%(uploader)s/%(title)s.%(ext)s']);
 *      $opt->getOptions();
 * 
 *      $opt = new Options;
 *      $opt->setOptions(['--version'])->getOptions(); // just one argument
 * 
 */
class Options{
    
    /**
     * hold options
     *
     * @var mixed[]
     */
    private $options;
    
    /**
     * The default options for this class.
     *
     * @var mixed[]
     */
    private $default_options =
        ['--ignore-errors', '--no-progress',
        '--no-warnings', '--ignore-config', '--force-ipv4',
        ];
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->options = $this->default_options;
    }
    
    /**
     * getDefaultOptions
     *
     * @return mixed[]
     */
    public function getDefaultOptions(){
        return $this->default_options;
    }

 
    /**
     * verifOptions
     * 
     * ['-a', '--b'] OK
     * ['-f' => '18'] OK
     * ['-f', '18'] Not OK
     *
     * @param  mixed[] $options
     * @throws LogicException
     * @return void
     */
    private function verifOptions(array $options){
        foreach($options as $k => $v){
            if (is_int($k) && (substr($v, 0, 1) != '-')){
                throw new \LogicException("Options must begin with '-' or '--'. 'key' => 'value' for option with value. URL isn't an ytdl option.");
            }
        }
    }


    /**
     * addOptions
     * 
     * Add or owerwrite some options to ytdl commandline
     *
     * @param mixed[] $options
     * @return $this
     */
    public function addOptions(array $options){
        $this->verifOptions($options);
        $this->options = array_merge($this->options, $options);
        return $this;
    }
    
    /**
     * setOptions
     * 
     * Reset all default options and set ytdl options
     *
     * @param  mixed[] $options
     * @return $this
     */
    public function setOptions(array $options = []){ 
        $this->verifOptions($options);
        $this->options = [];
        if (!empty($options)){
            $this->addOptions($options);
        }
        return $this;
    }

    
    /**
     * isOption
     * 
     * True if begin with '-' or '--' else false
     *
     * TODO delete ?
     * 
     * @param  string $needle
     * @return bool
     */
    public function isOption(string $needle){
        // an option must begin with '-' or '--'
        if (substr($needle, 0, 1) != '-'){
            return false;
        }
        $arr = $this->linearOptions($this->options);
        return in_array($needle, $arr);
    }

        
    /**
     * linearOptions
     * 
     * a key must be an int, each argument (simple or with a value) is an array value.
     * 
     * From ['-a', '-b' => 'bb'] to ['-a', '-b', 'bb']
     *
     * @param  mixed[] $arr
     * @return mixed[]
     */
    private function linearOptions(array $arr){
        $final_options = [];
        foreach($arr as $k => $v){
            if (is_int($k)){
                $final_options[] = $v;
            } else {
                $final_options[] = $k;
                $final_options[] = $v;
            }
        }
        return $final_options;
    }

    /**
     * getOption
     * 
     * return value from options[$key] or just one option (if alone),
     * else return default.
     * 
     * @param string $key
     * @param string $default
     * @return mixed
     */
    public function getOption(string $key, string $default = ''){
        if ($this->isOption($key)){
            return (key_exists($key, $this->options)) ? $this->options[$key] : $key;
        } else {
            return $default;
        }
    }

    /**
     * getOptions
     * 
     * return all options ready for ytdl process
     * 
     * @return mixed[]
     */
    public function getOptions(): array
    {
        return $this->linearOptions($this->options);
    }
    
    /**
     * removeOption
     *
     * @param  string $opt
     * @return void
     */
    public function removeOption(string $opt){
        // $k=>$v
        if (key_exists($opt, $this->options)){
            unset($this->options[$opt]);
        } else {
            // alone    
            if (($key = array_search($opt, $this->options)) !== false) {
                unset($this->options[$key]);
            }
        }
    }

}
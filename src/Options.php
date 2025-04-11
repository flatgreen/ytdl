<?php

namespace Flatgreen\Ytdl;

use LogicException;

/**
 * Options
 *
 * This is a class to help write options for the command line.
 *
 * All options are the same as youtube-dl and are passed with an array.
 * @link https://github.com/ytdl-org/youtube-dl#options
 * @link https://github.com/yt-dlp/yt-dlp#usage-and-options
 *
 * The class is not for 'URL' argument.
 *
 * Basic usage:
 *      $opt = new Options();
 *      $opt->getDefaultOptions(); // see default options
 *
 * Usage with more options:
 *      $opt = new Options();
 *      $opt->addOptions(['-f' => '18']);
 *
 *      $opt = new Options();
 *      $opt->addOptions(['-o' => '%(uploader)s/%(title)s.%(ext)s']);
 *
 */
class Options
{
    /**
     * hold options
     *
     * @var string[]
     */
    private $options;

    /**
     * The default options for this class.
     *
     * @var string[]
     */
    private $default_options =
        ['--ignore-errors', '--no-progress',
        '--no-warnings', '--ignore-config'
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
    public function getDefaultOptions(): array
    {
        return $this->default_options;
    }

    /**
     * verifOptions
     *
     * ['-a', '--b'] OK
     * ['-f' => '18'] OK
     * ['-f', '18'] Not OK
     *
     * @param  string[] $options
     * @throws LogicException
     * @return void
     */
    private function verifOptions(array $options): void
    {
        foreach ($options as $k => $v) {
            if (is_int($k) && (substr($v, 0, 1) != '-')) {
                throw new \LogicException("Options must begin with '-' or '--'. 'key' => 'value' for option with value. URL isn't an ytdl option.");
            }
        }
    }


    /**
     * addOptions
     *
     * Add or owerwrite some options to ytdl commandline
     *
     * @param string[] $options
     * @return $this
     */
    public function addOptions(array $options)
    {
        $this->verifOptions($options);
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * addRawOptions
     *
     * Add some options in a command line manner
     *
     * @param string $options
     * @phpstan-impure
     * @return $this
     */
    public function addRawOptions(string $options)
    {
        $arr_opt = explode(' ', trim($options));
        while (null != ($val = array_shift($arr_opt))) {
            // only '-' or '--' without value
            if (substr($val, 0, 1) == '-' && isset($arr_opt[0]) && (substr($arr_opt[0], 0, 1) != '-')) {
                $this->addOptions([$val => $arr_opt[0]]);
                array_shift($arr_opt);
            } else {
                $this->addOptions([$val]);
            }
        }
        return $this;
    }

    /**
     * isOption
     *
     * True if begin with '-' or '--' else false
     *
     * @param  string $needle
     * @return bool
     */
    public function isOption(string $needle): bool
    {
        if (substr($needle, 0, 1) != '-') {
            return false;
        }
        $arr = $this->linearOptions($this->options);
        return in_array($needle, $arr);
    }

    /**
     * linearOptions
     *
     * Transform an Option array in simple array,
     * finally each argument (simple or with a value) is an array value.
     *
     * From ['-a', '-b' => 'bb'] to ['-a', '-b', 'bb']
     *
     * @param  string[] $arr
     * @return string[]
     */
    protected function linearOptions(array $arr): array
    {
        $final_options = [];
        foreach ($arr as $k => $v) {
            if (is_int($k)) {
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
     * else return $default.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getOption(string $key, string $default = ''): string
    {
        if ($this->isOption($key)) {
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
     * @return string[]
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
    public function removeOption(string $opt): void
    {
        // $k=>$v
        if (key_exists($opt, $this->options)) {
            unset($this->options[$opt]);
        } else {
            // alone
            if (($key = array_search($opt, $this->options)) !== false) {
                unset($this->options[$key]);
            }
        }
    }
}

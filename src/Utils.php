<?php

namespace Flatgreen\Ytdl;

/**
 * Utils
 * 
 * Some clever functions for Ytdl class
 */
class Utils
{    
    /**
     * changeArrayWithUniqueValueFor
     *
     * $array_to_transform is something like :
     * 
     * [0 => ['title' => 'dopo', 'autre' => 'cc'],
     * 1 => ['title' => 'dopi'],
     * 2 => ['autre' => 'dd','title' => 'dopo'],
     * 3 => ['title' => 'Dopo', 'dopa' => 'tt']];
     * 
     * $key_needle = 'title'
     * 
     * Return :
     * 
     * [0 => ['title' => 'dopo', 'autre' => 'cc'],
     * 1 => ['title' => 'dopi'],
     * 2 => ['autre' => 'dd','title' => 'dopo-1'],
     * 3 => ['title' => 'Dopo-2', 'dopa' => 'tt']];
     * 
     * the new values : dopo, dopo-1, Dopo-2
     * 
     * @param  mixed[] $array_to_transform ($info_dict)
     * @param  string $key_needle key for unique value
     * @param  int $deb_prefix default: 1
     * @return mixed[]
     */
    static public function changeArrayWithUniqueValueFor(array $array_to_transform, string $key_needle, int $deb_prefix = 1){
        $new_array = [];
        // double array
        foreach($array_to_transform as $k_first_array => $value_first_array){
            // $value_first_array = ['title' => 'dopo', 'autre' => 'cc'];
            $flag_duplicate = false;
            foreach($new_array as $new_k => $new_value){
                if (strtolower($value_first_array[$key_needle]) == strtolower($new_value[$key_needle])){
                    $flag_duplicate = true;
                    break;
                }
            }
            if ($flag_duplicate){
                $new_array[$k_first_array] = $value_first_array;
                    $new_array[$k_first_array][$key_needle] = $new_array[$k_first_array][$key_needle] . "-$deb_prefix";
                    $deb_prefix++;
            } else {
            $new_array[$k_first_array] = $value_first_array;
            }
        }
        return $new_array;
    }

    /**
     * slugify.
     * inspiration: 
     * https://www.php.net/manual/en/transliterator.transliterate.php#115162
     * https://gist.github.com/james2doyle/9158349#file-slugify-php
     *
     * @param  string $string the string to slugify
     * @param  string $delimiter '-' by default
     *
     * @return string the slugify string !
     */
    public static function slugify(string $string, string $delimiter = '-'){
        // $slug = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower();', $string);
        $slug = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $string);
        // $slug = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $slug);
        // remove non ascii
        $slug = preg_replace("/[^a-zA-Z0-9\/_|+ -\.]/", ' ', $slug);
        // symbols in -
        $slug = preg_replace("/[\/_|+ -\.]+/", $delimiter, $slug);
        return trim($slug, $delimiter);
    }

}
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
     * @param  int $deb_suffix default: 1
     * @return mixed[]
     */
    public static function changeArrayWithUniqueValueFor(array $array_to_transform, string $key_needle, int $deb_suffix = 1)
    {
        $new_array = [];
        // double array
        foreach ($array_to_transform as $k_first_array => $value_first_array) {
            // $value_first_array = ['title' => 'dopo', 'autre' => 'cc'];
            $flag_duplicate = false;
            foreach ($new_array as $new_k => $new_value) {
                if (strtolower($value_first_array[$key_needle]) == strtolower($new_value[$key_needle])) {
                    $flag_duplicate = true;
                    break;
                }
            }
            if ($flag_duplicate) {
                $new_array[$k_first_array] = $value_first_array;
                $new_array[$k_first_array][$key_needle] = $new_array[$k_first_array][$key_needle] . "-$deb_suffix";
                $deb_suffix++;
            } else {
                $new_array[$k_first_array] = $value_first_array;
            }
        }
        return $new_array;
    }
}

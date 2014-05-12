<?php

/* 
 * User format file size
 */

class Utils extends \Phalcon\Mvc\User\Component {
    public static function formatFileSize($bytes)
    {
        $kilobytes = round($bytes / 1024, 1);
        $megabytes = round($kilobytes / 1024, 1);
        $gigabytes = round($megabytes / 1024, 1);

        if ($gigabytes >= 1) {
        	return $gigabytes . " GB";
        } else if ($megabytes >= 1) {
            return $megabytes . " MB";
        } else {
            return $kilobytes . " KB";
        }
    }
}


<?php
/*
 * User send mail helper
 */

class Utils extends \Phalcon\Mvc\User\Component {

    public static function formatFileSize($bytes) {
        $kilobytes = round($bytes / 1024, 2);
        $megabytes = round($kilobytes / 1024, 2);
        $gigabytes = round($megabytes / 1024, 2);

        if ($gigabytes >= 1) {
            return $gigabytes . " GB";
        } elseif ($megabytes >= 1) {
            return $megabytes . " MB";
        } else {
            return $kilobytes . " KB";
        }
    }

}

<?php

if(!class_exists('ST_CriticalCss_Logger')) {
    class ST_CriticalCss_Logger extends ST_CriticalCss_Base {
        public function init() {

        }

        function log($log) {
            $date = new \DateTime();
            $date_fmt= $date->format('Y-m-d H:i:s');

            $log = "[$date_fmt] " . $log ."\n";
            file_put_contents(plugin_dir_path(__FILE__).'debug.log', $log, FILE_APPEND);
        }
    }
}
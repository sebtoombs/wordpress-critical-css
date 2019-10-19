<?php

if(!class_exists('ST_CriticalCss_Cache')) {
    class ST_CriticalCss_Cache extends ST_CriticalCss_Base {

        public function init() {

        }


        public function check_cache_status() {
            $cache_time = $this->get_opt('cache_time');

            $cache_file = $this->get_cache_file_full();
            $exists = file_exists($cache_file);

            if(!$exists) return 'empty';

            $fresh = ($exists && (filemtime($cache_file) > (time() - $cache_time )));

            return $fresh ? 'fresh' : 'stale';
        }

        public function get_cache_file_name($uncritical=false) {
            $url = $this->get_url();
            $file_name = $this->sanitise_file_name($url);
            if($uncritical) $file_name .= '.uncritical';
            $file_name .= '.css';
            return $file_name;
        }

        public function get_cache_file_path() {
            return WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'css_cache';
        }

        public function get_cache_file_full($filename=null) {
            if($filename === null) $filename = $this->get_cache_file_name();
            return $this->get_cache_file_path().DIRECTORY_SEPARATOR.$filename;
        }

        public function sanitise_file_name($file_name) {
            $filename_raw = $file_name;
            $special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
            $special_chars = apply_filters('sanitize_file_name_chars', $special_chars, $filename_raw);
            $filename = str_replace($special_chars, '', $file_name);
            $filename = preg_replace('/[\s-]+/', '-', $filename);
            $filename = trim($filename, '.-_');
            return apply_filters('sanitize_file_name', $filename, $filename_raw);
        }

        public function save_to_cache($css, $args=null) {
            $args = is_null($args) ? [] : $args;

            $file_name = null;
            if(!empty($args['url'])) {
                $file_name = $this->sanitise_file_name($args['url']);
            }
            if(!empty($args['uncritical']) && $file_name) {
                $file_name .= '.uncritical';
            }
            if($file_name) {
                $file_name .='.css';
            }
            //Save to cache
            file_put_contents($this->get_cache_file_full($file_name), $css, LOCK_EX);
        }


        public function get_from_cache() {
            $cache_file = $this->get_cache_file_full();
            $exists = file_exists($cache_file);
            if(!$exists) return; //TODO maybe log?

            $critical_css = file_get_contents($cache_file); //TODO check this
        }
    }
}
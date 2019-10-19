<?php

if(!class_exists('ST_CriticalCss_Options')) {

    class ST_CriticalCss_Options extends ST_CriticalCss_Base {

        const OPTION_KEY = 'critical_css_options';

        private $_defaults = [
            'cache_time'=>0, //time in seconds eg 5 minutes: 300 or 0 for infinite
            'use_stale'=>true,
            //'api_key'=>false,
            'ignore_styles'=>[],
            'use_uncritical'=>true
        ];
        private $opts;

        public function init() {
            $opts = get_option(self::OPTION_KEY, []);
            $this->opts = array_merge($this->_defaults, $opts);
        }

        public function get_all() {
            return $this->opts;
        }

        public function get_opt($key, $default=null) {
            if(isset($this->opts[$key])) {
                return $this->opts[$key];
            }
            if(!is_null($default) || !isset($this->_defaults[$key])) return $default;
            return $this->_defaults[$key];
        }

        public function set_opt($key, $value, $save=true) {
            $this->opts[$key] = $value;
            if($save !== false) {
                update_option(self::OPTION_KEY, $this->opts);
            }
        }

        public function update_options($options, $save=true) {
            foreach($options as $key=>$value) {
                //$this->opts[$key] = $value;
                $this->set_opt($key, $value, false);
            }
            if($save !==false) {
                update_option(self::OPTION_KEY, $this->opts);
            }
        }
    }
}
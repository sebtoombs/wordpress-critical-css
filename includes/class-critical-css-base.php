<?php

if(!class_exists('ST_CriticalCss_Base')) {
    abstract class ST_CriticalCss_Base {
        private $_get_dep;

        public function __construct($get_dep) {
            $this->_get_dep = $get_dep;

            $this->init();
        }

        public function get_dep($dep) {
            return call_user_func($this->_get_dep, $dep);
        }

        public abstract function init();



        //Utils
        public function get_url() {
            global $wp;
            $url = home_url( $wp->request );
            return $url;
        }
    }
}
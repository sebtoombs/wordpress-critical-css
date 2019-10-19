<?php

if(!class_exists('ST_CriticalCss_Api')) {
    class ST_CriticalCss_Api extends ST_CriticalCss_Base {

        //const api_url = 'https://critical-css-gen.herokuapp.com/';
        const api_url = 'http://localhost:8000/';
        const api_option_key = 'critical_css_api_key';

        private $_api_key = false;

        public function init() {
            $this->_api_key = get_option(self::api_option_key, false);
        }


        public function get_key() {
            return $this->_api_key;
        }

        public function request_css($url) {

            if(!$this->_api_key) return false;

            $response = wp_remote_post(self::api_url.'critical',[
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'blocking' => false,//true,
                'body' => array(
                    'key' => $this->_api_key,
                    'url' => $url,
                    'webhook' => home_url().'/?critical_css'
                )
            ]);

            if ( is_wp_error( $response ) ) {
                //TODO log?
                return false;
            }
            return true;
        }
    }
}
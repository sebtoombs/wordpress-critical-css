<?php

if(!class_exists('ST_CriticalCss_Api')) {
    class ST_CriticalCss_Api extends ST_CriticalCss_Base {

        //const api_url = 'https://critical-css-gen.herokuapp.com/';
        const api_url = 'http://localhost:8000/';
        const api_option_key = 'critical_css_api_key';

        private $_api_key = false;

        public function init() {
            $this->_api_key = get_option(self::api_option_key, false);
            add_action('template_redirect', array($this, 'webhook_listener'));
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



        public function ping() {

            if(!$this->_api_key) return [false, 'api_key'];

            $response = wp_remote_post(self::api_url.'ping', [
                'method'=>'POST',
                'timeout'=>60,
                'blocking'=>true,
                'body'=> [
                    'key'=>$this->_api_key
                ]
            ]);

            if(is_wp_error($response)) {
                return [false, 'api_error'];
            }

            return [true];

        }

        public function revoke_key() {

            if(!$this->_api_key) return false;

            //TODO maybe delete api key regardless of api response?

            $response = wp_remote_post(self::api_url.'revoke',[
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'blocking' => true,
                'body' => array(
                    'key'=>$this->_api_key
                )
            ]);

            if ( is_wp_error( $response ) ) {
                $Logger = $this->get_dep('logger');
                $Logger->log(print_r($response,true));
                return [false, 'api_error'];
            }

            $data = false;
            try {
                $data = json_decode($response['body'], true);
            } catch(Exception $error) {

            }
            if(!$data) {
                return [false, 'api_error'];
            }

            if($response['response']['code'] !== 200) {
                return [false, 'api_error'];
            }

            delete_option(self::api_option_key);

            return [true];

        }


        public function validate_key($key) {

            if(!$key) return [false, 'missing key'];

            $response = wp_remote_post(self::api_url.'validate',[
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'blocking' => true,
                'body' => array(
                    'key' => $key
                )
            ]);

            if ( is_wp_error( $response ) ) {
                $Logger = $this->get_dep('logger');
                $Logger->log(print_r($response,true));
                return [false, 'api_error'];
            }

            $data = false;
            try {
                $data = json_decode($response['body'], true);
            } catch(Exception $error) {

            }
            if(!$data) {
                return [false, 'api_error'];
            }

            if($response['response']['code'] !== 200) {
                return [false, 'api_error'];
            }

            update_option('critical_css_api_key', $key);

            return [true];
        }



        public function request_critical() {

            //$Options = $this->get_dep('options');
            $Logger = $this->get_dep('logger');

            if(!$this->_api_key) {
                //TODO log?
                $Logger->log('Missing API Key');
                return;
            }


            $Logger->log('Requesting Critical CSS');

            $url = $this->get_url();
            //$response = wp_remote_post('https://critical-css-gen.herokuapp.com/critical', [//'http://localhost:8000/critical',[
            $response = wp_remote_post(self::api_url.'critical',[
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'blocking' => false,//true,
                'body' => array(
                    'key' => $this->_api_key,
                    'url' => $url,
                    'webhook' => home_url().'/?critical_css'
                ),
            ]);

            if ( is_wp_error( $response ) ) {
                //TODO log?
                $Logger->log('Error requesting critical CSS');
                return;
            }
        }




        public function webhook_listener() {

            $Options = $this->get_dep('options');
            $Logger = $this->get_dep('logger');
            $Cache = $this->get_dep('cache');

            if($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_REQUEST['critical_css'])) return;
            if(!$this->_api_key) {
                $Logger->log('Webhook listener failed - no api key - local');
                return;
            }
            ob_start();
            $Logger->log('Webhook listener started');
            //if(!isset($_REQUEST['critical_css'])) return;

            // Takes raw data from the request
            $json = file_get_contents('php://input');

            // Converts it into a PHP object

            $data = json_decode($json, true);

            //$this->log('Raw JSON: '.$json);

            if(!isset($data['css']) || !isset($data['key']) || !isset($data['url'])) {
                $Logger->log('Webhook listener failed - missing data');
                return;
            }
            if($data['key'] !== $this->_api_key) {
                $Logger->log('Webhook listener failed - invalid key');
                return;
            }

            if(isset($data['css']['critical'])) {
                $Cache->save_to_cache($data['css']['critical'], ['url'=>$data['url']]);
                $Logger->log('Critical CSS Saved');
            }
            if(isset($data['css']['uncritical'])) {
                $Cache->save_to_cache($data['css']['uncritical'], ['url'=>$data['url'], 'uncritical'=>true]);
                $Logger->log('Uncritical CSS Saved');
            }
            $Logger->log('Webhook listener finished');
            ob_get_clean();
            header("HTTP/1.0 200 OK");
            echo 'OK';
            exit;
        }


    }
}
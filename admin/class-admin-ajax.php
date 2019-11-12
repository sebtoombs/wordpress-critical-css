<?php

if(!class_exists('ST_CriticalCSS_AdminAjax')) {
    class ST_CriticalCss_AdminAjax extends ST_CriticalCss_Base {

        public $_actions = [
            'get_options',
            'update_option',
            'delete_options',
            'get_status',
            'validate_key',
            'revoke_key'
        ];

        public function init() {
            $this->add_hooks();
        }

        public function add_hooks() {
            foreach($this->_actions as $action) {
                add_action('wp_ajax_critical_css_'.$action, array($this, 'ajax_'.$action));
            }
        }

        public function verify_ajax() {
            if(!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'critical-css')) {
                header("HTTP/1.0 400 Bad Request");
                wp_send_json_error(['message'=>'nonce failed']);
                exit;
            }
        }



        public function ajax_success($data=null, $status = 200) {
            $response = [];
            http_response_code($status);
            $response['nonce'] = wp_create_nonce('critical-css');
            if(isset($data)) {
                $response['data'] = $data;
            }
            wp_send_json($response);
            exit;
        }
        public function ajax_error($error=null, $status=500){
            http_response_code($status);
            wp_send_json_error($error);
            exit;
        }


        public function ajax_get_options() {

            $this->verify_ajax();

            $Options = $this->get_dep('options');

            $options = $Options->get_all();

            $this->ajax_success($options);

        }

        public function ajax_update_option() {
            $this->verify_ajax();

            if(!isset($_POST['options'])) {
                $this->ajax_error(['message'=>'options missing']);
            }

            $options = @json_decode( html_entity_decode( stripslashes ($_POST['options'] ) ) );

            if(empty($options)) {
                $this->ajax_error(['message'=>'invalid options object']);
            }


            $Options = $this->get_dep('options');
            $Options->update_options($options);

            $this->ajax_success();
        }

        public function ajax_delete_options() {
            $this->verify_ajax();

            $Options = $this->get_dep('options');

            $Options->reset_options();

            $this->ajax_success();
        }

        public function ajax_get_status() {
            $this->verify_ajax();

            $Status = $this->get_dep('status');
            $status= $Status->get_status();

            $this->ajax_success($status);
        }

        public function ajax_validate_key() {

            $this->verify_ajax();

            $key = isset($_POST['key']) ? $_POST['key'] : false;

            if(!$key) {
                $this->ajax_error(['message'=>'key not set']);
            }

            $Api = $this->get_dep('api');

            $api_response = $Api->validate_key($key);

            if(!$api_response[0]) $this->ajax_error($api_response);

            $this->ajax_success();

        }


        public function ajax_revoke_key() {

            $this->verify_ajax();

            $Api = $this->get_dep('api');

            $api_response = $Api->revoke_key();

            if(!$api_response[0]) $this->ajax_error($api_response);

            $this->ajax_success();


        }
    }
}
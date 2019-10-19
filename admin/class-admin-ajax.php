<?php

if(!class_exists('ST_CriticalCSS_AdminAjax')) {
    class ST_CriticalCss_AdminAjax extends ST_CriticalCss_Base {

        public $_actions = [
            'get_options',
            'update_option'
        ];

        public function init() {
            $this->add_hooks();
        }

        public function add_hooks() {
            /*foreach($this->_actions as $action) {
                add_action('wp_ajax_critical_css_'.$action, array($this, 'ajax_'.$action));
            }*/
            add_action('wp_ajax_critical_css_get_options', array($this, 'ajax_get_options'));
        }

        public function verify_ajax() {
            if(!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'critical-css')) {
                header("HTTP/1.0 400 Bad Request");
                wp_send_json_error(['message'=>'nonce failed']);
                exit;
            }
        }





        public function ajax_get_options() {
            wp_send_json_success(['message'=>'test']);
            exit;

            $this->verify_ajax();

            $options = $this->get_dep('options');

            wp_send_json_success($options);
            exit;
        }

        public function ajax_update_option() {
            $this->verify_ajax();

            if(!isset($_POST['options'])) {
                wp_send_json_error(['message'=>'options missing']);
                exit;
            }

            //TODO maybe whitelist options?

            $options = $this->get_dep('options');

            //Process options

            $debug = '';
            $options_to_update = [];
            foreach($_POST['options'] as $key=>$value) {
                $sanitised_value = sanitize_option('critical_css_'.$key, $value);

                if($key === 'ignore_styles') {
                    $sanitised_value = explode(",", $sanitised_value);
                }

                $options_to_update[$key] = $sanitised_value;
            }
            //update_option('critical_css_options', $options_to_update);
            $options->update_options($options_to_update);


            wp_send_json_success(['message'=>'success', 'debug'=>$debug]);
            exit;
        }
    }
}
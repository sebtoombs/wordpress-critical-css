<?php

if(!class_exists('ST_CriticalCss_Status')) {
    class ST_CriticalCss_Status extends ST_CriticalCss_Base {
        public function init() {
            // TODO: Implement init() method.
        }

        public function get_status() {
            $Options = $this->get_dep('options');

            $status = [
                'cache_dir'=>null
            ];

            //Check if cache directory exists & is writable
            $Cache = $this->get_dep('cache');

            $status['cache_dir'] = $Cache->cache_writable();


            //Check if api accessible & remote_post available
            $api_status = $Options->get_opt('api_status', false);
            if($api_status) {
                $status['api_status'] = [true];
            } else {
                $Api = $this->get_dep('api');
                $api_ping = $Api->ping();
                if($api_ping[0]) {
                    $Options->set_opt('api_status', time());
                }

                $status['api_status'] = $api_ping;
            }




            return $status;
        }
    }
}
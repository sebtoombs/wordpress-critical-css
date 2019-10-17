<?php

/*
Plugin Name: Critical CSS
Plugin URI: https://sebtoombs.com.au
Description: Generate & inline critical css, and defer other styles for faster page loads.
Version: 1.0.0
Author: Seb Toombs <seb@sebtoombs.com>
Author URI: https://sebtoombs.com
Text Domain: critical-css
*/

class ST_CriticalCss {
    public function __construct() {
        $this->add_hooks();
    }

    function log($log) {
        $date = new DateTime();
        $date_fmt= $date->format('Y-m-d H:i:s');

        $log = "[$date_fmt] " . $log ."\n";
        file_put_contents(plugin_dir_path(__FILE__).'debug.log', $log, FILE_APPEND);
    }

    public function add_hooks() {
        add_action('template_redirect', array($this, 'webhook_listener'));
        add_action('wp_head', array($this, 'do_critical'), 7);
    }

    public function webhook_listener() {

        if($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_REQUEST['critical_css'])) return;
        $this->log('Webhook listener started');
        //if(!isset($_REQUEST['critical_css'])) return;

        // Takes raw data from the request
        $json = file_get_contents('php://input');

        // Converts it into a PHP object

        $data = json_decode($json, true);

        //$this->log('Raw JSON: '.$json);

        if(!isset($data['css']) || !isset($data['key']) || !isset($data['url'])) {
            $this->log('Webhook listener failed - missing data');
            return;
        }
        if($data['key'] !== 'dbe30568-cae1-4169-a5d2-2a724a6725b1') {
            $this->log('Webhook listener failed - invalid key');
            return;
        }

        $this->save_to_cache($data['css'], $data['url']);
        header("HTTP/1.0 200 OK");
        echo 'OK';
        exit;
    }

    public function do_critical() {

        //Do nothing if we're building
        if(isset($_REQUEST['critical_css'])) return;

        // Check cache for critical css file for this page
        $cache_status = $this->check_cache_status();

        echo '<!-- CACHE STATUS: '.$cache_status.' -->';

        if($cache_status !== 'fresh') {//might be expired or stale

            // If not in cache fire off critical request
            $this->request_critical();

            //Todo maybe fallback to stale whilst being generated?
            //And do nothing else
            /*if($cache_status === 'stale') {
                $this->print_critical();
                $this->defer_stylesheets();
            }*/

        } else {//Fresh

            // If in cache, print critical
            $this->print_critical();
            // and defer other css
            $this->defer_stylesheets();

        }
    }

    public function check_cache_status() {
        $cache_time = 60*5; //5 minutes

        $cache_file = $this->get_cache_file_full();
        $exists = file_exists($cache_file);

        if(!$exists) return 'empty';

        $fresh = ($exists && (filemtime($cache_file) > (time() - $cache_time )));

        return $fresh ? 'fresh' : 'stale';
    }

    public function get_url() {
        global $wp;
        $url = home_url( $wp->request );
        return $url;
    }

    public function get_cache_file_name() {
        $url = $this->get_url();
        $file_name = $this->sanitise_file_name($url);
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


    public function request_critical() {
        $url = $this->get_url();
        $response = wp_remote_post('https://critical-css-gen.herokuapp.com/critical', [//'http://localhost:8000/critical',[
            'method' => 'POST',
            'timeout' => 60,
            'redirection' => 5,
            'blocking' => false,//true,
            'body' => array(
                'key' => 'dbe30568-cae1-4169-a5d2-2a724a6725b1',
                'url' => $url,
                'webhook' => home_url().'/?critical_css'
            ),
        ]);

        /*if ( is_wp_error( $response ) ) {
            //TODO log?
            return;
        }

        //Parse response
        $critical_css = null;
        try {
            $critical_css = json_decode($response['body'], true);
        } catch(Exception $error) {
            //TODO maybe log?
        }

        if(!$critical_css) {
            return;
        }

        $this->save_to_cache($critical_css['css']);*/
    }

    public function save_to_cache($css, $url=null) {
        $file_name = null;
        if($url !== null) {
            $file_name = $this->sanitise_file_name($url) . '.css';
        }
        //Save to cache
        file_put_contents($this->get_cache_file_full($file_name), $css, LOCK_EX);
    }

    public function print_critical() {
        $cache_file = $this->get_cache_file_full();
        $critical_css = file_get_contents($cache_file);
        ?>
        <!-- INLINE CRITICAL CSS -->
        <style type="text/css">
            <?php echo $critical_css;?>
        </style>
        <?php
    }


    public function defer_stylesheets() {
        global $wp_styles;

        //print_r($wp_styles);

        $styles_to_defer = array();
        //$styles_to_defer = array_merge($kingsdesign_preload_styles, $styles_to_defer);

        $inline_styles = "";

        //foreach($styles_to_defer as $handle) {
        foreach($wp_styles->registered as $handle=>$style) {
            if(wp_style_is($handle, 'enqueued') && wp_style_is($handle, 'registered')) {
                $style = $wp_styles->registered[$handle];
                $src = $style->src;
                $ver = $style->ver;
                //$deps = $style->deps; //is an array of dep handles

                wp_dequeue_style($handle);

                /*if(strcmp($handle, 'dashicons') ==0 || strcmp($handle, 'wp-block-library') == 0) {
                    echo 'CORE: ';
                    print_r($style);
                    $src = home_url($src);
                }*/
                if(strpos($src, '/') ===0) {
                    $src = home_url($src);
                }

                if(isset($style->extra['after'])) {
                    foreach($style->extra['after'] as $after) {
                        $inline_styles .= $after . "\n";
                    }
                }

                //Add modified link
                add_action('wp_footer', function() use ($handle, $src, $ver) { ?>
                    <link id="<?= $handle; ?>-css" rel="preload" href="<?= $src; ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
                    <noscript><link id="<?= $handle; ?>-css" rel="stylesheet" href="<?= $src; ?>"></noscript>
                    <?php
                },0);
            }
        }

        if(!empty($inline_styles)) {
            add_action('wp_footer', function() use ($inline_styles) {
                echo '<!-- EXTRA INLINE --><style type="text/css">'.$inline_styles.'</style>';
            },0);
        }

        //Add loadCss polyfill
        add_action('wp_head', function() {?>
            <script>
                (function(w){"use strict";if(!w.loadCSS){w.loadCSS=function(){}}
                    var rp=loadCSS.relpreload={};rp.support=(function(){var ret;try{ret=w.document.createElement("link").relList.supports("preload")}catch(e){ret=!1}
                        return function(){return ret}})();rp.bindMediaToggle=function(link){var finalMedia=link.media||"all";function enableStylesheet(){if(link.addEventListener){link.removeEventListener("load",enableStylesheet)}else if(link.attachEvent){link.detachEvent("onload",enableStylesheet)}
                        link.setAttribute("onload",null);link.media=finalMedia}
                        if(link.addEventListener){link.addEventListener("load",enableStylesheet)}else if(link.attachEvent){link.attachEvent("onload",enableStylesheet)}
                        setTimeout(function(){link.rel="stylesheet";link.media="only x"});setTimeout(enableStylesheet,3000)};rp.poly=function(){if(rp.support()){return}
                        var links=w.document.getElementsByTagName("link");for(var i=0;i<links.length;i++){var link=links[i];if(link.rel==="preload"&&link.getAttribute("as")==="style"&&!link.getAttribute("data-loadcss")){link.setAttribute("data-loadcss",!0);rp.bindMediaToggle(link)}}};if(!rp.support()){rp.poly();var run=w.setInterval(rp.poly,500);if(w.addEventListener){w.addEventListener("load",function(){rp.poly();w.clearInterval(run)})}else if(w.attachEvent){w.attachEvent("onload",function(){rp.poly();w.clearInterval(run)})}}
                    if(typeof exports!=="undefined"){exports.loadCSS=loadCSS}
                    else{w.loadCSS=loadCSS}}(typeof global!=="undefined"?global:this))
            </script>
        <?php },11);
    }

    public static function mkdir($path, $mask = 0775) {
        if ( !@is_dir( $path ) ) {
            if ( !@mkdir( $path, $mask ) ) {
                return false;
            }
        }
        return true;
    }


    public static function install() {
        self::mkdir(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'css_cache');
        touch(plugin_dir_path(__FILE__).'debug.log');
    }
}
new ST_CriticalCss();


register_activation_hook( __FILE__, array( 'ST_CriticalCss', 'install' ) );
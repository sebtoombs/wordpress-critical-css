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

/*require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/sebtoombs/wordpress-critical-css',
    __FILE__, //Full path to the main plugin file or functions.php.
    'critical-css'
);*/
//Optional: Set the branch that contains the stable release.
//$myUpdateChecker->setBranch('release');
//$myUpdateChecker->getVcsApi()->enableReleaseAssets();

class ST_CriticalCss {

    private $opts;
    private $_defaults;


    private $api_url = 'https://critical-css-gen.herokuapp.com/critical';

    public function __construct() {

        //TODO load opts from wp
        $this->_defaults = [
            'cache_time'=>0, //time in seconds eg 5 minutes: 300 or 0 for infinite
            'use_stale'=>true,
            'api_key'=>false,
            'ignore_styles'=>[],
            'use_uncritical'=>true
        ];
        $opts = [
            'api_key'=>'dbe30568-cae1-4169-a5d2-2a724a6725b1',
            'ignore_styles'=>[
                'wp-content/themes/storefront/style.css',
                'https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,300,300italic,400italic,600,700,900&subset=latin%2Clatin-ext',
                //'wp-content/themes/storefront/assets/css/base/icons.css'
            ]
        ];
        $this->opts = array_merge($this->_defaults, $opts); //TODO load in opts here

        $this->add_hooks();
    }

    public function get_opt($key, $default=null) {
        if(isset($this->opts[$key])) {
            return $this->opts[$key];
        }
        if(!is_null($default) || !isset($this->_defaults[$key])) return $default;
        return $this->_defaults[$key];
    }

    function log($log) {
        $date = new DateTime();
        $date_fmt= $date->format('Y-m-d H:i:s');

        $log = "[$date_fmt] " . $log ."\n";
        file_put_contents(plugin_dir_path(__FILE__).'debug.log', $log, FILE_APPEND);
    }

    public function add_hooks() {
        add_action('template_redirect', array($this, 'webhook_listener'));
        add_action('wp_head', array($this, 'maybe_do_critical'), 7);
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

        if(isset($data['css']['critical'])) {
            $this->save_to_cache($data['css']['critical'], ['url'=>$data['url']]);
        }
        if(isset($data['css']['uncritical'])) {
            $this->save_to_cache($data['css']['uncritical'], ['url'=>$data['url'], 'uncritical'=>true]);
        }
        header("HTTP/1.0 200 OK");
        echo 'OK';
        exit;
    }

    public function maybe_do_critical() {

        //Do nothing if we're building
        if(isset($_REQUEST['critical_css'])) return;

        // Check cache for critical css file for this page
        $cache_status = $this->check_cache_status();

        echo '<!-- CACHE STATUS: '.$cache_status.' -->';

        if($cache_status !== 'fresh') {//might be expired or stale

            // If not in cache fire off critical request
            $this->request_critical();

            //IF use stale, use stale until new is generated
            if($cache_status === 'stale' && $this->get_opt('use_stale')) {
                $this->do_critical();
            }

        } else {//Fresh

            //Use critical & defer
            $this->do_critical();

        }
    }

    //Print critical styles & defer rest.
    //WARNING: this function does no checks!
    public function do_critical() {
        // If in cache, print critical
        $this->print_critical();

        $args = [];
        if($this->get_opt('use_uncritical')) {
            $uncritical_cache_file = $this->get_cache_file_full($this->get_cache_file_name(true));
            $exists = file_exists($uncritical_cache_file);

            if($exists) {
                $args['uncritical'] = $uncritical_cache_file;
            }
        }

        // and defer other css
        $this->defer_stylesheets($args);

    }

    public function check_cache_status() {
        $cache_time = $this->get_opt('cache_time');

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


    public function request_critical() {

        if(!$this->get_opt('api_key')) {
            //TODO log?
            $this->log('Missing API Key');
            return;
        }

        $url = $this->get_url();
        //$response = wp_remote_post('https://critical-css-gen.herokuapp.com/critical', [//'http://localhost:8000/critical',[
        $response = wp_remote_post($this->api_url,[
            'method' => 'POST',
            'timeout' => 60,
            'redirection' => 5,
            'blocking' => false,//true,
            'body' => array(
                'key' => $this->get_opt('api_key'),
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

    public function print_critical() {
        $cache_file = $this->get_cache_file_full();
        $exists = file_exists($cache_file);
        if(!$exists) return; //TODO maybe log?

        $critical_css = file_get_contents($cache_file); //TODO check this
        ?>
        <!-- INLINE CRITICAL CSS -->
        <style type="text/css">
            <?php echo $critical_css;?>
        </style>
        <?php
    }


    public function defer_stylesheets($args=null) {
        global $wp_styles;

        if(is_null($args)) $args = [];

        //print_r($wp_styles);

        $ignore_admin = [
            'wp-includes/css/admin-bar.css'
        ];

        $ignore_styles = array_merge($ignore_admin, $this->get_opt('ignore_styles'));
        //echo '<!-- IGNORE: '.print_r($ignore_styles, true). '-->';

        //Collect any inline styles and print them later
        $inline_styles = "";

        $deferred_styles = [];

        //foreach($styles_to_defer as $handle) {
        foreach($wp_styles->registered as $handle=>$style) {
            if(wp_style_is($handle, 'enqueued') && wp_style_is($handle, 'registered')) {
                $style = $wp_styles->registered[$handle];
                $src = $style->src;
                $ver = $style->ver;
                //$deps = $style->deps; //is an array of dep handles

                //If its a core asset, add home_url to src
                if(strpos($src, '/') ===0) {
                    $src = home_url($src);
                }

                //check if in ignore_styles
                $home_url = home_url();
                $home_url_len = strlen($home_url);
                $check_part = $src;
                if(strpos($src, $home_url) === 0) {
                    $check_part = substr($src, $home_url_len);
                }
                if(strpos($check_part, '/') ==0) {
                    $check_part = substr($check_part, 1);
                }
                //echo '<!-- CHECK: '.$check_part .'-->';
                if(in_array($check_part, $ignore_styles)) {
                    //echo '<!-- IGNORED: '.$handle.' -->' ;
                    continue;
                }

                wp_dequeue_style($handle);

                //If has inline, collect
                if(isset($style->extra['after'])) {
                    foreach($style->extra['after'] as $after) {
                        $inline_styles .= $after . "\n";
                    }
                }

                if(!empty($args['uncritical'])) {
                    continue;
                }

                $deferred_styles[$handle] = [
                    'src'=>$src,
                    'ver'=>$ver
                ];
            }
        }

        //Add modified links
        if(!empty($deferred_styles)) {
            add_action('wp_footer', function() use ($deferred_styles) {
                foreach($deferred_styles as $handle=>$style) {
                    $src = $style['src'];
                    if(!empty($style['ver'])) $src .= '?ver='.$style['ver'];
                    ?>
                    <link id="<?= $handle; ?>-css" rel="preload" href="<?= $src; ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
                    <noscript><link id="<?= $handle; ?>-css" rel="stylesheet" href="<?= $src; ?>"></noscript>
                    <?php
                }
            },0);

            $this->add_load_css_polyfill();
        }
        if(!empty($args['uncritical'])) {
            $path = 'css_cache' .DIRECTORY_SEPARATOR . basename($args['uncritical']);
            $src = content_url( $path );
            ?>
            <link id="uncritical-css" rel="preload" href="<?= $src; ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
            <noscript><link id="<?= $handle; ?>-css" rel="stylesheet" href="<?= $src; ?>"></noscript>
            <?php
            $this->add_load_css_polyfill();
        }

        //Print extra inline styles
        if(!empty($inline_styles)) {
            add_action('wp_footer', function() use ($inline_styles) {
                echo '<!-- EXTRA INLINE --><style type="text/css">'.$inline_styles.'</style>';
            },1);
        }
    }

    public function add_load_css_polyfill() {
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
$st_critical_css = new ST_CriticalCss();
//TODO replace with singleton?
if(!function_exists('get_st_critical_css')) {
    function get_st_critical_css() {
        global $st_critical_css;
        return $st_critical_css;
    }
}


register_activation_hook( __FILE__, array( 'ST_CriticalCss', 'install' ) );
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

require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/sebtoombs/wordpress-critical-css',
    __FILE__, //Full path to the main plugin file or functions.php.
    'critical-css'
);
//Optional: Set the branch that contains the stable release.
$myUpdateChecker->setBranch('release');

require_once 'includes/class-critical-css-base.php';
require_once 'includes/class-critical-css-logger.php';
require_once 'includes/class-critical-css-options.php';
require_once 'includes/class-critical-css-api.php';
require_once 'includes/class-critical-css-cache.php';
require_once 'includes/class-critical-css-status.php';
require_once 'includes/class-critical-css-frontend.php';

require_once 'admin/class-admin.php';
require_once 'admin/class-admin-ajax.php';

class ST_CriticalCss {

    private $deps = [];

    public function __construct() {


        $get_dep = array($this, 'get_dep');

        $this->deps['logger'] = new ST_CriticalCss_Logger($get_dep);
        $this->deps['options'] = new ST_CriticalCss_Options($get_dep);
        $this->deps['api'] = new ST_CriticalCss_Api($get_dep);
        $this->deps['cache'] = new ST_CriticalCss_Cache($get_dep);
        $this->deps['status'] = new ST_CriticalCss_Status($get_dep);
        $this->deps['frontend'] = new ST_CriticalCss_Frontend($get_dep);

        $this->deps['admin'] = new ST_CriticalCss_Admin($get_dep);
        $this->deps['admin_ajax'] = new ST_CriticalCss_AdminAjax($get_dep);
    }

    public function get_dep($name) {
        if(isset($this->deps[$name])) {
            return $this->deps[$name];
        }
        return false;
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

if(!function_exists('get_st_critical_css')) {
    function get_st_critical_css() {
        global $st_critical_css;
        return $st_critical_css;
    }
}


register_activation_hook( __FILE__, array( 'ST_CriticalCss', 'install' ) );
<?php

if(!class_exists('ST_CriticalCss_Admin')) {
    class ST_CriticalCss_Admin extends ST_CriticalCss_Base {

        public function init() {
            $this->add_hooks();
        }

        public function add_hooks() {
            add_action( 'admin_menu', array($this, 'add_options_page'));
            add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue_styles' ));
        }

        public function add_options_page() {
            // add top level menu page
            add_menu_page(
                'Critical CSS Options',
                'Critical CSS',
                'manage_options',
                'critical-css',
                array($this, 'options_page_html')
            );
        }
        public function options_page_html() {
            // check user capabilities
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            //$options = $this->get_dep('options');
            ?>
            <div class="wrap critical-css-admin">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <div id="critical-css-admin-app"><!-- React App Goes Here --></div>
            </div>
            <?php
        }
        public function admin_enqueue_styles($screen) {
            if('toplevel_page_critical-css' !== $screen) return;

            wp_enqueue_script('critical-css-admin', plugin_dir_url( __FILE__ ) . 'app/assets/js/admin.js', array(), null);
            wp_localize_script('critical-css-admin', 'critical_css_admin', $this->localise_script());
        }
        public function localise_script() {
            return [
                'nonce'=>wp_create_nonce('critical-css'),
                'ajax_prefix'=>'critical_css_'
            ];
        }
    }
}
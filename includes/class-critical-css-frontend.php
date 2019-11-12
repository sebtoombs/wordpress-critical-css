<?php

if(!class_exists('ST_CriticalCss_Frontend')) {
    class ST_CriticalCss_Frontend extends ST_CriticalCss_Base {

        public function init() {
            $this->add_hooks();
        }

        public function add_hooks() {
            add_action('wp_head', array($this, 'maybe_do_critical'), 7);
        }

        public function maybe_do_critical() {

            //Do nothing if we're building, or if is 404
            if(isset($_REQUEST['critical_css']) || is_404()) return;

            $Cache = $this->get_dep('cache');
            $Api = $this->get_dep('api');
            $Options = $this->get_dep('options');

            // Check cache for critical css file for this page
            $cache_status = $Cache->check_cache_status();

            echo '<!-- CACHE STATUS: '.$cache_status.' -->';

            if($cache_status !== 'fresh') {//might be expired or stale

                // If not in cache fire off critical request
                $Api->request_critical();

                //IF use stale, use stale until new is generated
                if($cache_status === 'stale' && $Options->get_opt('use_stale')) {
                    $this->do_critical();
                }

            } else {//Fresh

                //Use critical & defer
                $this->do_critical();

            }
        }


        public function do_critical() {
            // If in cache, print critical
            $this->print_critical();

            $Options = $this->get_dep('options');

            $args = [];
            if($Options->get_opt('use_uncritical')) {
                $Cache = $this->get_dep('cache');

                $uncritical_cache_file = $Cache->get_cache_file_full($Cache->get_cache_file_name(true));
                $exists = file_exists($uncritical_cache_file);

                if($exists) {
                    $args['uncritical'] = $uncritical_cache_file;
                }
            }

            // and defer other css
            $this->defer_stylesheets($args);

        }

        public function print_critical() {
            $cache = $this->get_dep('cache');

            if(!($critical_css = $cache->get_from_cache())) return;
            ?>
            <!-- INLINE CRITICAL CSS -->
            <style type="text/css">
                <?php echo $critical_css;?>
            </style>
            <?php
        }


        public function defer_stylesheets($args=null) {
            global $wp_styles;

            $Options = $this->get_dep('options');

            if(is_null($args)) $args = [];

            //print_r($wp_styles);

            $ignore_admin = [
                'wp-includes/css/admin-bar.css'
            ];

            $ignore_styles = $Options->get_opt('ignore_styles');
            if(!is_array($ignore_styles)) $ignore_styles = [];

            $ignore_styles = array_merge($ignore_admin, $ignore_styles);
            echo '<!-- IGNORE: '.print_r($ignore_styles, true). '-->';

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
                        echo '<!-- IGNORED: '.$handle.' -->' ;
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
    }
}
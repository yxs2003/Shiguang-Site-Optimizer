<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Update {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['update'] ?? [];

        if (!empty($opt['core_update_disable'])) {
            add_filter('auto_update_core', '__return_false');
            add_filter('pre_site_transient_update_core', '__return_null');
        }
        if (!empty($opt['theme_update_disable'])) {
            add_filter('auto_update_theme', '__return_false');
            add_filter('pre_site_transient_update_themes', '__return_null');
        }
        if (!empty($opt['plugin_update_disable'])) {
            add_filter('auto_update_plugin', '__return_false');
            add_filter('pre_site_transient_update_plugins', '__return_null');
        }
    }
}

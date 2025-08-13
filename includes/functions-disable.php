<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Disable {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['disable'] ?? [];

        // 禁用 translations_api
        if (!empty($opt['translations_api'])) {
            add_filter('translations_api', '__return_empty_array', 10, 3);
        }

        // 禁用 wp_check_php_version / wp_check_browser_version （尽力阻断）
        if (!empty($opt['wp_check_php_version'])) {
            remove_action( 'admin_init', 'wp_check_php_version' );
        }
        if (!empty($opt['wp_check_browser_version'])) {
            remove_action( 'admin_init', 'wp_check_browser_version' );
        }

        // 禁用 current_screen
        if (!empty($opt['current_screen'])) {
            remove_filter( 'current_screen', 'current_screen' );
        }
    }
}

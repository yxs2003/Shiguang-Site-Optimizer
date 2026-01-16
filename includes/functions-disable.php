<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Disable {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['disable'] ?? [];

        // 禁用 translations_api (禁止后台自动下载语言包信息)
        if (!empty($opt['translations_api'])) {
            add_filter('translations_api', '__return_empty_array');
        }

        // 仅在后台执行以下检查的禁用，前台本来就不执行，避免无意义 Hook
        if ( is_admin() ) {
            // 禁用 wp_check_php_version
            if (!empty($opt['wp_check_php_version'])) {
                // 使用 remove_action 时需要确保优先级正确，通常 init 钩子是可以的
                remove_action( 'admin_init', 'wp_check_php_version' );
            }
            
            // 禁用 wp_check_browser_version
            if (!empty($opt['wp_check_browser_version'])) {
                remove_action( 'admin_init', 'wp_check_browser_version' );
            }
        }

        //风险警告：禁用 current_screen 会导致众多插件（WooCommerce, ACF, Elementor）报错或白屏。
            if (!empty($opt['current_screen'])) {
                remove_filter( 'current_screen', 'current_screen' ); 
        }
    }
}
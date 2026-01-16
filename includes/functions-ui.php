<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_UI {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['ui'] ?? [];

        // 隐藏登录页 WordPress logo
        if (!empty($opt['login_logo_hide'])) {
            add_action('login_enqueue_scripts', function(){
                // 增加 !important 权重
                echo '<style>.login h1 a{display:none !important;visibility:hidden;}</style>';
            });
        }

        // 前台隐藏管理工具条
        if (!empty($opt['frontend_adminbar_hide'])) {
            add_filter('show_admin_bar', '__return_false');
        }

        // 后台左上 WP logo 隐藏 (W 符号)
        if (!empty($opt['admin_wp_logo_hide'])) {
            add_action('admin_head', function(){
                // 同时隐藏 Logo 和它旁边的菜单
                echo '<style>#wp-admin-bar-wp-logo{display:none !important;}</style>';
            });
        }

        // 登录页语言选择隐藏（WP 5.9+）
        if (!empty($opt['login_language_selector_hide'])) {
            add_filter('login_display_language_dropdown', '__return_false');
        }
    }
}
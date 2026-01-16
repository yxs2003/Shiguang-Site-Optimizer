<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Update {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['update'] ?? [];

        // 核心更新
        if (!empty($opt['core_update_disable'])) {
            add_filter('auto_update_core', '__return_false');
            add_filter('pre_site_transient_update_core', '__return_null');
            // 清理 Cron 任务
            if ( wp_next_scheduled( 'wp_version_check' ) ) {
                wp_clear_scheduled_hook( 'wp_version_check' );
            }
        }

        // 主题更新
        if (!empty($opt['theme_update_disable'])) {
            add_filter('auto_update_theme', '__return_false');
            add_filter('pre_site_transient_update_themes', '__return_null');
            if ( wp_next_scheduled( 'wp_update_themes' ) ) {
                wp_clear_scheduled_hook( 'wp_update_themes' );
            }
        }

        // 插件更新
        if (!empty($opt['plugin_update_disable'])) {
            add_filter('auto_update_plugin', '__return_false');
            add_filter('pre_site_transient_update_plugins', '__return_null');
            if ( wp_next_scheduled( 'wp_update_plugins' ) ) {
                wp_clear_scheduled_hook( 'wp_update_plugins' );
            }
        }
    }
}
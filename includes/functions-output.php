<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Output {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['output'] ?? [];

        // 移除 WP 版本号
        if (!empty($opt['remove_wp_version'])) {
            add_filter('the_generator', '__return_empty_string', 99);
        }

        // 移除资源版本号 ?ver=
        if (!empty($opt['remove_asset_version'])) {
            add_filter('style_loader_src', [__CLASS__, 'strip_ver'], 9999);
            add_filter('script_loader_src', [__CLASS__, 'strip_ver'], 9999);
        }

        // 移除 dns-prefetch
        if (!empty($opt['remove_dns_prefetch'])) {
            add_filter('wp_resource_hints', [__CLASS__, 'remove_dns_prefetch'], 10, 2);
        }

        // 移除 JSON API Link
        if (!empty($opt['remove_json_api_link'])) {
            remove_action('wp_head', 'rest_output_link_wp_head', 10);
            remove_action('template_redirect', 'rest_output_link_header', 11);
        }

        // 移除文章前后页 meta
        if (!empty($opt['remove_adjacent_posts'])) {
            remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
        }

        // 移除 head feed 链接
        if (!empty($opt['remove_head_feed_links'])) {
            remove_action('wp_head', 'feed_links_extra', 3);
            remove_action('wp_head', 'feed_links', 2);
        }

        // 移除 Gutenberg 前端样式
        if (!empty($opt['remove_wp_block_library_css'])) {
            add_action('wp_enqueue_scripts', function(){
                wp_dequeue_style('wp-block-library');
                wp_dequeue_style('wp-block-library-theme');
                wp_dequeue_style('global-styles'); // 旧版
            }, 999);
        }

        // 移除 Dashicons（前台）
        if (!empty($opt['remove_dashicons'])) {
            add_action('wp_enqueue_scripts', function(){
                if ( ! is_user_logged_in() ) {
                    wp_deregister_style('dashicons');
                }
            }, 100);
        }

        // 移除 RSD
        if (!empty($opt['remove_rsd'])) {
            remove_action('wp_head', 'rsd_link');
        }

        // 移除经典主题样式注入
        if (!empty($opt['remove_classic_theme_css'])) {
            add_action('wp_enqueue_scripts', function(){
                wp_dequeue_style('classic-theme-styles');
            }, 100);
        }

        // 移除全局样式（wp_global_styles）
        if (!empty($opt['remove_global_styles'])) {
            remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
            add_action('wp_head', function(){
                remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
            }, 1);
        }

        // 移除 SVG 滤镜
        if (!empty($opt['remove_svg_filters'])) {
            remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
        }

        // 禁用 robots 标签输出
        if (!empty($opt['disable_robots_tag'])) {
            remove_action( 'wp_head', 'wp_robots', 2 );
        }
    }

    public static function strip_ver( $src ){
        if ( strpos($src, 'ver=') !== false ) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    public static function remove_dns_prefetch( $hints, $relation_type ){
        if ( 'dns-prefetch' === $relation_type ) {
            return [];
        }
        return $hints;
    }
}

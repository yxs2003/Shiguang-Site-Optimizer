<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Post {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['post'] ?? [];
        
        // 安全获取 api 配置，防止数组解构报错
        $api_opt = isset($o['api']) ? $o['api'] : [];

        // 关闭修订版本
        if (!empty($opt['revisions_disable'])) {
            add_filter( 'wp_revisions_to_keep', '__return_zero', 999 );
        }

        // 关闭自动保存
        if (!empty($opt['autosave_disable'])) {
            add_action('admin_enqueue_scripts', function(){
                wp_deregister_script('autosave');
            });
        }

        // 关闭大图阈值压缩
        if (!empty($opt['image_big_threshold_disable'])) {
            add_filter( 'big_image_size_threshold', '__return_false' );
        }

        // 禁止生成多尺寸
        if (!empty($opt['intermediate_image_sizes_disable'])) {
            add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
        }

        // 禁止插入图片添加宽高/class (注意：这会影响 CLS，但尊重用户选择)
        if (!empty($opt['image_size_attributes_disable'])) {
            add_filter( 'wp_get_attachment_image_attributes', function($attr){ 
                // 仅在前台移除，避免后台媒体库显示异常
                if ( is_admin() ) return $attr;
                
                if ( is_array($attr) ) {
                    unset($attr['width'], $attr['height'], $attr['class']); 
                }
                return $attr; 
            }, 99 );
        }

        // 禁止 -scaled 后缀
        if (!empty($opt['image_scaling_disable'])) {
            add_filter( 'big_image_size_threshold', '__return_false' );
        }

        // 关闭字符转码 (wptexturize)
        if (!empty($opt['content_texturize_disable'])) {
            remove_filter( 'the_content', 'wptexturize' );
            remove_filter( 'the_title', 'wptexturize' );
            remove_filter( 'comment_text', 'wptexturize' );
        }

        // 禁止 Auto Embeds
        if (!empty($opt['auto_embeds_disable'])) {
            global $wp_embed;
            if ( is_object($wp_embed) ) {
                remove_filter('the_content', array($wp_embed, 'run_shortcode'), 8);
                remove_filter('the_content', array($wp_embed, 'autoembed'), 8);
            }
        }

        // 禁止文章 oEmbed (Discovery Links)
        if (!empty($opt['post_embed_disable'])) {
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');
            add_filter('embed_oembed_discover', '__return_false');
        }

        // 禁止 Gutenberg 编辑器 (回退到经典编辑器)
        if (!empty($opt['gutenberg_disable'])) {
            add_filter( 'use_block_editor_for_post', '__return_false', 10 );
            // 同时移除前端样式 (双重保险)
            add_action('wp_enqueue_scripts', function(){
                wp_dequeue_style('wp-block-library');
            }, 100);
        }

        // 禁止小工具区块编辑器
        if (!empty($opt['widget_block_editor_disable'])) {
            add_filter( 'use_widgets_block_editor', '__return_false' );
            add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' ); // 兼容旧版
        }

        // Emoji 禁用 (与 Post 紧密相关)
        if (!empty($api_opt['emoji_disable'])) {
            add_action( 'init', [__CLASS__, 'disable_emoji'] );
        }
    }

    public static function disable_emoji(){
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        
        // 这是一个 Filter，不是 Action，需要返回 false
        add_filter('emoji_svg_url', '__return_false');
        
        // 移除 TinyMCE 插件
        add_filter( 'tiny_mce_plugins', function($plugins){
            if ( is_array( $plugins ) ) {
                return array_diff( $plugins, array( 'wpemoji' ) );
            }
            return $plugins;
        });
    }
}
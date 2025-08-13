<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Post {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['post'] ?? [];
        $api = (get_option(SHIGUANG_OPTION_KEY)['api'] ?? []);

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

        // 禁止插入图片添加宽高/class
        if (!empty($opt['image_size_attributes_disable'])) {
            add_filter( 'wp_get_attachment_image_attributes', function($attr){ 
                unset($attr['width'], $attr['height'], $attr['class']); 
                return $attr; 
            }, 99 );
        }

        // 禁止 -scaled
        if (!empty($opt['image_scaling_disable'])) {
            add_filter( 'big_image_size_threshold', '__return_false' );
        }

        // 关闭字符转码
        if (!empty($opt['content_texturize_disable'])) {
            remove_filter( 'the_content', 'wptexturize' );
            remove_filter( 'the_title', 'wptexturize' );
            remove_filter( 'comment_text', 'wptexturize' );
        }

        // 禁止 Auto Embeds
        if (!empty($opt['auto_embeds_disable'])) {
            remove_filter('the_content', array($GLOBALS['wp_embed'], 'run_shortcode'), 8);
            remove_filter('the_content', array($GLOBALS['wp_embed'], 'autoembed'), 8);
        }

        // 禁止文章 Embeds
        if (!empty($opt['post_embed_disable'])) {
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');
            add_filter('embed_oembed_discover', '__return_false');
        }

        // 禁止 Gutenberg
        if (!empty($opt['gutenberg_disable'])) {
            add_filter( 'use_block_editor_for_post', '__return_false', 10 );
        }

        // 禁止小工具区块编辑器
        if (!empty($opt['widget_block_editor_disable'])) {
            add_filter( 'use_widgets_block_editor', '__return_false' );
        }

        // 接口相关（与 post 关系紧密的放这里二次保障）
        if (!empty($api['emoji_disable'])) {
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
        add_filter('emoji_svg_url', '__return_false');
    }
}

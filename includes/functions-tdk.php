<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_TDK {

    public static function init(){
        add_action('wp_head', [__CLASS__, 'output_meta'], 1);
    }

    public static function output_meta(){
        // 1. 后台不输出
        if ( is_admin() ) return;

        // 2. 关键修复：仅在首页输出全局配置的 TDK
        // 如果在文章页也输出相同的 description，会导致严重的 SEO 降权（重复元描述）
        if ( ! ( is_home() || is_front_page() ) ) {
            return;
        }

        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $tdk = $o['tdk'] ?? [];
        
        $desc  = $tdk['meta_description'] ?? '';
        $keys  = $tdk['meta_keywords'] ?? '';

        // 输出 Meta 标签
        if ( !empty($desc) ) {
            echo '<meta name="description" content="'. esc_attr($desc) .'">'."\n";
        }
        if ( !empty($keys) ) {
            echo '<meta name="keywords" content="'. esc_attr($keys) .'">'."\n";
        }
        
        // 注意：Site Title 通常由 wp_title 或 theme_support('title-tag') 处理，
        // 强制 echo <title> 容易导致标签重复，故此处不处理 Title。
    }
}
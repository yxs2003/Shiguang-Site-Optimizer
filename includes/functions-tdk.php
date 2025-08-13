<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_TDK {

    public static function init(){
        add_action('wp_head', [__CLASS__, 'output_meta'], 1);
    }

    public static function output_meta(){
        if ( is_admin() ) return;
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $tdk = $o['tdk'] ?? [];
        $title = $tdk['site_title'] ?? '';
        $desc  = $tdk['meta_description'] ?? '';
        $keys  = $tdk['meta_keywords'] ?? '';

        // 简单输出（不与专业 SEO 插件冲突，优先级低）
        if ( !empty($desc) ) {
            echo '<meta name="description" content="'. esc_attr($desc) .'">'."\n";
        }
        if ( !empty($keys) ) {
            echo '<meta name="keywords" content="'. esc_attr($keys) .'">'."\n";
        }
        // 标题不强制覆盖，交由主题或 SEO 插件处理
    }
}

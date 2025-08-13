<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Style {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['style'] ?? [];

        // 全站变灰
        if (!empty($opt['site_gray'])) {
            add_action('wp_head', function(){
                echo '<style>html{filter:grayscale(100%);} img,video{filter:grayscale(100%);}</style>';
            }, 99);
        }

        // 悬停变色
        if (!empty($opt['hover_color_enable'])) {
            $color = $opt['hover_color_value'] ?? '#3b82f6';
            add_action('wp_head', function() use ($color){
                $c = esc_attr($color);
                echo '<style>a:hover, .entry-content a:hover{color:'.$c.'!important;}</style>';
            }, 99);
        }
    }
}

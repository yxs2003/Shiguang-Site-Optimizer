<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Style {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['style'] ?? [];

        // 全站变灰 (特殊日子)
        if (!empty($opt['site_gray'])) {
            add_action('wp_head', function(){
                // 增加 -webkit- 前缀兼容旧版浏览器
                echo '<style>
                    html {
                        -webkit-filter: grayscale(100%);
                        filter: grayscale(100%);
                    }
                </style>';
            }, 99);
        }

        // 悬停变色
        if (!empty($opt['hover_color_enable'])) {
            $color = $opt['hover_color_value'] ?? '#3b82f6';
            add_action('wp_head', function() use ($color){
                // 确保颜色代码安全，并增加 !important
                $c = sanitize_hex_color($color);
                if (!$c) $c = '#3b82f6';
                
                echo '<style>
                    a:hover, 
                    .entry-content a:hover, 
                    .widget a:hover,
                    .site-footer a:hover {
                        color: '.$c.' !important;
                    }
                </style>';
            }, 99);
        }
    }
}
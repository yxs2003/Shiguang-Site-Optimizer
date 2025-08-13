<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Code {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['code'] ?? [];

        // <head> 注入
        add_action('wp_head', function() use ($opt){
            if (!empty($opt['head_code'])) {
                echo "\n<!-- Shiguang head code -->\n" . $opt['head_code'] . "\n";
            }
            if (!empty($opt['custom_css'])) {
                echo "\n<!-- Shiguang custom CSS -->\n<style>\n" . $opt['custom_css'] . "\n</style>\n";
            }
        }, 99);

        // 页脚注入
        add_action('wp_footer', function() use ($opt){
            if (!empty($opt['footer_code'])) {
                echo "\n<!-- Shiguang footer code -->\n" . $opt['footer_code'] . "\n";
            }
        }, 99);
    }
}

<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 加速与上传重命名（按开关联动）
 * - jsDelivr/Gravatar/Google Fonts CSS/Fonts Files/Ajax → cdn.iocdn.cc
 * - 上传 MD5 重命名、时间戳（由 image 组开关控制）
 */
class Shiguang_Accelerate {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $acc = $o['accelerate'] ?? [];
        $img = $o['image'] ?? [];

        // 输出缓冲替换（按开关）
        if ( self::any_accelerate_enabled($acc) ) {
            add_action('template_redirect', [__CLASS__, 'start_buffer']);
        }
    }

    private static function any_accelerate_enabled($acc){
        return !empty($acc['jsdelivr_to_iocdn']) ||
               !empty($acc['gravatar_to_iocdn']) ||
               !empty($acc['gfonts_css_to_iocdn']) ||
               !empty($acc['gfonts_files_to_iocdn']) ||
               !empty($acc['gajax_to_iocdn']);
    }

    public static function start_buffer(){
        // 关键修复：防止在 Feed, API, AJAX, XML RPC 或 Robots.txt 中执行替换
        if ( is_admin() || is_feed() || is_trackback() || is_robots() ) {
            return;
        }

        // 兼容 REST API / Block Editor 请求
        if ( defined('REST_REQUEST') && REST_REQUEST ) {
            return;
        }
        
        // 兼容 XML-RPC
        if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
            return;
        }

        ob_start([__CLASS__, 'replace_cdn_urls']);
    }

    public static function replace_cdn_urls($buffer){
        // 如果缓冲区为空，直接返回
        if (empty($buffer) || !is_string($buffer)) {
            return $buffer;
        }

        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $acc = $o['accelerate'] ?? [];

        // jsDelivr 前端库
        if (!empty($acc['jsdelivr_to_iocdn'])) {
            $buffer = str_replace('//cdn.jsdelivr.net', '//cdn.iocdn.cc', $buffer);
        }

        // Gravatar
        if (!empty($acc['gravatar_to_iocdn'])) {
            // 能够更精准的匹配，防止替换掉页面普通文本
            $buffer = str_replace(
                array('//www.gravatar.com/avatar', '//0.gravatar.com/avatar', '//1.gravatar.com/avatar', '//2.gravatar.com/avatar', '//secure.gravatar.com/avatar'), 
                '//cdn.iocdn.cc/avatar', 
                $buffer
            );
        }

        // Google Fonts CSS
        if (!empty($acc['gfonts_css_to_iocdn'])) {
            $buffer = str_replace('//fonts.googleapis.com/css', '//cdn.iocdn.cc/css', $buffer);
            $buffer = str_replace('//fonts.googleapis.com/icon', '//cdn.iocdn.cc/icon', $buffer);
            $buffer = str_replace('//fonts.googleapis.com/earlyaccess', '//cdn.iocdn.cc/earlyaccess', $buffer);
        }

        // Google Fonts 文件
        if (!empty($acc['gfonts_files_to_iocdn'])) {
            $buffer = str_replace('//fonts.gstatic.com/s', '//cdn.iocdn.cc/s', $buffer);
            $buffer = str_replace('//themes.googleusercontent.com/static', '//cdn.iocdn.cc/static', $buffer);
        }

        // Google Ajax
        if (!empty($acc['gajax_to_iocdn'])) {
            $buffer = str_replace('//ajax.googleapis.com/ajax', '//cdn.iocdn.cc/ajax', $buffer);
        }

        return $buffer;
    }
}
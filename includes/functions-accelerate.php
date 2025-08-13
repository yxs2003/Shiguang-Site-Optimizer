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

        // 上传重命名 / 时间戳由 Shiguang_Image 负责，这里不重复注册
        // 仅确保兼容：啥也不做
    }

    private static function any_accelerate_enabled($acc){
        return !empty($acc['jsdelivr_to_iocdn']) ||
               !empty($acc['gravatar_to_iocdn']) ||
               !empty($acc['gfonts_css_to_iocdn']) ||
               !empty($acc['gfonts_files_to_iocdn']) ||
               !empty($acc['gajax_to_iocdn']);
    }

    public static function start_buffer(){
        ob_start([__CLASS__, 'replace_cdn_urls']);
    }

    public static function replace_cdn_urls($buffer){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $acc = $o['accelerate'] ?? [];

        // jsDelivr 前端库
        if (!empty($acc['jsdelivr_to_iocdn'])) {
            $buffer = str_replace('//cdn.jsdelivr.net', '//cdn.iocdn.cc', $buffer);
        }

        // Gravatar
        if (!empty($acc['gravatar_to_iocdn'])) {
            $buffer = str_replace('//www.gravatar.com/avatar', '//cdn.iocdn.cc/avatar', $buffer);
            $buffer = str_replace(array('//0.gravatar.com/avatar','//1.gravatar.com/avatar','//2.gravatar.com/avatar','//secure.gravatar.com/avatar'), '//cdn.iocdn.cc/avatar', $buffer);
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

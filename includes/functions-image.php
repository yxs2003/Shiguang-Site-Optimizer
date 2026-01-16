<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Image {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['image'] ?? [];

        // WebP 支持（即使 WP 5.8+ 内置了，这里确保 MIME 类型被允许）
        if (!empty($opt['webp_allow'])) {
            add_filter('upload_mimes', [__CLASS__, 'allow_webp']);
        }

        // SVG 支持（带安全清洗）
        if (!empty($opt['svg_allow'])) {
            add_filter('upload_mimes', [__CLASS__, 'allow_svg']);
            add_filter('wp_check_filetype_and_ext', [__CLASS__, 'fix_svg_mime_type'], 10, 4);
            add_filter('wp_handle_upload_prefilter', [__CLASS__, 'check_svg_safety']);
        }

        // 上传重命名（MD5/随机哈希）
        if (!empty($opt['uploads_md5_rename'])) {
            add_filter('wp_handle_upload_prefilter', [__CLASS__, 'rename_file_hash']);
        }

        // 上传时间戳（仅在未开启 MD5 重命名时生效，避免双重重命名）
        if (empty($opt['uploads_md5_rename']) && !empty($opt['uploads_timestamp_enable'])) {
            add_filter('wp_handle_upload_prefilter', [__CLASS__, 'rename_file_timestamp']);
        }
    }

    /**
     * 允许 WebP
     */
    public static function allow_webp($mimes) {
        $mimes['webp'] = 'image/webp';
        return $mimes;
    }

    /**
     * 允许 SVG
     */
    public static function allow_svg($mimes) {
        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
    }

    /**
     * 修正 SVG 的 MIME 类型检测
     */
    public static function fix_svg_mime_type($data, $file, $filename, $mimes) {
        $ext = isset($data['ext']) ? $data['ext'] : '';
        if ( empty($ext) ) {
            $check_ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
            if ( 'svg' === $check_ext ) {
                $data['ext']  = 'svg';
                $data['type'] = 'image/svg+xml';
            }
        }
        return $data;
    }

    /**
     * SVG 安全检查：防止 XSS 注入
     * 简单的字符串匹配，拦截包含 script 的 SVG
     */
    public static function check_svg_safety($file) {
        if ( $file['type'] === 'image/svg+xml' || strpos($file['name'], '.svg') !== false ) {
            $content = file_get_contents($file['tmp_name']);
            // 简单的关键词黑名单
            if ( stripos($content, '<script') !== false || stripos($content, 'javascript:') !== false ) {
                $file['error'] = '为了安全起见，拒绝上传包含脚本代码的 SVG 文件。';
            }
        }
        return $file;
    }

    /**
     * 使用哈希重命名文件 (比 md5_file 更快)
     * md5_file 读取大文件会非常慢，使用 uniqid + 随机数足够保证唯一性且极快
     */
    public static function rename_file_hash($file) {
        $info = pathinfo($file['name']);
        $ext = empty($info['extension']) ? '' : '.' . strtolower($info['extension']);
        // 生成规则：md5(文件名 + 时间 + 随机数)
        $hash = md5($info['filename'] . time() . mt_rand(100, 999));
        $file['name'] = $hash . $ext;
        return $file;
    }

    /**
     * 仅追加时间戳
     */
    public static function rename_file_timestamp($file) {
        $info = pathinfo($file['name']);
        $ext = empty($info['extension']) ? '' : '.' . strtolower($info['extension']);
        $file['name'] = $info['filename'] . '-' . time() . $ext;
        return $file;
    }
}
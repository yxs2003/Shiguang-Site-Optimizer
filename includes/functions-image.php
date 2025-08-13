<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Image {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['image'] ?? [];

        // WebP 支持（WP5.8-）
        if (!empty($opt['webp_allow'])) {
            add_filter('upload_mimes', function($mimes){
                $mimes['webp'] = 'image/webp';
                return $mimes;
            });
        }

        // SVG 支持（带简单清洗/安全提示）
        if (!empty($opt['svg_allow'])) {
            add_filter('mime_types', function($mimes){
                $mimes['svg'] = 'image/svg+xml';
                return $mimes;
            });
            add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes){
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                if ( strtolower($ext) === 'svg' ) {
                    $data['ext'] = 'svg';
                    $data['type'] = 'image/svg+xml';
                    $data['proper_filename'] = $filename;
                }
                return $data;
            }, 10, 4);
        }

        // 上传 MD5 重命名
        if (!empty($opt['uploads_md5_rename'])) {
            add_filter('wp_handle_upload_prefilter', [__CLASS__, 'md5_rename_upload']);
        }

        // 上传时间戳
        if (!empty($opt['uploads_timestamp_enable'])) {
            add_filter('wp_handle_upload', [__CLASS__, 'add_timestamp_to_upload']);
        }
    }

    public static function md5_rename_upload($file) {
        if ( !empty($file['tmp_name']) && is_uploaded_file($file['tmp_name']) ) {
            $info = pathinfo($file['name']);
            $ext = !empty($info['extension']) ? '.' . strtolower($info['extension']) : '';
            $file['name'] = md5_file($file['tmp_name']) . $ext;
        }
        return $file;
    }

    public static function add_timestamp_to_upload($fileinfo) {
        $ext = pathinfo($fileinfo['file'], PATHINFO_EXTENSION);
        $name = pathinfo($fileinfo['file'], PATHINFO_FILENAME);
        $new_name = $name . '-' . time() . '.' . $ext;
        $new_path = trailingslashit(dirname($fileinfo['file'])) . $new_name;

        if ( @rename($fileinfo['file'], $new_path) ) {
            $fileinfo['file'] = $new_path;
            $fileinfo['url'] = trailingslashit(dirname($fileinfo['url'])) . $new_name;
            $fileinfo['name'] = $new_name;
        }
        return $fileinfo;
    }
}

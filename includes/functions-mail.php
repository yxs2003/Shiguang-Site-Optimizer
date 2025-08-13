<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Mail {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['mail'] ?? [];

        // 用户信息更改通知
        if (!empty($opt['user_change_notify_disable'])) {
            add_filter('send_password_change_email', '__return_false');
            add_filter('send_email_change_email', '__return_false');
        }

        // 新用户注册通知（发给管理员）
        if (!empty($opt['new_user_notify_admin_disable'])) {
            add_filter('wp_new_user_notification_email_admin', '__return_empty_array');
        }

        // 屏蔽管理员邮箱定期验证
        if (!empty($opt['admin_email_check_disable'])) {
            add_filter('admin_email_check_interval', '__return_false');
        }
    }
}

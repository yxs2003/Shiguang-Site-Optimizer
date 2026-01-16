<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Mail {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['mail'] ?? [];

        // 用户信息更改通知
        // 返回 false 禁止发送
        if (!empty($opt['user_change_notify_disable'])) {
            add_filter('send_password_change_email', '__return_false');
            add_filter('send_email_change_email', '__return_false');
        }

        // 新用户注册通知（发给管理员）
        // 注意：wp_new_user_notification_email_admin 过滤的是邮件内容数组。
        // 返回空数组会导致 wp_mail 发送空参数，虽然不报错但不够优雅。
        // 但这是目前不覆盖 pluggable 函数的最简单方法。
        if (!empty($opt['new_user_notify_admin_disable'])) {
            add_filter('wp_new_user_notification_email_admin', '__return_empty_array');
        }

        // 屏蔽管理员邮箱定期验证
        // admin_email_check_interval 期望返回整数（秒）。
        // 返回 0 或 false (0) 均可禁用检查。
        if (!empty($opt['admin_email_check_disable'])) {
            add_filter('admin_email_check_interval', '__return_false');
        }
    }
}
<?php
/**
 * Plugin Name: Shiguang - Site Optimizer
 * Description: 模块化的站点优化插件（输出精简、功能屏蔽、编辑优化、更新与邮件控制、图片与链接优化、界面特效、代码注入、加速、TDK、Sitemap 等）。
 * Version: 1.3.0
 * Author: FuHua
 * Text Domain: https://www.shiguang.ink/1851
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 基础常量
define( 'SHIGUANG_DIR', plugin_dir_path( __FILE__ ) );
define( 'SHIGUANG_URL', plugin_dir_url( __FILE__ ) );
define( 'SHIGUANG_OPTION_KEY', 'shiguang_options' );

// 引入模块
require_once SHIGUANG_DIR . 'includes/admin.php';
require_once SHIGUANG_DIR . 'includes/functions-output.php';
require_once SHIGUANG_DIR . 'includes/functions-disable.php';
require_once SHIGUANG_DIR . 'includes/functions-ui.php';
require_once SHIGUANG_DIR . 'includes/functions-post.php';
require_once SHIGUANG_DIR . 'includes/functions-update.php';
require_once SHIGUANG_DIR . 'includes/functions-mail.php';
require_once SHIGUANG_DIR . 'includes/functions-image.php';
require_once SHIGUANG_DIR . 'includes/functions-link.php';
require_once SHIGUANG_DIR . 'includes/functions-style.php';
require_once SHIGUANG_DIR . 'includes/functions-code.php';
require_once SHIGUANG_DIR . 'includes/functions-accelerate.php';
require_once SHIGUANG_DIR . 'includes/functions-tdk.php';
require_once SHIGUANG_DIR . 'includes/functions-sitemap.php'; 

// 安装：默认配置
register_activation_hook( __FILE__, function(){
    $defaults = array(
        'intro' => array(
            'show_quick_links' => 1,
        ),
        'output' => array(
            'remove_wp_version' => 1,
            'remove_asset_version' => 1,
            'remove_dns_prefetch' => 0,
            'remove_json_api_link' => 1,
            'remove_adjacent_posts' => 1,
            'remove_head_feed_links' => 1,
            'remove_wp_block_library_css' => 1,
            'remove_dashicons' => 0,
            'remove_rsd' => 1,
            'remove_classic_theme_css' => 1,
            'remove_global_styles' => 1,
            'remove_svg_filters' => 1,
            'disable_robots_tag' => 0,
        ),
        'disable' => array(
            'translations_api' => 0,
            'wp_check_php_version' => 0,
            'wp_check_browser_version' => 0,
            'current_screen' => 0,
        ),
        'ui' => array(
            'login_logo_hide' => 0,
            'frontend_adminbar_hide' => 0,
            'admin_wp_logo_hide' => 0,
            'login_language_selector_hide' => 0,
        ),
        'api' => array(
            'rest_api_disable' => 0,
            'trackbacks_disable' => 1,
            'xmlrpc_disable' => 1,
            'emoji_disable' => 1,
        ),
        'post' => array(
            'revisions_disable' => 1,
            'autosave_disable' => 1,
            'image_big_threshold_disable' => 1,
            'intermediate_image_sizes_disable' => 0,
            'image_size_attributes_disable' => 0,
            'image_scaling_disable' => 1,
            'content_texturize_disable' => 0,
            'auto_embeds_disable' => 1,
            'post_embed_disable' => 1,
            'gutenberg_disable' => 1,
            'widget_block_editor_disable' => 1,
        ),
        'update' => array(
            'core_update_disable' => 0,
            'theme_update_disable' => 0,
            'plugin_update_disable' => 0,
        ),
        'mail' => array(
            'user_change_notify_disable' => 0,
            'new_user_notify_admin_disable' => 0,
            'admin_email_check_disable' => 1,
        ),
        'image' => array(
            'webp_allow' => 1,
            'svg_allow' => 1,
            'uploads_md5_rename' => 1,
            'uploads_timestamp_enable' => 0,
        ),
        'link' => array(
            'append_html_to_page' => 0,
            'remove_category_base' => 1,
        ),
        'style' => array(
            'site_gray' => 0,
            'hover_color_enable' => 1,
            'hover_color_value' => '#3b82f6',
        ),
        'code' => array(
            'head_code' => '',
            'footer_code' => '',
            'custom_css' => '',
        ),
        'accelerate' => array(
            'jsdelivr_to_iocdn' => 1,
            'gravatar_to_iocdn' => 1,
            'gfonts_css_to_iocdn' => 1,
            'gfonts_files_to_iocdn' => 1,
            'gajax_to_iocdn' => 1,
        ),
        'tdk' => array(
            'site_title' => get_bloginfo('name'),
            'meta_description' => get_bloginfo('description'),
            'meta_keywords' => '',
        ),
        // 新增：Sitemap 默认配置
        'sitemap' => array(
            'enable'            => 1,
            'include_posts'     => 1,
            'include_pages'     => 1,
            'include_categories'=> 1,
            'include_tags'      => 0,
            'max_urls'          => 1000,
            'update_freq'       => 'daily', // always, hourly, daily, weekly, monthly, yearly, never
            'ping_search'       => 0,       // 预留：是否ping搜索引擎
        ),
    );

    if ( false === get_option( SHIGUANG_OPTION_KEY ) ) {
        update_option( SHIGUANG_OPTION_KEY, $defaults );
    } else {
        $old = get_option( SHIGUANG_OPTION_KEY );
        update_option( SHIGUANG_OPTION_KEY, wp_parse_args( $old, $defaults ) );
    }

    // 确保重写规则包含 /sitemap.xml
    if ( class_exists('Shiguang_Sitemap') ) {
        Shiguang_Sitemap::activate();
    }
});

// 信息看板数据（仅在插件首页使用）
function shiguang_get_dashboard_info() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    // 文章数量更精确统计
    $pc = wp_count_posts('post');
    $published = intval($pc->publish ?? 0);
    $draft     = intval($pc->draft ?? 0);
    $pending   = intval($pc->pending ?? 0);
    $future    = intval($pc->future ?? 0);
    $private   = intval($pc->private ?? 0);
    $total_posts = $published + $draft + $pending + $future + $private;

    // 插件与用户
    $users = count_users();
    $plugins = get_plugins();
    $active_plugins = (array) get_option('active_plugins', array());

    // 已启用的优化项数量（勾选为 1 即视为启用）
    $opts = get_option( SHIGUANG_OPTION_KEY, array() );
    $enabled_count = 0;
    foreach ( $opts as $group ) {
        if ( is_array($group) ) {
            foreach ( $group as $k => $v ) {
                if ( is_numeric($v) && intval($v) === 1 ) $enabled_count++;
            }
        }
    }

    // PHP 扩展
    $common_exts = array(
        'curl','mbstring','openssl','zip','gd','imagick','exif','intl','redis','memcached','pdo','mysqli','bcmath','soap','xml','xmlwriter','xmlreader'
    );
    $ext_status = array();
    foreach ($common_exts as $e) {
        $ext_status[$e] = extension_loaded($e);
    }

    return array(
        'wp_version'        => get_bloginfo('version'),
        'php_version'       => phpversion(),
        'mysql_version'     => $wpdb->db_version(),
        'total_users'       => intval($users['total_users']),
        'role_counts'       => (array) ($users['avail_roles'] ?? array()),
        'total_plugins'     => count($plugins),
        'active_plugins'    => count($active_plugins),
        'total_posts'       => $total_posts,
        'published_posts'   => $published,
        'draft_posts'       => $draft,
        'pending_posts'     => $pending,
        'future_posts'      => $future,
        'private_posts'     => $private,
        'total_comments'    => intval(wp_count_comments()->total_comments),
        'approved_comments' => intval(wp_count_comments()->approved),
        'pending_comments'  => intval(wp_count_comments()->moderated),
        'enabled_options'   => $enabled_count,
        'php_extensions'    => $ext_status,
    );
}

// 管理后台
if ( is_admin() ) {
    Shiguang_Admin::init();
}

// 注册前台/通用模块
Shiguang_Output::init();
Shiguang_Disable::init();
Shiguang_UI::init();
Shiguang_Post::init();
Shiguang_Update::init();
Shiguang_Mail::init();
Shiguang_Image::init();
Shiguang_Link::init();
Shiguang_Style::init();
Shiguang_Code::init();
Shiguang_Accelerate::init();
Shiguang_TDK::init();
Shiguang_Sitemap::init(); 

add_filter( 'plugin_row_meta', 'shiguang_plugin_add_links', 10, 2 );

function shiguang_plugin_add_links( $links, $file ) {
    if ( strpos( $file, 'shiguang.php' ) !== false ) {
        $new_links = array(
            '<a href="https://www.shiguang.ink/1851" target="_blank">' . __( '访问插件主页', 'shiguang-site-optimizer' ) . '</a>',
        );

        // 将新链接合并到现有链接数组中
        $links = array_merge( $links, $new_links );
    }

    return $links;
}

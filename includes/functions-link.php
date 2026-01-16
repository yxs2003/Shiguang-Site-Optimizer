<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Link {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['link'] ?? [];

        // 页面追加 .html（仅页面）
        if (!empty($opt['append_html_to_page'])) {
            add_action('init', [__CLASS__, 'add_html_rewrite']);
            add_filter('page_link', [__CLASS__, 'page_html_suffix'], 10, 2);
            add_filter('user_trailingslashit', [__CLASS__, 'no_slash_for_html'], 10, 2);
        }

        // 移除分类 category 基础
        if (!empty($opt['remove_category_base'])) {
            add_action('created_category', [__CLASS__, 'flush_rules_on_change']);
            add_action('edited_category', [__CLASS__, 'flush_rules_on_change']);
            add_action('delete_category', [__CLASS__, 'flush_rules_on_change']);
            add_action('init', [__CLASS__, 'remove_category_base_permastruct']);
            add_filter('category_rewrite_rules', [__CLASS__, 'remove_category_base_rewrite']);
            // 兼容旧的 category_link
            add_filter('term_link', [__CLASS__, 'remove_category_base_link'], 10, 3);
        }
    }

    /**
     * --------------------------------------------------
     * 页面 .html 相关
     * --------------------------------------------------
     */

    public static function add_html_rewrite(){
        add_rewrite_tag('%pagename_html%', '(.+).html$');
        // 确保 .html 页面能被正确解析为 page
        add_rewrite_rule('^([^/]+).html$', 'index.php?pagename=$matches[1]', 'top');
        add_rewrite_rule('^(.+?)/([^/]+).html$', 'index.php?pagename=$matches[1]/$matches[2]', 'top');
    }

    public static function page_html_suffix($link, $post_id){
        // 如果是后台或者链接为空，不做处理
        if (is_admin() || empty($link)) return $link;
        return user_trailingslashit( untrailingslashit($link) . '.html' );
    }

    public static function no_slash_for_html($string, $type_of_url){
        if ($type_of_url !== 'single') return $string; // 仅针对单个页面/文章
        if (strpos($string, '.html') !== false) {
            return untrailingslashit($string);
        }
        return $string;
    }

    /**
     * --------------------------------------------------
     * 移除 Category Base 相关
     * --------------------------------------------------
     */

    // 变动时刷新规则（慎用，仅在分类增删改时触发）
    public static function flush_rules_on_change(){
        delete_option('rewrite_rules');
    }

    // 修改固定连接结构
    public static function remove_category_base_permastruct() {
        global $wp_rewrite;
        // 如果没有启用固定链接，不执行
        if ( ! $wp_rewrite->using_permalinks() ) return;

        $wp_rewrite->extra_permastructs['category']['struct'] = '%category%';
    }

    // 重写规则，处理子分类
    public static function remove_category_base_rewrite($rules) {
        $new_rules = array();
        $categories = get_categories(array('hide_empty' => false));
        
        foreach ($categories as $category) {
            $slug = $category->slug;
            // 递归获取父级
            if ($category->parent) {
                $slug = get_category_parents($category->parent, false, '/', true) . $slug;
            }
            
            // 对应规则
            $new_rules['('.$slug.')/?$'] = 'index.php?category_name=$matches[1]';
            $new_rules['('.$slug.')/page/?([0-9]{1,})/?$'] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
            $new_rules['('.$slug.')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
        }
        
        return $new_rules + $rules;
    }

    // 修复输出的链接（防止 wp_list_categories 等函数输出带 /category/ 的链接）
    public static function remove_category_base_link($link, $term, $taxonomy){
        if ($taxonomy !== 'category') return $link;
        
        $category_base = get_option('category_base');
        if (empty($category_base)) $category_base = 'category';

        // 移除 base
        $link = str_replace('/' . $category_base . '/', '/', $link);
        return $link;
    }
}
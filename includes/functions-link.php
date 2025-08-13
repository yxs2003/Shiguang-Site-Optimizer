<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Shiguang_Link {

    public static function init(){
        $o = get_option( SHIGUANG_OPTION_KEY, [] );
        $opt = $o['link'] ?? [];

        // 页面追加 .html（仅页面）
        if (!empty($opt['append_html_to_page'])) {
            add_filter('page_link', [__CLASS__, 'page_html_suffix'], 10, 2);
            add_action('init', [__CLASS__, 'add_html_rewrite']);
            add_filter('post_type_link', [__CLASS__, 'maybe_add_html_to_cpt'], 10, 2);
        }

        // 移除分类 category 基础
        if (!empty($opt['remove_category_base'])) {
            add_action('init', [__CLASS__, 'remove_category_base_init']);
        }
    }

    // 页面 .html
    public static function page_html_suffix($link, $post_id){
        return user_trailingslashit( untrailingslashit($link) . '.html' );
    }

    public static function add_html_rewrite(){
        add_rewrite_tag('%pagename_html%', '(.+).html$');
        add_rewrite_rule('(.+).html$', 'index.php?pagename=$matches[1]', 'top');
    }

    public static function maybe_add_html_to_cpt($permalink, $post){
        if ( $post->post_type !== 'page' ) return $permalink;
        return self::page_html_suffix($permalink, $post->ID);
    }

    // 分类基础移除
    public static function remove_category_base_init(){
        global $wp_rewrite;
        $wp_rewrite->extra_permastructs['category']['struct'] = '%category%';
        add_filter('category_rewrite_rules', function($rules){
            $new_rules = [];
            $categories = get_categories(['hide_empty'=>false]);
            foreach ($categories as $category) {
                $slug = $category->slug;
                if ($category->parent) {
                    $slug = get_category_parents($category->parent, false, '/', true) . $slug;
                }
                $new_rules[$slug.'/?$'] = 'index.php?category_name='.$slug;
                $new_rules[$slug.'/page/([0-9]{1,})/?$'] = 'index.php?category_name='.$slug.'&paged=$matches[1]';
                $new_rules[$slug.'/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?category_name='.$slug.'&feed=$matches[1]';
            }
            return $new_rules + $rules;
        });
    }
}

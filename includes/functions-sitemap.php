<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 网站地图（Sitemap）模块
 * - 动态输出：/sitemap.xml
 * - 一键生成：静态 /sitemap.xml 文件
 */
class Shiguang_Sitemap {

    public static function init() {
        add_filter('query_vars', array(__CLASS__, 'add_query_var'));
        add_action('init', array(__CLASS__, 'add_rewrite'));
        add_action('template_redirect', array(__CLASS__, 'render'));
    }

    public static function activate() {
        self::add_rewrite();
        flush_rewrite_rules(false);
    }

    public static function add_query_var($vars){
        $vars[] = 'shiguang_sitemap';
        return $vars;
    }

    public static function add_rewrite(){
        add_rewrite_rule('^sitemap\.xml$', 'index.php?shiguang_sitemap=1', 'top');
    }

    public static function render(){
        if ( intval(get_query_var('shiguang_sitemap')) !== 1 ) return;

        $opts = get_option( SHIGUANG_OPTION_KEY, array() );
        $cfg  = $opts['sitemap'] ?? array();
        if ( empty($cfg['enable']) ) return;

        $max  = max(1, intval($cfg['max_urls'] ?? 1000));
        $freq = sanitize_text_field($cfg['update_freq'] ?? 'daily');

        $urls = self::collect_urls($cfg, $max);

        // 输出 XML
        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach($urls as $u): ?>
  <url>
    <loc><?php echo esc_url($u['loc']); ?></loc>
    <?php if(!empty($u['lastmod'])): ?><lastmod><?php echo esc_html($u['lastmod']); ?></lastmod><?php endif; ?>
    <changefreq><?php echo esc_html($freq); ?></changefreq>
    <priority><?php echo esc_html($u['priority']); ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
<?php
        exit;
    }

    /**
     * 一键生成静态 sitemap.xml 到站点根目录
     */
    public static function generate_static(){
        $opts = get_option( SHIGUANG_OPTION_KEY, array() );
        $cfg  = $opts['sitemap'] ?? array();
        if ( empty($cfg['enable']) ) $cfg['enable'] = 1; // 即使未勾选，也允许生成静态文件

        $max  = max(1, intval($cfg['max_urls'] ?? 1000));
        $freq = sanitize_text_field($cfg['update_freq'] ?? 'daily');
        $urls = self::collect_urls($cfg, $max);

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach($urls as $u){
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.esc_url($u['loc'])."</loc>\n";
            if(!empty($u['lastmod'])){
                $xml .= '    <lastmod>'.esc_html($u['lastmod'])."</lastmod>\n";
            }
            $xml .= '    <changefreq>'.esc_html($freq)."</changefreq>\n";
            $xml .= '    <priority>'.esc_html($u['priority'])."</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";

        $file = ABSPATH . 'sitemap.xml';
        $ok   = @file_put_contents($file, $xml) !== false;
        return $ok;
    }

    /**
     * 按配置搜集 URL 列表
     */
    private static function collect_urls($cfg, $max){
        $urls = array();

        // 首页
        $urls[] = array(
            'loc' => home_url('/'),
            'lastmod' => date('c'),
            'priority' => '1.0',
        );

        // 文章
        if ( ! empty($cfg['include_posts']) ) {
            $args = array(
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => min(200, $max), // 分段取，避免一次性过大
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'paged'          => 1,
                'no_found_rows'  => true,
                'fields'         => 'ids',
            );
            $added = 0;
            do {
                $q = new WP_Query($args);
                if ( ! $q->have_posts() ) break;
                foreach($q->posts as $pid){
                    if ( count($urls) >= $max ) break 2;
                    $urls[] = array(
                        'loc' => get_permalink($pid),
                        'lastmod' => get_post_modified_time('c', true, $pid),
                        'priority' => '0.9',
                    );
                    $added++;
                }
                $args['paged']++;
            } while ( $added > 0 );
        }

        // 页面
        if ( ! empty($cfg['include_pages']) && count($urls) < $max ) {
            $pages = get_posts(array(
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ));
            foreach($pages as $pid){
                if ( count($urls) >= $max ) break;
                $urls[] = array(
                    'loc' => get_permalink($pid),
                    'lastmod' => get_post_modified_time('c', true, $pid),
                    'priority' => '0.8',
                );
            }
        }

        // 分类
        if ( ! empty($cfg['include_categories']) && count($urls) < $max ) {
            $cats = get_categories(array('hide_empty'=>true));
            foreach($cats as $cat){
                if ( count($urls) >= $max ) break;
                $urls[] = array(
                    'loc' => get_category_link($cat->term_id),
                    'lastmod' => date('c'),
                    'priority' => '0.6',
                );
            }
        }

        // 标签
        if ( ! empty($cfg['include_tags']) && count($urls) < $max ) {
            $tags = get_tags(array('hide_empty'=>true));
            foreach($tags as $tag){
                if ( count($urls) >= $max ) break;
                $urls[] = array(
                    'loc' => get_tag_link($tag->term_id),
                    'lastmod' => date('c'),
                    'priority' => '0.5',
                );
            }
        }

        return $urls;
    }
}

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

        // 防止其他插件的输出干扰 XML 结构（常见错误：XML parsing error）
        if ( ob_get_length() ) {
            ob_end_clean();
        }

        $max  = max(1, intval($cfg['max_urls'] ?? 1000));
        $freq = sanitize_text_field($cfg['update_freq'] ?? 'daily');

        $urls = self::collect_urls($cfg, $max);

        // 设置 Header
        if ( !headers_sent() ) {
            header('Content-Type: application/xml; charset=UTF-8');
            header('X-Robots-Tag: noindex, follow'); // 告诉搜索引擎不要索引 XML 文件本身
        }

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
        // 允许手动生成，即使开关未开启
        
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
        // 使用 @ 抑制权限错误，但实际上应该检查 is_writable
        $ok   = @file_put_contents($file, $xml) !== false;
        return $ok;
    }

    /**
     * 按配置搜集 URL 列表
     */
    private static function collect_urls($cfg, $max){
        $urls = array();

        // 1. 首页
        $urls[] = array(
            'loc' => home_url('/'),
            'lastmod' => date('c'),
            'priority' => '1.0',
        );

        // 2. 文章 (分批获取以节省内存)
        if ( ! empty($cfg['include_posts']) ) {
            $batch_size = 100;
            $paged = 1;
            
            while( count($urls) < $max ) {
                $q = new WP_Query(array(
                    'post_type'      => 'post',
                    'post_status'    => 'publish',
                    'posts_per_page' => $batch_size,
                    'paged'          => $paged,
                    'fields'         => 'ids',
                    'orderby'        => 'modified',
                    'order'          => 'DESC',
                    'no_found_rows'  => true, // 性能优化：不计算总行数
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                ));

                if ( !$q->have_posts() ) break;

                foreach($q->posts as $pid){
                    if ( count($urls) >= $max ) break;
                    $urls[] = array(
                        'loc' => get_permalink($pid),
                        'lastmod' => get_post_modified_time('c', true, $pid),
                        'priority' => '0.8', // 文章权重
                    );
                }
                $paged++;
            }
            wp_reset_postdata();
        }

        // 3. 页面
        if ( ! empty($cfg['include_pages']) && count($urls) < $max ) {
            // 页面通常较少，一次性获取
            $pages = get_posts(array(
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'posts_per_page' => min(100, $max - count($urls)),
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ));
            foreach($pages as $pid){
                $urls[] = array(
                    'loc' => get_permalink($pid),
                    'lastmod' => get_post_modified_time('c', true, $pid),
                    'priority' => '0.6',
                );
            }
        }

        // 4. 分类
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

        // 5. 标签
        if ( ! empty($cfg['include_tags']) && count($urls) < $max ) {
            $tags = get_tags(array('hide_empty'=>true));
            foreach($tags as $tag){
                if ( count($urls) >= $max ) break;
                $urls[] = array(
                    'loc' => get_tag_link($tag->term_id),
                    'lastmod' => date('c'),
                    'priority' => '0.4',
                );
            }
        }

        return $urls;
    }
}
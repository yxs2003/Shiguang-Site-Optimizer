<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin: settings page for Shiguang
 */
class Shiguang_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
        add_action( 'admin_post_shiguang_generate_sitemap', array( __CLASS__, 'handle_generate_sitemap' ) );
    }

    public static function add_menu() {
        add_menu_page(
            __( 'Shiguang 优化设置', 'shiguang' ),
            __( 'Shiguang', 'shiguang' ),
            'manage_options',
            'shiguang-settings',
            array( __CLASS__, 'settings_page' ),
            'dashicons-admin-generic',
            80
        );
    }

    public static function enqueue($hook){
        if ( $hook !== 'toplevel_page_shiguang-settings' ) return;
        // 注意：这里路径要确保正确，根据你的目录结构
        wp_enqueue_style('shiguang-admin', plugin_dir_url(__FILE__) . '../assets/admin.css', array(), '1.3.0');
        wp_enqueue_script('shiguang-admin', plugin_dir_url(__FILE__) . '../assets/admin.js', array('jquery'), '1.3.0', true);
    }

    public static function register_settings(){
        register_setting( 'shiguang_group', SHIGUANG_OPTION_KEY, array( __CLASS__, 'sanitize' ) );
    }

    /**
     * 关键修复：增强的数据清洗
     */
    public static function sanitize( $input ){
        $old = get_option( SHIGUANG_OPTION_KEY, array() );
        $clean = array();

        // 1. 遍历旧数据结构，确保字段存在
        foreach( $old as $section_key => $section_val ) {
            if( !isset($input[$section_key]) ) {
                $input[$section_key] = array();
            }
        }

        // 2. 递归处理字段
        // 定义允许包含 HTML 代码的字段 (代码注入部分)
        $code_fields = array('head_code', 'footer_code', 'custom_css');

        foreach ( $input as $group => $fields ) {
            if ( !is_array($fields) ) continue;
            
            foreach ( $fields as $key => $val ) {
                
                // 处理代码注入字段
                if ( $group === 'code' && in_array($key, $code_fields) ) {
                    // 只有具备 unfiltered_html 权限的用户才能保存原始代码（防止低权限管理员注入恶意脚本）
                    if ( current_user_can( 'unfiltered_html' ) ) {
                        $clean[$group][$key] = $val; // 允许原始内容
                    } else {
                        $clean[$group][$key] = wp_kses_post( $val ); // 过滤危险标签
                    }
                    continue;
                }

                // 处理 TDK 文本字段
                if ( $group === 'tdk' ) {
                     if ( $key === 'meta_description' ) {
                         $clean[$group][$key] = sanitize_textarea_field( $val );
                     } else {
                         $clean[$group][$key] = sanitize_text_field( $val );
                     }
                     continue;
                }

                // 处理样式颜色
                if ( $group === 'style' && $key === 'hover_color_value' ) {
                    $clean[$group][$key] = sanitize_hex_color( $val );
                    continue;
                }

                // 处理 Sitemap 配置
                if ( $group === 'sitemap' ) {
                    if ( $key === 'max_urls' ) {
                        $clean[$group][$key] = max(1, absint($val));
                    } elseif ( $key === 'update_freq' ) {
                        $valid_freqs = array('always','hourly','daily','weekly','monthly','yearly','never');
                        $clean[$group][$key] = in_array($val, $valid_freqs) ? $val : 'daily';
                    } else {
                        // 开关类
                        $clean[$group][$key] = ($val == 1) ? 1 : 0;
                    }
                    continue;
                }

                // 默认处理：数字开关或普通文本
                if ( is_numeric($val) ) {
                    $clean[$group][$key] = ($val == 1) ? 1 : 0; // 强制转为 0 或 1
                } else {
                    $clean[$group][$key] = sanitize_text_field( $val );
                }
            }
        }

        // 合并默认值，防止缺失键
        return wp_parse_args( $clean, $old );
    }

    public static function handle_generate_sitemap(){
        if ( ! current_user_can('manage_options') ) wp_die('Forbidden');
        check_admin_referer('shiguang_generate_sitemap');

        if ( class_exists('Shiguang_Sitemap') ) {
            $ok = Shiguang_Sitemap::generate_static();
            $msg = $ok ? '网站地图已生成：/sitemap.xml' : '生成失败：请检查站点根目录写入权限';
        } else {
            $msg = 'Sitemap 模块不存在，请确认插件文件已完整上传。';
        }
        wp_redirect( add_query_arg( array('page'=>'shiguang-settings','sg_msg'=>rawurlencode($msg)), admin_url('admin.php') ) );
        exit;
    }

    public static function settings_page(){
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __('No') );
        $opts = get_option( SHIGUANG_OPTION_KEY );
        $msg = isset($_GET['sg_msg']) ? sanitize_text_field(wp_unslash($_GET['sg_msg'])) : '';
        ?>
        <div class="wrap shiguang-wrap">
            <h1>Shiguang - 站点优化设置</h1>

            <?php if ($msg): ?>
                <div class="notice notice-info is-dismissible"><p><?php echo esc_html($msg); ?></p></div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'shiguang_group' ); ?>
                <?php // 注意：$opts 应该再次获取以确保是最新，或者直接使用上面获取的变量 ?>
                
                <h2 class="nav-tab-wrapper">
                    <a class="nav-tab nav-tab-active" href="#tab-intro">插件功能介绍</a>
                    <a class="nav-tab" href="#tab-output">输出精简</a>
                    <a class="nav-tab" href="#tab-disable">函数 / 接口禁用</a>
                    <a class="nav-tab" href="#tab-ui">外观与后台</a>
                    <a class="nav-tab" href="#tab-post">文章与编辑</a>
                    <a class="nav-tab" href="#tab-update">升级与更新</a>
                    <a class="nav-tab" href="#tab-mail">邮件相关</a>
                    <a class="nav-tab" href="#tab-image">图片与上传</a>
                    <a class="nav-tab" href="#tab-link">链接与分类</a>
                    <a class="nav-tab" href="#tab-style">界面与交互</a>
                    <a class="nav-tab" href="#tab-code">代码注入</a>
                    <a class="nav-tab" href="#tab-accelerate">加速与镜像</a>
                    <a class="nav-tab" href="#tab-tdk">TDK / SEO</a>
                    <a class="nav-tab" href="#tab-sitemap">网站地图</a>
                </h2>

                <div class="shiguang-content-wrapper">
                    <div class="shiguang-tab" id="tab-intro">
                        <h2>插件功能介绍</h2>
                        <p class="sg-muted">Shiguang-Site Optimizer 通过模块化的方式，帮你关闭不需要的功能、精简前端输出、优化编辑体验、控制更新与邮件、图片与链接规则、界面特效、注入自定义代码、CDN 加速、SEO TDK，以及站点地图（Sitemap）。你可以在上方标签里逐项启用/关闭。</p>

                        <?php
                        // 看板逻辑
                        $board = function(){
                            if( !function_exists('shiguang_get_dashboard_info') ) return;
                            $info = shiguang_get_dashboard_info();
                            $exts = $info['php_extensions'];
                            ?>
                            <div class="sg-grid">
                                <div class="sg-card sg-col-4">
                                    <h3>环境信息</h3>
                                    <div class="sg-kv"><span>WordPress 版本</span><strong><?php echo esc_html($info['wp_version']); ?></strong></div>
                                    <div class="sg-kv"><span>PHP 版本</span><strong><?php echo esc_html($info['php_version']); ?></strong></div>
                                    <div class="sg-kv"><span>MySQL 版本</span><strong><?php echo esc_html($info['mysql_version']); ?></strong></div>
                                </div>

                                <div class="sg-card sg-col-4">
                                    <h3>网站信息</h3>
                                    <div class="sg-kv"><span>启用优化项</span><strong><?php echo intval($info['enabled_options']); ?></strong></div>
                                    <div class="sg-kv"><span>插件（已启用/总数）</span><strong><?php echo intval($info['active_plugins']).' / '.intval($info['total_plugins']); ?></strong></div>
                                    <div class="sg-kv"><span>用户总量</span><strong><?php echo intval($info['total_users']); ?></strong></div>
                                </div>

                                <div class="sg-card sg-col-4">
                                    <h3>文章信息</h3>
                                    <div class="sg-kv"><span>文章总量</span><strong><?php echo intval($info['total_posts']); ?></strong></div>
                                    <div class="sg-kv"><span>已发布</span><strong><?php echo intval($info['published_posts']); ?></strong></div>
                                    <div class="sg-kv"><span>草稿 / 待审 / 计划 / 私密</span>
                                        <strong><?php echo intval($info['draft_posts']).' / '.intval($info['pending_posts']).' / '.intval($info['future_posts']).' / '.intval($info['private_posts']); ?></strong>
                                    </div>
                                </div>

                                <div class="sg-card sg-col-6">
                                    <h3>评论信息</h3>
                                    <div class="sg-kv"><span>评论总量</span><strong><?php echo intval($info['total_comments']); ?></strong></div>
                                    <div class="sg-kv"><span>已通过 / 待审</span><strong><?php echo intval($info['approved_comments']).' / '.intval($info['pending_comments']); ?></strong></div>
                                </div>
                                <div class="sg-card sg-col-6">
                                    <h3>PHP 扩展（常用）</h3>
                                    <div>
                                        <?php foreach($exts as $k=>$ok): ?>
                                            <span class="sg-badge <?php echo $ok?'ok':'no'; ?>">
                                                <?php echo esc_html($k); ?> <?php echo $ok?'✓':'✗'; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                        };
                        $board();
                        ?>

                        <h3>我应该怎么用？</h3>
                        <ol class="ul-disc">
                            <li>从左到右逐个标签查看，按需勾选想启用的优化项；</li>
                            <li>“加速与镜像”可自动把常见资源替换到更快的国内 CDN；</li>
                            <li>“TDK / SEO”可设置站点标题、描述、关键词；</li>
                            <li>“网站地图”开启后，访问 <code><?php echo esc_url( home_url('/sitemap.xml') ); ?></code> 可查看动态地图；也可以一键生成静态 <code>/sitemap.xml</code> 文件。</li>
                        </ol>
                    </div>

                    <div class="shiguang-tab" id="tab-output" style="display:none;">
                        <h2>输出精简</h2>
                        <?php self::checkbox_field('output','remove_wp_version','移除 WordPress 版本号（降低被针对风险）', $opts); ?>
                        <?php self::checkbox_field('output','remove_asset_version','移除前端 CSS/JS 版本参数', $opts); ?>
                        <?php self::checkbox_field('output','remove_dns_prefetch','移除 <code>dns-prefetch</code> 预解析链接', $opts); ?>
                        <?php self::checkbox_field('output','remove_json_api_link','移除头部 JSON API 链接', $opts); ?>
                        <?php self::checkbox_field('output','remove_adjacent_posts','移除文章前后页 <code>rel</code> 链接', $opts); ?>
                        <?php self::checkbox_field('output','remove_head_feed_links','移除头部 Feed 链接', $opts); ?>
                        <?php self::checkbox_field('output','remove_wp_block_library_css','移除前端 Gutenberg 样式（5.0+）', $opts); ?>
                        <?php self::checkbox_field('output','remove_dashicons','移除前台 Dashicons 资源', $opts); ?>
                        <?php self::checkbox_field('output','remove_rsd','移除 RSD（XML-RPC 发现）', $opts); ?>
                        <?php self::checkbox_field('output','remove_classic_theme_css','移除经典主题兼容样式', $opts); ?>
                        <?php self::checkbox_field('output','remove_global_styles','移除 WP 全局样式变量', $opts); ?>
                        <?php self::checkbox_field('output','remove_svg_filters','移除 WP 内置 SVG Filter', $opts); ?>
                        <?php self::checkbox_field('output','disable_robots_tag','禁用 robots 元标签输出', $opts); ?>
                    </div>

                    <div class="shiguang-tab" id="tab-disable" style="display:none;">
                        <h2>函数 / 接口禁用</h2>
                        <?php self::checkbox_field('disable','translations_api','禁用 translations_api（减少对 wp.org 查询）', $opts); ?>
                        <?php self::checkbox_field('disable','wp_check_php_version','禁用 PHP 版本检查（进入设置很慢）', $opts); ?>
                        <?php self::checkbox_field('disable','wp_check_browser_version','禁用浏览器兼容性检查（进入设置很慢）', $opts); ?>
                        <?php self::checkbox_field('disable','current_screen','禁用 current_screen（降低开销）', $opts); ?>
                    </div>

                    <div class="shiguang-tab" id="tab-ui" style="display:none;">
                        <h2>外观与后台</h2>
                        <?php self::checkbox_field('ui','login_logo_hide','关闭后台登录页面 WordPress Logo', $opts); ?>
                        <?php self::checkbox_field('ui','frontend_adminbar_hide','关闭前台顶部工具条', $opts); ?>
                        <?php self::checkbox_field('ui','admin_wp_logo_hide','移除后台左上角 WordPress Logo', $opts); ?>
                        <?php self::checkbox_field('ui','login_language_selector_hide','隐藏登录页语言切换', $opts); ?>
                    </div>

                    <div class="shiguang-tab" id="tab-post" style="display:none;">
                        <h2>文章与编辑</h2>
                        <?php self::checkbox_field('post','revisions_disable','关闭保存修订版本', $opts); ?>
                        <?php self::checkbox_field('post','autosave_disable','关闭文章自动保存', $opts); ?>
                        <?php self::checkbox_field('post','image_big_threshold_disable','关闭图像高度限制', $opts); ?>
                        <?php self::checkbox_field('post','intermediate_image_sizes_disable','禁止生成多种图像尺寸', $opts); ?>
                        <?php self::checkbox_field('post','image_size_attributes_disable','禁止图片插入附带 width/height/class', $opts); ?>
                        <?php self::checkbox_field('post','image_scaling_disable','禁止大图 -scaled 缩放', $opts); ?>
                        <?php self::checkbox_field('post','content_texturize_disable','关闭字符转码（中英文标点转换）', $opts); ?>
                        <?php self::checkbox_field('post','auto_embeds_disable','禁止 Auto Embeds', $opts); ?>
                        <?php self::checkbox_field('post','post_embed_disable','禁止文章 oEmbed', $opts); ?>
                        <?php self::checkbox_field('post','gutenberg_disable','禁用 Gutenberg 编辑器（经典编辑器）', $opts); ?>
                        <?php self::checkbox_field('post','widget_block_editor_disable','禁用区块小工具编辑器', $opts); ?>
                    </div>

                    <div class="shiguang-tab" id="tab-update" style="display:none;">
                        <h2>升级与更新</h2>
                        <?php self::checkbox_field('update','core_update_disable','关闭 WordPress 核心更新检查', $opts); ?>
                        <?php self::checkbox_field('update','theme_update_disable','关闭主题更新检查', $opts); ?>
                        <?php self::checkbox_field('update','plugin_update_disable','关闭插件更新检查', $opts); ?>
                    </div>

                    <div class="shiguang-tab" id="tab-mail" style="display:none;">
                        <h2>邮件相关</h2>
                        <?php self::checkbox_field('mail','user_change_notify_disable','关闭用户信息变更通知邮件', $opts); ?>
                        <?php self::checkbox_field('mail','new_user_notify_admin_disable','关闭新用户注册后台通知', $opts); ?>
                        <?php self::checkbox_field('mail','admin_email_check_disable','屏蔽定期邮箱验证', $opts); ?>
                    </div>

                    <div class="shiguang-tab" id="tab-image" style="display:none;">
                        <h2>图片与上传</h2>
                        <?php self::checkbox_field('image','webp_allow','允许上传 WebP', $opts); ?>
                        <?php self::checkbox_field('image','svg_allow','允许上传 SVG', $opts); ?>
                        <?php self::checkbox_field('image','uploads_md5_rename','上传图片 MD5 重命名', $opts); ?>
                        <?php self::checkbox_field('image','uploads_timestamp_enable','上传图片追加时间戳（可选）', $opts); ?>
                    </div>

                    <div class="shiguang-tab" id="tab-link" style="display:none;">
                        <h2>链接与分类</h2>
                        <?php self::checkbox_field('link','append_html_to_page','为页面添加 .html 后缀', $opts); ?>
                        <?php self::checkbox_field('link','remove_category_base','移除 category 前缀', $opts); ?>
                    </div>

                    <div class="shiguang-tab" id="tab-style" style="display:none;">
                        <h2>界面与交互</h2>
                        <?php self::checkbox_field('style','site_gray','全站变灰（特殊日期）', $opts); ?>
                        <?php self::checkbox_field('style','hover_color_enable','启用鼠标悬停变色', $opts); ?>
                        <p>
                            <label>悬停颜色值：
                                <input type="text" name="<?php echo SHIGUANG_OPTION_KEY; ?>[style][hover_color_value]" value="<?php echo esc_attr($opts['style']['hover_color_value'] ?? '#3b82f6'); ?>" class="regular-text" placeholder="#3b82f6">
                            </label>
                        </p>
                    </div>

                    <div class="shiguang-tab" id="tab-code" style="display:none;">
                        <h2>代码注入</h2>
                        <p><label>页头代码（自动输出到 <code>&lt;head&gt;</code>）：<br>
                            <textarea name="<?php echo SHIGUANG_OPTION_KEY; ?>[code][head_code]" class="large-text" rows="6"><?php echo esc_textarea($opts['code']['head_code'] ?? ''); ?></textarea>
                        </label></p>
                        <p><label>页脚代码（自动输出到 <code>&lt;/body&gt;</code> 前）：<br>
                            <textarea name="<?php echo SHIGUANG_OPTION_KEY; ?>[code][footer_code]" class="large-text" rows="6"><?php echo esc_textarea($opts['code']['footer_code'] ?? ''); ?></textarea>
                        </label></p>
                        <p><label>自定义 CSS（自动插入样式表）：<br>
                            <textarea name="<?php echo SHIGUANG_OPTION_KEY; ?>[code][custom_css]" class="large-text" rows="6"><?php echo esc_textarea($opts['code']['custom_css'] ?? ''); ?></textarea>
                        </label></p>
                    </div>

                    <div class="shiguang-tab" id="tab-accelerate" style="display:none;">
                        <h2>加速与镜像</h2>
                        <?php self::checkbox_field('accelerate','jsdelivr_to_iocdn','将 jsDelivr 替换为 cdn.iocdn.cc', $opts); ?>
                        <?php self::checkbox_field('accelerate','gravatar_to_iocdn','将 Gravatar 替换为 cdn.iocdn.cc', $opts); ?>
                        <?php self::checkbox_field('accelerate','gfonts_css_to_iocdn','将 Google Fonts CSS 使用 cdn.iocdn.cc', $opts); ?>
                        <?php self::checkbox_field('accelerate','gfonts_files_to_iocdn','将 Google Fonts 静态文件使用 cdn.iocdn.cc', $opts); ?>
                        <?php self::checkbox_field('accelerate','gajax_to_iocdn','将 Google Ajax 使用 cdn.iocdn.cc', $opts); ?>
                    </div>

                    <div class="shiguang-tab" id="tab-tdk" style="display:none;">
                        <h2>TDK / SEO</h2>
                        <table class="form-table">
                            <tr>
                                <th><label for="sg_tdk_title">站点标题</label></th>
                                <td><input type="text" id="sg_tdk_title" name="<?php echo SHIGUANG_OPTION_KEY; ?>[tdk][site_title]" value="<?php echo esc_attr($opts['tdk']['site_title'] ?? ''); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="sg_tdk_desc">站点描述</label></th>
                                <td><textarea id="sg_tdk_desc" name="<?php echo SHIGUANG_OPTION_KEY; ?>[tdk][meta_description]" class="large-text" rows="4"><?php echo esc_textarea($opts['tdk']['meta_description'] ?? ''); ?></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="sg_tdk_keys">关键词（逗号分隔）</label></th>
                                <td><input type="text" id="sg_tdk_keys" name="<?php echo SHIGUANG_OPTION_KEY; ?>[tdk][meta_keywords]" value="<?php echo esc_attr($opts['tdk']['meta_keywords'] ?? ''); ?>" class="regular-text"></td>
                            </tr>
                        </table>
                    </div>

                    <div class="shiguang-tab" id="tab-sitemap" style="display:none;">
                        <h2>网站地图（Sitemap）</h2>
                        <?php self::checkbox_field('sitemap','enable','启用 Sitemap（动态：/sitemap.xml）', $opts); ?>
                        <fieldset style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;margin:10px 0;">
                            <legend><strong>包含范围</strong></legend>
                            <?php self::checkbox_field('sitemap','include_posts','包含文章', $opts); ?>
                            <?php self::checkbox_field('sitemap','include_pages','包含页面', $opts); ?>
                            <?php self::checkbox_field('sitemap','include_categories','包含分类', $opts); ?>
                            <?php self::checkbox_field('sitemap','include_tags','包含标签', $opts); ?>
                        </fieldset>
                        <p>
                            <label>最大 URL 数（过大可能耗时）：
                                <input type="number" min="1" step="1" name="<?php echo SHIGUANG_OPTION_KEY; ?>[sitemap][max_urls]" value="<?php echo esc_attr($opts['sitemap']['max_urls'] ?? 1000); ?>" class="small-text">
                            </label>
                            &nbsp;&nbsp;
                            <label>更新频率：
                                <select name="<?php echo SHIGUANG_OPTION_KEY; ?>[sitemap][update_freq]">
                                    <?php
                                    $freqs = array('always','hourly','daily','weekly','monthly','yearly','never');
                                    $cur = $opts['sitemap']['update_freq'] ?? 'daily';
                                    foreach($freqs as $f){
                                        echo '<option value="'.esc_attr($f).'" '.selected($cur,$f,false).'>'.esc_html($f).'</option>';
                                    }
                                    ?>
                                </select>
                            </label>
                        </p>

                        <p>
                            <a class="button sg-link" href="<?php echo esc_url( home_url('/sitemap.xml') ); ?>" target="_blank">查看动态 Sitemap</a>
                            <?php
                            $static = ABSPATH . 'sitemap.xml';
                            if ( file_exists($static) ) {
                                $size = size_format(filesize($static));
                                $time = date_i18n('Y-m-d H:i:s', filemtime($static));
                                echo '<span class="sg-muted">已存在静态文件（'.$size.'，更新于 '.$time.'）</span>';
                            }
                            ?>
                        </p>

                        <p>
                            <?php
                            $generate_sitemap_url = wp_nonce_url(
                                admin_url('admin-post.php?action=shiguang_generate_sitemap'),
                                'shiguang_generate_sitemap'
                            );
                            ?>
                            <a href="<?php echo esc_url($generate_sitemap_url); ?>" class="button button-primary">一键生成静态 sitemap.xml</a>
                            <span class="sg-muted">（写入站点根目录，若失败请检查写权限）</span>
                        </p>
                    </div>
                </div> <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private static function checkbox_field($group, $key, $label, $opts){
        $val = isset($opts[$group][$key]) ? $opts[$group][$key] : 0;
        $name = SHIGUANG_OPTION_KEY . "[$group][$key]";
        echo '<p><label><input type="hidden" name="'.esc_attr($name).'" value="0">';
        echo '<input type="checkbox" name="'.esc_attr($name).'" value="1" '.checked(1,$val,false).' /> ';
        echo wp_kses_post($label).'</label></p>';
    }
}
# **Shiguang-Site Optimizer 模块化的WordPress 优化插件**

## **1.插件简介**

Shiguang - Site Optimizer 是一款 **模块化的 WordPress 优化插件**，旨在帮助站长在无需复杂代码的情况下快速完成网站的性能优化、界面美化、SEO 设置、安全增强等功能。
插件采用 **模块化设计**，所有功能都可以在后台开启或关闭，方便用户根据自己的网站需求进行个性化配置。

## **主要特点：**

* **一键优化**：勾选即可启用，不懂代码的小白也能轻松使用。
* **模块化设计**：不同功能分成独立文件，互不干扰，方便扩展与维护。
* **性能与安全兼顾**：既优化网站速度，又增加安全性。
* **前后端优化**：从前台加载到后台体验，全方位提升。

---

**👋作者说：市面上这种优化插件非常多，我就只能算重复造轮子，只是这个轮子是我喜欢的样子罢了，感谢前辈们的优化思路和代码。**

---

## **插件截图：**

![介绍页](https://s2.loli.net/2025/08/13/iF3Wx9LyNmSM2pR.jpg)
![功能页](https://s2.loli.net/2025/08/13/4BNTtLaMxmowrVH.jpg)


## **2. 插件文件目录结构**

```
shiguang/
│── shiguang.php                  # 插件主文件（注册模块、常量、默认配置、激活逻辑）
│
├── includes/
│   ├── admin.php                  # 插件后台界面逻辑（功能设置页、信息看板、推送日志等）
│   ├── functions-output.php       # 输出优化（去除 WP 版本号、精简 <head>）
│   ├── functions-disable.php      # 屏蔽功能（关闭 REST API、XML-RPC、Trackbacks 等）
│   ├── functions-ui.php           # UI 优化（隐藏登录页语言选择、隐藏工具栏等）
│   ├── functions-post.php         # 文章与编辑优化（禁用自动保存、禁用修订版本等）
│   ├── functions-update.php       # 更新控制（禁用主题/插件/核心更新）
│   ├── functions-mail.php         # 邮件通知控制（关闭某些系统邮件）
│   ├── functions-image.php        # 图片优化（WebP 支持、上传重命名、添加时间戳）
│   ├── functions-link.php         # 链接优化（去除分类目录前缀、页面后缀 .html）
│   ├── functions-style.php        # 界面特效（全站置灰、鼠标悬停变色）
│   ├── functions-code.php         # 代码注入（head/footer 自定义代码、自定义 CSS）
│   ├── functions-accelerate.php   # 加速服务（替换 CDN 链接、Google Fonts 本地化）
│   ├── functions-tdk.php          # SEO TDK 设置（网站标题、描述、关键词）
│   ├── functions-sitemap.php      # 网站地图（Sitemap 生成与更新）
│
└── assets/
    ├── css/                        # 后台样式文件
    └── js/                         # 后台脚本文件
```

---

## **3. 文件作用说明**

| 文件                           | 作用                                                       |
| ---------------------------- | -------------------------------------------------------- |
| **shiguang.php**             | 插件入口文件，定义常量、加载模块、设置默认选项、注册激活逻辑。                          |
| **includes/admin.php**       | 后台管理页面，包含功能开关设置、信息看板（WordPress 版本、PHP 版本、插件数等）、推送日志等。    |
| **functions-output.php**     | 精简网站 HTML 输出，移除多余的 meta 信息和 WordPress 版本号等。              |
| **functions-disable.php**    | 禁用不必要的 WordPress 功能（REST API、XML-RPC、Trackbacks）。        |
| **functions-ui.php**         | 界面优化（隐藏登录页语言选择、隐藏前台管理工具栏等）。                              |
| **functions-post.php**       | 优化文章功能（禁用自动保存、修订版本、图片缩放等）。                               |
| **functions-update.php**     | 控制 WordPress 自动更新（核心、主题、插件）。                             |
| **functions-mail.php**       | 控制 WordPress 系统邮件发送（禁用新用户/管理员通知等）。                       |
| **functions-image.php**      | 图片优化（支持 WebP、SVG，上传文件 MD5 重命名、时间戳）。                      |
| **functions-link.php**       | 优化网站链接结构（去除分类目录前缀、页面 URL 添加后缀）。                          |
| **functions-style.php**      | 添加全站灰色、悬停高亮颜色等视觉效果。                                      |
| **functions-code.php**       | 支持添加自定义 CSS、head/footer 脚本。                              |
| **functions-accelerate.php** | 替换静态资源 CDN（jsDelivr → iocdn），加速 Google Fonts、Gravatar 等。 |
| **functions-tdk.php**        | SEO 相关，设置网站标题（Title）、描述（Description）、关键词（Keywords）。      |
| **functions-sitemap.php**    | 网站地图生成功能（支持文章、页面、分类、标签等，自动更新）。                           |

---

## **4. 插件主要功能**

### **① 信息看板**

* **展示网站关键信息**：WordPress 版本、PHP 版本、MySQL 版本、用户数、插件数量、文章数量、评论统计等。
* **PHP 扩展检测**：检查常用 PHP 扩展（如 cURL、mbstring、gd 等）是否开启。
* **优化项统计**：显示已启用的优化功能数量。

### **② 输出优化**

* 移除 WordPress 版本号（防止被扫描漏洞）。
* 移除多余的 meta 链接（例如 RSD、wlwmanifest）。
* 禁用 DNS 预获取，减少无用请求。
* 禁用 Block Library 样式等多余 CSS。

### **③ 功能屏蔽**

* 禁用 REST API（防止未授权数据泄露）。
* 禁用 XML-RPC（阻止暴力破解）。
* 禁用 Trackbacks/Pingbacks。

### **④ 界面优化**

* 隐藏后台 WordPress Logo。
* 隐藏登录页语言切换。
* 隐藏前台工具栏。

### **⑤ 文章与编辑优化**

* 禁用修订版本（减少数据库占用）。
* 禁用自动保存（提升写作体验）。
* 禁用大图缩放（保留原图）。

### **⑥ 更新控制**

* 禁用 WordPress 核心更新。
* 禁用主题与插件更新提示。

### **⑦ 邮件优化**

* 禁用新用户注册通知邮件。
* 禁用管理员邮箱验证邮件。

### **⑧ 图片优化**

* 允许上传 WebP 与 SVG。
* 上传文件自动 MD5 重命名（防止文件名冲突）。
* 可选添加时间戳（方便强制刷新缓存）。

### **⑨ 链接优化**

* 去除分类目录前缀（如 `/category/`）。
* 页面 URL 自动添加 `.html` 后缀。

### **⑩ 界面特效**

* 全站灰色模式（纪念日可用）。
* 鼠标悬停高亮颜色设置。

### **⑪ 代码注入**

* 自定义 `head` 区域代码（如统计代码）。
* 自定义 `footer` 代码。
* 自定义全站 CSS 样式。

### **⑫ 加速功能**

* jsDelivr 资源替换为国内 CDN。
* Google Fonts 本地化。
* Gravatar 头像国内加速。

### **⑬ SEO 设置**

* 自定义站点标题（Title）。
* 自定义站点描述（Description）。
* 自定义关键词（Keywords）。

### **⑭ 网站地图**

* 自动生成 `sitemap.xml`。
* 支持文章、页面、分类、标签。
* 支持自动更新。

---

## 🍃安装说明 

本插件为WordPress专用，不支持其他CMS

**1.下载压缩包**
    
**2.上传压缩包**到网站的
```
/你的网站根目录/wp-content/plugins
```
路径下，然后解压缩即可，解压缩完毕记得检查有没有文件夹套文件夹，否则有可能会报错。

 **其他方式: 登录 网站 后台，在插件中直接上传ZIP压缩包即可。**

## 👋特别鸣谢

cdn.iocdn.cc加速服务（包括jsdelivr/Gravater/谷歌资源库加速）。若有需要您可以在代码中自行更换加速商或者自建文件加速。

所有代码均由ChatGPT完成

部分优化选项参考WPJAM和WPOPT插件

## 🌏更新日志


```
v1.0 实现常见优化功能，如果感觉v1.3部分功能用不上，可以使用此版本

v1.3 整合所有常见优化功能+网站信息统计
```


## 🔗贡献与反馈

如果您在使用过程中发现任何翻译错误或有更好的译法建议，欢迎提交 Pull Request 或直接创建 Issue。您的贡献将帮助我们持续改进这个插件，让它变得更好。


## 📕许可证

本项目遵循 **[Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International](https://creativecommons.org/licenses/by-nc-sa/4.0/)** (CC BY-NC-SA 4.0) 许可证。

* **您可以** 自由地使用、修改和分发本优化插件。
* **但您必须** 遵循以下条款：
    * **署名**: 必须注明本项目的原始来源。
    * **非商业性使用**: 禁止将本插件用于任何商业用途。
    * **相同方式共享**: 如果您基于此文件进行修改和分发，必须沿用此许可证。


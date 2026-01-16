jQuery(document).ready(function($){
    // Tab switching logic
    $('.nav-tab').on('click', function(e){
        e.preventDefault();
        
        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');
        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');
        
        // Hide all tab content
        $('.shiguang-tab').hide();
        
        // Show target content
        var id = $(this).attr('href');
        $(id).fadeIn(200); // 增加一点淡入效果
    });

    // 处理 URL 哈希，使得刷新页面后保持在当前 Tab
    var hash = window.location.hash;
    if (hash && $(hash).length) {
        $('.nav-tab[href="'+hash+'"]').click();
    }
});

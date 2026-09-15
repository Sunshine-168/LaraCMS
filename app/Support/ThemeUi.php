<?php

namespace App\Support;

use App\Models\Cms\Setting;

/** 主题里搜索、登录等固定短句，按内容语言切换 */
class ThemeUi
{
    /** @return array<string, array{label: string, zh_cn: string, en: string}> */
    public static function catalog(): array
    {
        return [
            'search' => ['label' => '搜索', 'zh_cn' => '搜索', 'en' => 'Search'],
            'login' => ['label' => '登录', 'zh_cn' => '登录', 'en' => 'Log in'],
            'register' => ['label' => '注册', 'zh_cn' => '注册', 'en' => 'Register'],
            'member' => ['label' => '会员', 'zh_cn' => '会员', 'en' => 'Member'],
            'logout' => ['label' => '退出', 'zh_cn' => '退出', 'en' => 'Log out'],
            'home' => ['label' => '首页', 'zh_cn' => '首页', 'en' => 'Home'],
            'friend_links' => ['label' => '友链', 'zh_cn' => '友情链接', 'en' => 'Friend links'],
            'sitemap' => ['label' => '站点地图', 'zh_cn' => '站点地图', 'en' => 'Sitemap'],
            'sitemap_hint' => ['label' => '站点地图说明', 'zh_cn' => '栏目、文章和标签的公开入口。搜索引擎请抓 /sitemap.xml。', 'en' => 'Public sections, articles, and tags. Search engines should use /sitemap.xml.'],
            'sitemap_cats' => ['label' => '地图栏目', 'zh_cn' => '栏目', 'en' => 'Sections'],
            'sitemap_posts' => ['label' => '地图文章', 'zh_cn' => '文章', 'en' => 'Articles'],
            'sitemap_tags' => ['label' => '地图标签', 'zh_cn' => '标签', 'en' => 'Tags'],
            'sitemap_extra' => ['label' => '地图其他', 'zh_cn' => '其他页面', 'en' => 'Other pages'],
            'keywords' => ['label' => '关键词', 'zh_cn' => '关键词', 'en' => 'Keywords'],
            'all_models' => ['label' => '全部模型', 'zh_cn' => '全部模型', 'en' => 'All models'],
            'all_categories' => ['label' => '全部栏目', 'zh_cn' => '全部栏目', 'en' => 'All categories'],
            'no_results' => ['label' => '无结果', 'zh_cn' => '没有结果', 'en' => 'No results'],
            'views' => ['label' => '浏览', 'zh_cn' => '浏览', 'en' => 'Views'],
            'prev' => ['label' => '上一篇', 'zh_cn' => '上一篇', 'en' => 'Previous'],
            'next' => ['label' => '下一篇', 'zh_cn' => '下一篇', 'en' => 'Next'],
            'prev_page' => ['label' => '上一页', 'zh_cn' => '上一页', 'en' => 'Previous'],
            'next_page' => ['label' => '下一页', 'zh_cn' => '下一页', 'en' => 'Next'],
            'not_found' => ['label' => '404', 'zh_cn' => '页面不存在', 'en' => 'Page not found'],
            'not_found_hint' => ['label' => '404说明', 'zh_cn' => '链接可能写错了，或内容已下线。', 'en' => 'This link may be wrong, or the content is gone.'],
            'back_home' => ['label' => '回首页', 'zh_cn' => '回首页', 'en' => 'Back home'],
            'comments' => ['label' => '评论', 'zh_cn' => '评论', 'en' => 'Comments'],
            'comments_empty' => ['label' => '无评论', 'zh_cn' => '暂无评论，来抢沙发吧。', 'en' => 'No comments yet.'],
            'comment_name' => ['label' => '昵称', 'zh_cn' => '昵称', 'en' => 'Name'],
            'comment_email' => ['label' => '评论邮箱', 'zh_cn' => '邮箱（可选）', 'en' => 'Email (optional)'],
            'comment_body' => ['label' => '评论内容', 'zh_cn' => '评论内容', 'en' => 'Comment'],
            'comment_submit' => ['label' => '提交评论', 'zh_cn' => '提交评论', 'en' => 'Post comment'],
            'comment_as_member' => ['label' => '会员评论', 'zh_cn' => '以会员身份评论：:name', 'en' => 'Commenting as :name'],
            'comment_login' => ['label' => '评论需登录', 'zh_cn' => '请 :link 后发表评论。', 'en' => 'Please :link to comment.'],
            'captcha' => ['label' => '验证码', 'zh_cn' => '验证码', 'en' => 'Captcha'],
            'favorite' => ['label' => '收藏', 'zh_cn' => '收藏本文', 'en' => 'Save'],
            'unfavorite' => ['label' => '取消收藏', 'zh_cn' => '取消收藏', 'en' => 'Saved'],
            'favorite_login' => ['label' => '收藏需登录', 'zh_cn' => ':link 后可收藏本文', 'en' => ':link to save this'],
            'prev_chapter' => ['label' => '上一话', 'zh_cn' => '上一话', 'en' => 'Previous chapter'],
            'next_chapter' => ['label' => '下一话', 'zh_cn' => '下一话', 'en' => 'Next chapter'],
            'back_comics' => ['label' => '返回漫画库', 'zh_cn' => '返回漫画库', 'en' => 'Back to comics'],
            'member_login' => ['label' => '会员登录', 'zh_cn' => '会员登录', 'en' => 'Member login'],
            'member_register' => ['label' => '会员注册', 'zh_cn' => '会员注册', 'en' => 'Register'],
            'member_center' => ['label' => '会员中心', 'zh_cn' => '会员中心', 'en' => 'Member area'],
            'email' => ['label' => '邮箱', 'zh_cn' => '邮箱', 'en' => 'Email'],
            'password' => ['label' => '密码', 'zh_cn' => '密码', 'en' => 'Password'],
            'password_confirm' => ['label' => '确认密码', 'zh_cn' => '确认密码', 'en' => 'Confirm password'],
            'remember' => ['label' => '记住我', 'zh_cn' => '记住我', 'en' => 'Remember me'],
            'forgot_password' => ['label' => '忘记密码', 'zh_cn' => '忘记密码', 'en' => 'Forgot password'],
            'forgot_title' => ['label' => '找回密码', 'zh_cn' => '找回密码', 'en' => 'Reset password'],
            'forgot_send' => ['label' => '发重置信', 'zh_cn' => '生成重置链接', 'en' => 'Send reset link'],
            'register_account' => ['label' => '注册账号', 'zh_cn' => '注册账号', 'en' => 'Create account'],
            'have_account' => ['label' => '已有账号', 'zh_cn' => '已有账号？登录', 'en' => 'Already have an account? Log in'],
            'back_login' => ['label' => '返回登录', 'zh_cn' => '返回登录', 'en' => 'Back to sign in'],
            'nickname' => ['label' => '昵称', 'zh_cn' => '昵称', 'en' => 'Display name'],
            'reset_password' => ['label' => '设置新密码', 'zh_cn' => '设置新密码', 'en' => 'Set a new password'],
            'new_password' => ['label' => '新密码', 'zh_cn' => '新密码', 'en' => 'New password'],
            'save' => ['label' => '保存', 'zh_cn' => '保存', 'en' => 'Save'],
            'hello' => ['label' => '问候', 'zh_cn' => '你好，:name', 'en' => 'Hello, :name'],
            'profile' => ['label' => '资料', 'zh_cn' => '资料', 'en' => 'Profile'],
            'save_profile' => ['label' => '保存资料', 'zh_cn' => '保存资料', 'en' => 'Save profile'],
            'change_password' => ['label' => '修改密码', 'zh_cn' => '修改密码', 'en' => 'Change password'],
            'current_password' => ['label' => '当前密码', 'zh_cn' => '当前密码', 'en' => 'Current password'],
            'save_password' => ['label' => '保存密码', 'zh_cn' => '保存密码', 'en' => 'Save password'],
            'my_comments' => ['label' => '我的评论', 'zh_cn' => '我的评论', 'en' => 'My comments'],
            'my_favorites' => ['label' => '我的收藏', 'zh_cn' => '我的收藏', 'en' => 'Saved'],
            'my_posts' => ['label' => '我的投稿', 'zh_cn' => '我的投稿', 'en' => 'My submissions'],
            'member_content' => ['label' => '会员内容', 'zh_cn' => '会员内容', 'en' => 'Member content'],
            'avatar' => ['label' => '头像', 'zh_cn' => '头像 URL', 'en' => 'Avatar URL'],
            'phone' => ['label' => '手机', 'zh_cn' => '手机', 'en' => 'Phone'],
            'bio' => ['label' => '简介', 'zh_cn' => '简介', 'en' => 'Bio'],
            'group' => ['label' => '分组', 'zh_cn' => '分组', 'en' => 'Group'],
            'more' => ['label' => '了解更多', 'zh_cn' => '了解更多', 'en' => 'Learn more'],
            'details' => ['label' => '详情', 'zh_cn' => '查看详情', 'en' => 'View details'],
            'view' => ['label' => '查看', 'zh_cn' => '查看', 'en' => 'View'],
            'edit' => ['label' => '编辑', 'zh_cn' => '编辑', 'en' => 'Edit'],
            'all' => ['label' => '全部', 'zh_cn' => '全部', 'en' => 'All'],
            'download' => ['label' => '下载', 'zh_cn' => '下载', 'en' => 'Download'],
            'download_now' => ['label' => '立即下载', 'zh_cn' => '立即下载', 'en' => 'Download now'],
            'download_source' => ['label' => '下载源码', 'zh_cn' => '下载源码', 'en' => 'Download source'],
            'download_pack' => ['label' => '下载源码包', 'zh_cn' => '下载源码包', 'en' => 'Download package'],
            'demo' => ['label' => '演示', 'zh_cn' => '演示', 'en' => 'Demo'],
            'demo_online' => ['label' => '在线演示', 'zh_cn' => '在线演示', 'en' => 'Live demo'],
            'free' => ['label' => '免费', 'zh_cn' => '免费', 'en' => 'Free'],
            'clicks' => ['label' => '点击', 'zh_cn' => '点击', 'en' => 'Clicks'],
            'sort' => ['label' => '排序', 'zh_cn' => '排序', 'en' => 'Sort'],
            'attachments' => ['label' => '附件', 'zh_cn' => '附件', 'en' => 'Files'],
            'platform' => ['label' => '平台', 'zh_cn' => '平台', 'en' => 'Platform'],
            'version' => ['label' => '版本', 'zh_cn' => '版本', 'en' => 'Version'],
            'size' => ['label' => '大小', 'zh_cn' => '大小', 'en' => 'Size'],
            'license' => ['label' => '授权', 'zh_cn' => '授权', 'en' => 'License'],
            'open_source' => ['label' => '打开源', 'zh_cn' => '打开源', 'en' => 'Open source'],
            'open_signal' => ['label' => '打开信号', 'zh_cn' => '打开信号/资讯源', 'en' => 'Open feed'],
            'enter_match' => ['label' => '进入赛事', 'zh_cn' => '进入赛事', 'en' => 'Open match'],
            'send_message' => ['label' => '发送留言', 'zh_cn' => '发送留言', 'en' => 'Send'],
            'submit_form' => ['label' => '提交', 'zh_cn' => '提交', 'en' => 'Submit'],
            'map' => ['label' => '地图', 'zh_cn' => '地图', 'en' => 'Map'],
            'company_address' => ['label' => '公司地址', 'zh_cn' => '公司地址', 'en' => 'Address'],
            'about_us' => ['label' => '公司简介', 'zh_cn' => '公司简介', 'en' => 'About'],
            'news' => ['label' => '新闻动态', 'zh_cn' => '新闻动态', 'en' => 'News'],
            'products' => ['label' => '产品中心', 'zh_cn' => '产品中心', 'en' => 'Products'],
            'contact' => ['label' => '联系我们', 'zh_cn' => '联系我们', 'en' => 'Contact'],
            'latest_news' => ['label' => '最新新闻', 'zh_cn' => '最新新闻', 'en' => 'Latest news'],
            'hot' => ['label' => '热门推荐', 'zh_cn' => '热门推荐', 'en' => 'Featured'],
            'all_cats' => ['label' => '全部分类', 'zh_cn' => '全部分类', 'en' => 'All categories'],
            'chapters' => ['label' => '章节列表', 'zh_cn' => '章节列表', 'en' => 'Chapters'],
            'download_hero' => ['label' => '下载首页标题', 'zh_cn' => '软件下载中心', 'en' => 'Downloads'],
            'download_hero_sub' => ['label' => '下载首页说明', 'zh_cn' => '按平台筛选，一键下载。', 'en' => 'Filter by platform and download.'],
            'nav_hero' => ['label' => '导航首页标题', 'zh_cn' => '网址导航', 'en' => 'Directory'],
            'nav_hero_sub' => ['label' => '导航首页说明', 'zh_cn' => '精选常用站点，点击直达。', 'en' => 'Hand-picked sites, one click away.'],
            'source_hero' => ['label' => '源码首页标题', 'zh_cn' => '开源 / 商业源码', 'en' => 'Source code'],
            'source_hero_sub' => ['label' => '源码首页说明', 'zh_cn' => '演示、授权、打包下载一站搞定。', 'en' => 'Demos, licenses, and packages.'],
            'manga_hero' => ['label' => '漫画首页标题', 'zh_cn' => '漫画库', 'en' => 'Comics'],
            'manga_hero_sub' => ['label' => '漫画首页说明', 'zh_cn' => '筛选地区 / 状态', 'en' => 'Filter by region or status'],
            'sports_hero' => ['label' => '体育首页标题', 'zh_cn' => '体育信号 / 资讯聚合', 'en' => 'Sports feeds'],
            'blog_hero_sub' => ['label' => '博客首页说明', 'zh_cn' => '简单博客主题 · 文章来自 article 模型', 'en' => 'A simple blog of published articles.'],
            'preview_bar' => ['label' => '预览条', 'zh_cn' => '预览稿 · 未公开 · 请勿分享给搜索引擎', 'en' => 'Preview · not public · do not share with search engines'],
            'demo_login_hint' => ['label' => '演示账号', 'zh_cn' => '演示账号见 README', 'en' => 'Demo accounts are listed in the README'],
            'all_posts' => ['label' => '全部投稿', 'zh_cn' => '全部投稿', 'en' => 'All submissions'],
            'write_post' => ['label' => '写新稿', 'zh_cn' => '写新稿', 'en' => 'New post'],
            'no_posts' => ['label' => '无投稿', 'zh_cn' => '还没有投稿。', 'en' => 'No submissions yet.'],
            'no_favorites' => ['label' => '无收藏', 'zh_cn' => '还没有收藏。', 'en' => 'Nothing saved yet.'],
            'my_inbox' => ['label' => '我的留言', 'zh_cn' => '我的留言', 'en' => 'My messages'],
            'unfavorite_short' => ['label' => '取消', 'zh_cn' => '取消', 'en' => 'Remove'],
            'tag' => ['label' => '标签', 'zh_cn' => '标签', 'en' => 'Tag'],
            'tag_empty' => ['label' => '标签空', 'zh_cn' => '该标签下暂无内容。', 'en' => 'Nothing in this tag yet.'],
            'price' => ['label' => '价格', 'zh_cn' => '价格', 'en' => 'Price'],
            'price_ask' => ['label' => '面议', 'zh_cn' => '面议', 'en' => 'On request'],
            'inquire' => ['label' => '立即咨询', 'zh_cn' => '立即咨询', 'en' => 'Inquire'],
            'visit_site' => ['label' => '访问网站', 'zh_cn' => '访问网站', 'en' => 'Visit site'],
            'extra_fields' => ['label' => '扩展字段', 'zh_cn' => '扩展字段', 'en' => 'Extra fields'],
            'please_select' => ['label' => '请选择', 'zh_cn' => '请选择', 'en' => 'Please select'],
            'yes' => ['label' => '是', 'zh_cn' => '是', 'en' => 'Yes'],
            'model' => ['label' => '模型', 'zh_cn' => '模型', 'en' => 'Model'],
            'channel' => ['label' => '栏目', 'zh_cn' => '栏目', 'en' => 'Category'],
            'title_field' => ['label' => '标题', 'zh_cn' => '标题', 'en' => 'Title'],
            'excerpt_field' => ['label' => '摘要', 'zh_cn' => '摘要', 'en' => 'Excerpt'],
            'body_field' => ['label' => '正文', 'zh_cn' => '正文', 'en' => 'Body'],
            'cover_url' => ['label' => '封面', 'zh_cn' => '封面 URL', 'en' => 'Cover URL'],
            'save_draft' => ['label' => '保存草稿', 'zh_cn' => '保存草稿', 'en' => 'Save draft'],
            'submit_review' => ['label' => '提交审核', 'zh_cn' => '提交审核', 'en' => 'Submit for review'],
            'back_list' => ['label' => '返回列表', 'zh_cn' => '返回列表', 'en' => 'Back to list'],
            'edit_post' => ['label' => '编辑投稿', 'zh_cn' => '编辑投稿', 'en' => 'Edit post'],
            'no_contribute_cats' => ['label' => '无投稿栏目', 'zh_cn' => '该模型还没有可投稿栏目，请联系管理员。', 'en' => 'This model has no open categories yet.'],
            'avatar_chunk_hint' => ['label' => '分片上传说明', 'zh_cn' => '分片上传头像（大文件自动切块）', 'en' => 'Large avatars are uploaded in chunks.'],
            'debug_link' => ['label' => '调试链接', 'zh_cn' => '本地调试链接：', 'en' => 'Debug link:'],
            'status_published' => ['label' => '已发布', 'zh_cn' => '已发布', 'en' => 'Published'],
            'status_pending' => ['label' => '待审', 'zh_cn' => '待审', 'en' => 'Pending'],
            'status_draft' => ['label' => '草稿', 'zh_cn' => '草稿', 'en' => 'Draft'],
            'comment_approved' => ['label' => '评论通过', 'zh_cn' => '已通过', 'en' => 'Approved'],
            'comment_rejected' => ['label' => '评论拒绝', 'zh_cn' => '已拒绝', 'en' => 'Rejected'],
            'comment_spam' => ['label' => '评论垃圾', 'zh_cn' => '垃圾', 'en' => 'Spam'],
            'comment_pending' => ['label' => '评论待审', 'zh_cn' => '待审核', 'en' => 'Pending'],
            'upload_init' => ['label' => '上传初始化', 'zh_cn' => '初始化…', 'en' => 'Starting…'],
            'upload_init_fail' => ['label' => '初始化失败', 'zh_cn' => '初始化失败', 'en' => 'Could not start upload'],
            'upload_chunk_fail' => ['label' => '分片失败', 'zh_cn' => '分片 :n 失败', 'en' => 'Part :n failed'],
            'upload_progress' => ['label' => '上传中', 'zh_cn' => '上传中 :pct%', 'en' => 'Uploading :pct%'],
            'upload_merge_fail' => ['label' => '合并失败', 'zh_cn' => '合并失败', 'en' => 'Could not finish upload'],
            'upload_done' => ['label' => '上传完成', 'zh_cn' => '上传完成', 'en' => 'Uploaded'],
            'upload_fail' => ['label' => '上传失败', 'zh_cn' => '上传失败', 'en' => 'Upload failed'],
            'not_logged_in' => ['label' => '未登录', 'zh_cn' => '未登录', 'en' => 'Please sign in'],
            'login_ok' => ['label' => '登录成功', 'zh_cn' => '登录成功', 'en' => 'Signed in'],
            'register_ok' => ['label' => '注册成功', 'zh_cn' => '注册成功', 'en' => 'Account created'],
            'login_fail' => ['label' => '登录失败', 'zh_cn' => '邮箱或密码错误', 'en' => 'Wrong email or password'],
            'profile_saved' => ['label' => '资料已更新', 'zh_cn' => '资料已更新', 'en' => 'Profile saved'],
            'password_saved' => ['label' => '密码已更新', 'zh_cn' => '密码已更新', 'en' => 'Password updated'],
            'password_reset_ok' => ['label' => '密码已重置', 'zh_cn' => '密码已重置，请登录', 'en' => 'Password reset. Please sign in.'],
            'reset_invalid' => ['label' => '重置无效', 'zh_cn' => '重置链接无效或已过期', 'en' => 'This reset link is invalid or expired'],
            'password_wrong' => ['label' => '当前密码不对', 'zh_cn' => '当前密码不正确', 'en' => 'Current password is incorrect'],
            'reset_mail_sent' => ['label' => '重置邮件已发', 'zh_cn' => '重置邮件已发送，请查收邮箱', 'en' => 'Check your inbox for the reset email'],
            'reset_debug' => ['label' => '重置调试', 'zh_cn' => '未配置发信。本地调试可使用下面的链接（1 小时内有效）', 'en' => 'Mail is not configured. Use the debug link below (valid for 1 hour).'],
            'reset_sent_if' => ['label' => '重置已受理', 'zh_cn' => '若该邮箱已注册，将发送重置指引', 'en' => 'If that email is registered, reset instructions will be sent'],
            'no_contribute_models' => ['label' => '无投稿模型', 'zh_cn' => '当前没有开放投稿的模型', 'en' => 'No models are open for submissions'],
            'model_missing' => ['label' => '模型不存在', 'zh_cn' => '模型不存在', 'en' => 'Model not found'],
            'cannot_contribute' => ['label' => '无权投稿', 'zh_cn' => '当前会员组无权投稿', 'en' => 'Your group cannot submit posts'],
            'cannot_contribute_model' => ['label' => '模型不可投', 'zh_cn' => '该模型不允许会员投稿', 'en' => 'This model is not open for submissions'],
            'fill_title' => ['label' => '请填标题', 'zh_cn' => '请填写标题', 'en' => 'Please enter a title'],
            'pick_category' => ['label' => '请选栏目', 'zh_cn' => '请选择栏目', 'en' => 'Please choose a category'],
            'published_locked' => ['label' => '已发布锁定', 'zh_cn' => '已发布内容请联系管理员修改', 'en' => 'Published posts can only be changed by an admin'],
            'only_own_posts' => ['label' => '只能改自己', 'zh_cn' => '只能编辑自己的投稿', 'en' => 'You can only edit your own posts'],
            'draft_saved' => ['label' => '草稿已保存', 'zh_cn' => '草稿已保存', 'en' => 'Draft saved'],
            'submitted_review' => ['label' => '已提交审核', 'zh_cn' => '已提交审核', 'en' => 'Submitted for review'],
            'comment_off' => ['label' => '评论关闭', 'zh_cn' => '评论功能已关闭', 'en' => 'Comments are turned off'],
            'comment_closed' => ['label' => '不可评论', 'zh_cn' => '内容不可评论', 'en' => 'Comments are not allowed here'],
            'comment_login_first' => ['label' => '评论先登录', 'zh_cn' => '请先登录后再评论', 'en' => 'Please sign in to comment'],
            'captcha_error' => ['label' => '验证码错误', 'zh_cn' => '验证码错误', 'en' => 'Captcha is incorrect'],
            'comment_rate' => ['label' => '评论太快', 'zh_cn' => '发表过于频繁，请 :n 秒后再试', 'en' => 'Please wait :n seconds before commenting again'],
            'comment_parent_gone' => ['label' => '回复不存在', 'zh_cn' => '回复的评论不存在或未通过审核', 'en' => 'That comment is gone or not approved'],
            'comment_published' => ['label' => '评论已发布', 'zh_cn' => '评论已发布', 'en' => 'Comment posted'],
            'comment_queued' => ['label' => '评论待审提示', 'zh_cn' => '评论已提交，通过审核后显示', 'en' => 'Comment submitted. It will appear after review.'],
            'member_login_view' => ['label' => '会员才能看', 'zh_cn' => '该内容需会员登录后查看', 'en' => 'Sign in as a member to view this'],
            'member_deny_view' => ['label' => '无权查看', 'zh_cn' => '当前会员组无权查看此内容', 'en' => 'Your group cannot view this'],
            'member_login_download' => ['label' => '会员才能下', 'zh_cn' => '该资源需会员登录后下载', 'en' => 'Sign in as a member to download this'],
            'member_deny_download' => ['label' => '无权下载', 'zh_cn' => '当前会员组无权下载', 'en' => 'Your group cannot download this'],
            'member_login_link' => ['label' => '会员才能点', 'zh_cn' => '该外链需会员登录后访问', 'en' => 'Sign in as a member to open this link'],
            'member_deny_link' => ['label' => '无权访问外链', 'zh_cn' => '当前会员组无权访问此外链', 'en' => 'Your group cannot open this link'],
            'preview_mark' => ['label' => '预览前缀', 'zh_cn' => '[预览] ', 'en' => '[Preview] '],
            'form_ok' => ['label' => '表单成功', 'zh_cn' => '提交成功，我们会尽快联系您。', 'en' => 'Submitted. We will get back to you soon.'],
            'cannot_submit_form' => ['label' => '无权交表', 'zh_cn' => '当前会员组无权提交表单', 'en' => 'Your group cannot submit this form'],
            'favorited' => ['label' => '已收藏', 'zh_cn' => '已收藏', 'en' => 'Saved'],
            'unfavorited' => ['label' => '已取消收藏', 'zh_cn' => '已取消收藏', 'en' => 'Removed'],
            'cannot_favorite' => ['label' => '无权收藏', 'zh_cn' => '无权收藏该内容', 'en' => 'You cannot save this'],
            'content_missing' => ['label' => '内容不存在', 'zh_cn' => '内容不存在', 'en' => 'Content not found'],
            'field_required' => ['label' => '字段必填', 'zh_cn' => ':name不能为空', 'en' => ':name is required'],
            'no_upload' => ['label' => '无上传权', 'zh_cn' => '无上传权限', 'en' => 'Upload is not allowed'],
            'chunks_invalid' => ['label' => '分片数量无效', 'zh_cn' => '分片数量无效', 'en' => 'Invalid chunk count'],
            'file_too_big' => ['label' => '文件过大', 'zh_cn' => '文件过大（上限 50MB）', 'en' => 'File is too large (50MB max)'],
            'upload_session' => ['label' => '上传会话无效', 'zh_cn' => '上传会话无效或已过期', 'en' => 'Upload session is invalid or expired'],
            'chunk_index' => ['label' => '分片序号无效', 'zh_cn' => '分片序号无效', 'en' => 'Invalid chunk index'],
            'chunk_too_big' => ['label' => '分片过大', 'zh_cn' => '分片过大', 'en' => 'Chunk is too large'],
            'chunks_missing' => ['label' => '分片未齐', 'zh_cn' => '分片未齐：缺少 :n', 'en' => 'Missing chunk :n'],
            'write_failed' => ['label' => '无法写入', 'zh_cn' => '无法写入目标文件', 'en' => 'Could not write the file'],
            'read_chunk_failed' => ['label' => '读分片失败', 'zh_cn' => '读取分片失败', 'en' => 'Could not read a chunk'],
            'merge_too_big' => ['label' => '合并超限', 'zh_cn' => '合并后超过大小限制', 'en' => 'Merged file is too large'],
            'avatar_must_image' => ['label' => '头像须图片', 'zh_cn' => '头像须为图片', 'en' => 'Avatar must be an image'],
            'sports_disclaimer' => ['label' => '体育声明', 'zh_cn' => '仅作体育资讯/公开源目录演示，请遵守当地法律法规与版权要求。', 'en' => 'Demo sports feeds and public sources. Follow local law and copyright.'],
            'mobile_edition' => ['label' => '移动版前缀', 'zh_cn' => '移动版 · ', 'en' => 'Mobile · '],
            'site_closed' => ['label' => '维护中', 'zh_cn' => '维护中', 'en' => 'Maintenance'],
            'site_closed_default' => ['label' => '关站默认说明', 'zh_cn' => '站点维护中，请稍后再访。', 'en' => 'The site is under maintenance. Please check back later.'],
            'server_error' => ['label' => '500', 'zh_cn' => '服务器出错了', 'en' => 'Something went wrong'],
            'server_error_hint' => ['label' => '500说明', 'zh_cn' => '服务器暂时出了点问题，请稍后再试。', 'en' => 'Please try again in a moment.'],
            'no_more' => ['label' => '没有了', 'zh_cn' => '没有了', 'en' => 'None'],
            'sort_newest' => ['label' => '最新', 'zh_cn' => '最新', 'en' => 'Newest'],
            'sort_oldest' => ['label' => '最早', 'zh_cn' => '最早', 'en' => 'Oldest'],
            'sort_weight' => ['label' => '权重', 'zh_cn' => '权重', 'en' => 'Order'],
            'sort_views' => ['label' => '浏览最多', 'zh_cn' => '浏览最多', 'en' => 'Most viewed'],
            'sort_downloads' => ['label' => '下载最多', 'zh_cn' => '下载最多', 'en' => 'Most downloaded'],
            'blog_hero' => ['label' => '博客首页标题', 'zh_cn' => '博客', 'en' => 'Blog'],
            'form_name' => ['label' => '表单姓名', 'zh_cn' => '姓名', 'en' => 'Name'],
            'form_message' => ['label' => '表单留言', 'zh_cn' => '留言', 'en' => 'Message'],
            'form_missing' => ['label' => '表单不存在', 'zh_cn' => '表单不存在', 'en' => 'Form not found'],
            'mail_notice' => ['label' => '邮件通知', 'zh_cn' => '通知', 'en' => 'Notice'],
            'mail_reset_subject' => ['label' => '重置密码邮件', 'zh_cn' => '重置密码', 'en' => 'Reset your password'],
            'mail_reset_heading' => ['label' => '重置密码标题', 'zh_cn' => '重置会员密码', 'en' => 'Reset your member password'],
            'mail_reset_audience' => ['label' => '重置对象', 'zh_cn' => '会员', 'en' => 'member'],
            'mail_reset_action' => ['label' => '打开重置链接', 'zh_cn' => '打开重置链接', 'en' => 'Open reset link'],
            'mail_reset_body' => ['label' => '重置邮件正文', 'zh_cn' => '你正在重置:audience密码。请在 1 小时内打开下面的按钮。如果不是你本人操作，请忽略这封信。', 'en' => 'You asked to reset your :audience password. Open the button below within 1 hour. If this was not you, ignore this email.'],
            'comment_need_name' => ['label' => '请填昵称', 'zh_cn' => '请填写昵称', 'en' => 'Please enter a name'],
            'comment_length' => ['label' => '评论字数', 'zh_cn' => '评论内容需在 2～2000 字之间', 'en' => 'Comments must be 2–2000 characters'],
            'reply' => ['label' => '回复', 'zh_cn' => '回复', 'en' => 'Reply'],
            'cancel_reply' => ['label' => '取消回复', 'zh_cn' => '取消回复', 'en' => 'Cancel reply'],
            'skip_content' => ['label' => '跳到正文', 'zh_cn' => '跳到正文', 'en' => 'Skip to content'],
            'replying_to' => ['label' => '回复给', 'zh_cn' => '回复 :name', 'en' => 'Replying to :name'],
            'forbidden' => ['label' => '无权访问', 'zh_cn' => '无权访问', 'en' => 'Access denied'],
            'forbidden_hint' => ['label' => '无权说明', 'zh_cn' => '你没有权限查看这个页面。', 'en' => 'You do not have permission to view this page.'],
            'session_expired' => ['label' => '页面过期', 'zh_cn' => '页面已过期', 'en' => 'This page expired'],
            'session_expired_hint' => ['label' => '过期说明', 'zh_cn' => '请刷新后重新提交。', 'en' => 'Refresh the page and submit again.'],
            'try_again' => ['label' => '重试', 'zh_cn' => '刷新重试', 'en' => 'Try again'],
        ];
    }

    /** @return array<string, array<string, string>> */
    public static function stored(): array
    {
        try {
            $raw = Setting::getValue('theme_ui', '');
        } catch (\Throwable) {
            return [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function get(string $key): string
    {
        $locale = Locale::current();
        $catalog = self::catalog();
        $fallback = $catalog[$key]['zh_cn'] ?? $key;
        $fromTheme = self::fromThemePack($key, $locale);
        if ($fromTheme !== null) {
            return $fromTheme;
        }
        $stored = self::stored();
        $fromStore = $stored[$key][$locale] ?? null;
        if (is_string($fromStore) && trim($fromStore) !== '') {
            return $fromStore;
        }
        $fromCatalog = $catalog[$key][$locale] ?? null;
        if (is_string($fromCatalog) && $fromCatalog !== '') {
            return $fromCatalog;
        }
        $default = Locale::default();
        $fromDefault = $stored[$key][$default] ?? $catalog[$key][$default] ?? $catalog[$key]['en'] ?? null;
        if (is_string($fromDefault) && $fromDefault !== '') {
            return $fromDefault;
        }

        return $fallback;
    }

    /** @return list<string> */
    public static function packKeys(): array
    {
        return ['search', 'home', 'login', 'register', 'member', 'logout', 'comments', 'prev', 'next', 'not_found', 'friend_links'];
    }

    /** @return array<string, array<string, string>> */
    public static function storedFor(string $slug): array
    {
        try {
            $raw = Setting::getValue('theme_lang.'.$slug, '');
        } catch (\Throwable) {
            return [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param  array<string, array<string, string>>  $input */
    public static function saveFor(string $slug, array $input): void
    {
        $prev = self::storedFor($slug);
        $codes = Locale::configuredCodes();
        if ($codes === []) {
            $codes = Locale::codes();
        }
        $out = [];
        foreach (self::packKeys() as $key) {
            $row = is_array($prev[$key] ?? null) ? $prev[$key] : [];
            foreach ($codes as $code) {
                if (isset($input[$key]) && array_key_exists($code, $input[$key])) {
                    $row[$code] = trim((string) $input[$key][$code]);
                } elseif (! isset($row[$code])) {
                    $row[$code] = '';
                }
            }
            $out[$key] = $row;
        }
        Setting::setValue('theme_lang.'.$slug, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected static function fromThemePack(string $key, string $locale): ?string
    {
        $slug = trim((string) config('cms.theme', ''));
        if ($slug === '') {
            return null;
        }
        $stored = self::storedFor($slug);
        $value = $stored[$key][$locale] ?? null;
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return null;
    }

    /** @param  array<string, array<string, string>>  $input */
    public static function save(array $input): void
    {
        $prev = self::stored();
        $codes = Locale::configuredCodes();
        if ($codes === []) {
            $codes = Locale::codes();
        }
        $out = [];
        foreach (self::catalog() as $key => $meta) {
            $row = is_array($prev[$key] ?? null) ? $prev[$key] : [];
            foreach ($codes as $code) {
                if (isset($input[$key]) && array_key_exists($code, $input[$key])) {
                    $value = trim((string) $input[$key][$code]);
                    $row[$code] = $value !== '' ? $value : (string) ($meta[$code] ?? $meta['zh_cn']);
                } elseif (! isset($row[$code]) || trim((string) $row[$code]) === '') {
                    $row[$code] = (string) ($meta[$code] ?? $meta['zh_cn']);
                }
            }
            $out[$key] = $row;
        }
        Setting::setValue('theme_ui', json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

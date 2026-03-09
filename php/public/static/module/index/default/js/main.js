$(function () {
    // 首页banner轮播图
    if ($('.home-banner.swiper-container').length) {
        var mySwiper = new Swiper('.home-banner.swiper-container', {
            // direction: 'horizontal',
            loop: true, // 循环模式选项
            autoplay: true,
            // 分页器
            pagination: {
                el: '.swiper-pagination',
            },

            // 进后退按钮
            navigation: {
                nextEl: '.swiper-home-next',
                prevEl: '.swiper-home-prev',
            },

            // 滚动条
            scrollbar: {
                el: '.swiper-scrollbar',
            },
        })
    }

    // 合作伙伴
    if ($('.frm-swiper').length) {
        var num = $(document).width()<=768?2:6;
        var mySwiper2 = new Swiper('.frm-swiper', {
            slidesPerView: num,
            spaceBetween: 30,
            // pagination: {
            //     el: ".swiper-pagination",
            //     clickable: true,
            // },

            // 进后退按钮
            navigation: {
                nextEl: '.swiper-outside-next',
                prevEl: '.swiper-outside-prev',
            },
        })
    }


    // 滚动改变导航栏
    $(window).on('scroll', function () {
        function fixedHeader() {
            var headerTopBar = 43;
            var headerOneTopSpace = 0;

            var headerOneELement = $('.header-one .site-navigation');
            var headerTwoELement = $('.header-two .site-navigation');

            if ($(window).scrollTop() > headerTopBar + headerOneTopSpace) {
                $(headerOneELement).addClass('navbar-fixed');
                $('.header-one').css('margin-bottom', headerOneELement.outerHeight());
            } else {
                $(headerOneELement).removeClass('navbar-fixed');
                $('.header-one').css('margin-bottom', 0);
            }
            if ($(window).scrollTop() > headerTopBar) {
                $(headerTwoELement).addClass('navbar-fixed');
                $('.header-two').css('margin-bottom', headerTwoELement.outerHeight());
            } else {
                $(headerTwoELement).removeClass('navbar-fixed');
                $('.header-two').css('margin-bottom', 0);
            }
        }
        fixedHeader();

        function scrollTopBtn() {
            var scrollToTop = $('#back-to-top'),
                scroll = $(window).scrollTop();
            if (scroll >= 50) {
                scrollToTop.fadeIn();
            } else {
                scrollToTop.fadeOut();
            }
        }
        scrollTopBtn();
    });
    if ($(window).scrollTop()>100) {
        $('.header-two .site-navigation').addClass('navbar-fixed');
        $('.header-two').css('margin-bottom', $('.header-two .site-navigation').outerHeight());
    }

    // 搜索
    function navSearch() {
        $('.nav-search').on('click', function () {
            $('.search-block').fadeIn(350);
        });
        $('.search-close').on('click', function () {
            $('.search-block').fadeOut(350);
        });
        // 绑定搜索回车
        $('#search-field').on('keypress',function () {})
        $('.search-field').on('keypress',function () {})
    }
    navSearch();

    // 滚动到顶部
    function backToTop() {
        $('#back-to-top').on('click', function () {
            $('#back-to-top').tooltip('hide');
            $('body,html').animate({
                scrollTop: 0
            }, 800);
            return false;
        });
    }
    backToTop();

    // 图片放大
    if ($('[data-toggle="photos"]').length>0) {
        $('[data-toggle="photos"]').click(function () {
            var data = [];
            $('#'+$(this).data('id')).find('img').each(function(index){
                var othis = $(this);
                othis.attr('layer-index', index);
                data.push({
                    alt: othis.attr('alt'),
                    pid: othis.attr('layer-pid'),
                    src: othis.attr('layer-src') || othis.attr('src'),
                    thumb: othis.attr('src')
                });
            });
            if (data) {
                layer.photos({
                    photos: {
                        start: $(this).attr('layer-index'),
                        data: data
                    },
                }, true);
            }
        })
    }

    // 视频播放
    if ($('[data-toggle="h5video"]').length>0) {
        $('[data-toggle="h5video"]').click(function () {
            layer.open({
                type: 1
                ,title: false
                ,closeBtn: true
                ,area: $('body').width()<768?'95%':'600px'
                ,id: 'home-id-css'
                ,btnAlign: 'c'
                ,moveType: 1
                ,resize: false
                ,content: '<video style="width: 100%;height: 100%;margin-bottom: -8px;" controls>\n' +
                    '<source src="'+$(this).data('url')+'" type="video/mp4"></video>'
                ,success: function(layero){}
            });
        })
    }

    // 邮箱、手机号弹出
    $('.social').click(function (e) {
        layer.open({
            type: 1
            ,title: false
            ,closeBtn: true
            ,id: 'home-id-css'
            ,btnAlign: 'c'
            ,moveType: 1
            ,resize: false
            ,content: '<div style="background-color: #ffffff;padding: 20px 30px">'+$(this).data('url')+'</div>'
            ,success: function(layero){}
        });
    })

    // 图片弹出
    $('.social-img').click(function (e) {
        layer.open({
            type: 1
            ,title: false
            ,closeBtn: true
            ,id: 'home-id-css'
            ,btnAlign: 'c'
            ,moveType: 1
            ,resize: false
            ,content: '<div style="background-color: #ffffff;padding: 20px 30px"><img src="'+$(this).data('url')+'" alt="" style="width: 250px;"></div>'
            ,success: function(layero){}
        });
    })

    if ($(document).width()<1000) {
        $('.navbar-collapse>ul>li.dropdown').children('.dropdown-toggle').attr('data-toggle','dropdown');
        // 手机端默认展开全部子栏目
        $('.navbar-collapse>ul>li.dropdown').click(function (e) {
            $(this).find('.dropdown-submenu .dropdown-menu').addClass('show')
        });
    }
})
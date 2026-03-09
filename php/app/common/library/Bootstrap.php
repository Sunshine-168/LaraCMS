<?php
// +----------------------------------------------------------------------
// | HkCms 自定义分页
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn/, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

namespace app\common\library;

use think\Collection;
use think\Paginator;

class Bootstrap extends Paginator
{
    public function __construct($items, int $listRows, int $currentPage = 1, int $total = null, bool $simple = false, array $options = [])
    {
        parent::__construct($items, $listRows, $currentPage, $total, $simple, $options);
        $this->options = array_merge($this->options, $options);

        $this->simple   = $simple;
        $this->listRows = $listRows;

        if (!$items instanceof Collection) {
            $items = Collection::make($items);
        }

        if ($simple) {
            $this->currentPage = $this->setCurrentPage($currentPage);
            $this->hasMore     = count($items) > ($this->listRows);
            $items             = $items->slice(0, $this->listRows);
        } else {
            $this->total       = $total;
            $this->lastPage    = (int) ceil($total / $listRows);
            $this->currentPage = $this->setCurrentPage($currentPage);
            $this->hasMore     = $this->currentPage < $this->lastPage;
        }
        $this->items = $items;
    }

    /**
     * 首页按钮
     * @param string $text
     * @return string
     */
    protected function getHomeButton(string $text = "&laquo;"): string
    {
        if ($this->currentPage() <= 1) {
            return $this->getDisabledTextWrapper($text);
        }

        $url = $this->url(1);

        return $this->getPageLinkWrapper($url, $text);
    }

    /**
     * 上一页按钮
     * @param string $text
     * @return string
     */
    protected function getPreviousButton(string $text = "&laquo;"): string
    {
        if ($this->currentPage() <= 1) {
            return $this->getDisabledTextWrapper($text);
        }

        $url = $this->url(
            $this->currentPage() - 1
        );

        return $this->getPageLinkWrapper($url, $text);
    }

    /**
     * 下一页按钮
     * @param string $text
     * @return string
     */
    protected function getNextButton(string $text = '&raquo;'): string
    {
        if (!$this->hasMore) {
            return $this->getDisabledTextWrapper($text);
        }

        $url = $this->url($this->currentPage() + 1);

        return $this->getPageLinkWrapper($url, $text);
    }

    /**
     * 末页按钮
     * @param string $text
     * @return string
     */
    protected function getLastButton(string $text = '&raquo;'): string
    {
        if (!$this->hasMore) {
            return $this->getDisabledTextWrapper($text);
        }

        $url = $this->url($this->lastPage);

        return $this->getPageLinkWrapper($url, $text);
    }

    /**
     * 页码信息
     * @param $txt
     * @return string
     */
    protected function getPageInfo($txt = ''): string
    {
        if ($txt) {
            return sprintf($txt, $this->lastPage, $this->total);
        }
        return '<span>'.lang('A total of %s page %s data', [$this->lastPage, $this->total]).'</span>';
    }

    /**
     * 跳转页码
     * @return string
     */
    protected function getPageJump(): string
    {
        if (site('url_mode')==1) {
            $query = $GLOBALS["JUMP_query"];
            unset($query['page']);
            $url = index_url($this->options['path'], $query);
        } else {
            // 动态
            $url = $this->url(1);
        }

        $data = parse_url($url);
        if (!empty($data['query'])) {
            $query = explode('&', $data['query']);
            $params = [];
            foreach ($query as $param) {
                $item = explode('=', $param);
                $params[$item[0]] = $item[1]??'';
            }
            $params['page'] = '';
            $url = $data['path'].'?'.http_build_query($params, '', '&');
        } else {
            $url .= '?page=';
        }

        $id = 'id-'.get_random_str(6);
        return '<span>'.lang('Jump to').'</span>
                <input type="number" class="input-jump-page" id="'.$id.'" placeholder="'.lang('Page number').'">
                <span>'.lang('Page').'</span>
                <a href="#" onclick="window.location.href=\''.$url.'\'+document.getElementById(\''.$id.'\').value;" class="btn btn-primary">'.lang('Jump').'</a>';
    }

    /**
     * 页码按钮
     * @param $side
     * @param integer $dots 【已取消】
     * @return string
     */
    protected function getLinks($side=3, $dots = 1): string
    {
        if ($this->simple) {
            return '';
        }

        $block = [
            'first'  => null,
            'slider' => null,
            'last'   => null,
        ];
        $window = $side;
//        $window = $side * 2;

//        if ($this->lastPage < $window + 4) {
//            $block['first'] = $this->getUrlRange(1, $this->lastPage);
//        } elseif ($this->currentPage <= $window) {
//            $block['first'] = $this->getUrlRange(1, $window + 2);
//            $block['last']  = $this->getUrlRange($this->lastPage - 1, $this->lastPage);
//        } elseif ($this->currentPage > ($this->lastPage - $window)) {
//            $block['first'] = $this->getUrlRange(1, 2);
//            $block['last']  = $this->getUrlRange($this->lastPage - ($window + 2), $this->lastPage);
//        } else {
//            $block['first']  = $this->getUrlRange(1, 2);
//            $block['slider'] = $this->getUrlRange($this->currentPage - $side, $this->currentPage + $side);
//            $block['last']   = $this->getUrlRange($this->lastPage - 1, $this->lastPage);
//        }

        if ($this->lastPage <= $window) { // 没超过原样显示
            $block['first'] = $this->getUrlRange(1, $this->lastPage);
        } else {
            $num = intval($window/2);
            if ($this->currentPage-$window <= 0) {
                if ($window%2) {
                    if ($this->currentPage <= $num+1) {
                        $block['first']  = $this->getUrlRange(1, $window);
                    } else {
                        $block['first']  = $this->getUrlRange($this->currentPage-$num, $this->currentPage+$num);
                    }
                } else {
                    if ($this->currentPage <= $num) {
                        $block['first']  = $this->getUrlRange(1, $window);
                    } else {
                        $block['first']  = $this->getUrlRange($this->currentPage-$num+1, $this->currentPage+$num);
                    }
                }
            } else if ($this->currentPage+$num > $this->lastPage) {
                $block['first']  = $this->getUrlRange($this->lastPage-$window+1, $this->lastPage);
            } else {
                $block['first']  = $this->getUrlRange($window%2?$this->currentPage-$num:$this->currentPage-$num+1, $this->currentPage+$num);
            }
        }

        $html = '';

        if (is_array($block['first'])) {
            $html .= $this->getUrlLinks($block['first']);
        }

        if (is_array($block['slider'])) {
            //if (!$dots) {
            //    $html .= $this->getDots();
            //}
            $html .= $this->getUrlLinks($block['slider']);
        }

        if (is_array($block['last'])) {
            //if (!$dots) {
            //    $html .= $this->getDots();
            //}
            $html .= $this->getUrlLinks($block['last']);
        }

        return $html;
    }

    /**
     * 渲染分页html
     * @param $tag
     * @return mixed
     */
    public function render($tag=[])
    {
        $item = !empty($tag['item']) ? explode(',', $tag['item']) : [];
        if (empty($item)) {
            if ($this->hasPages()) {
                if ($this->simple) {
                    return sprintf(
                        '<ul class="pager">%s %s</ul>',
                        $this->getPreviousButton(),
                        $this->getNextButton()
                    );
                } else {
                    return sprintf(
                        '<ul class="pagination">%s %s %s</ul>',
                        $this->getPreviousButton(),
                        $this->getLinks(),
                        $this->getNextButton()
                    );
                }
            }
        }
        $side = !empty($tag['size']) && is_numeric($tag['size']) ? $tag['size'] : 5;
        $dots = isset($tag['dots']) && $tag['dots']==1 ? 0 : 1;
        $infotxt = !empty($tag['info']) ? $tag['info']:'';
        $emptxt = !empty($tag['emptxt']) ? $tag['emptxt']:'';
        $hasemp = !empty($tag['hasemp']) && $tag['hasemp']=='false' ? 0:1;

        if ($this->hasPages()) {
            if ($this->simple) {
                return sprintf(
                    '<ul class="pager">%s %s</ul>',
                    $this->getPreviousButton(),
                    $this->getNextButton()
                );
            } else {
                $ul_arr = [];
                $info = '';
                $jump = '';
                if (in_array('home', $item)) { // 首页
                    $ul_arr[] = $this->getHomeButton($tag['home']??lang('Home'));
                }
                if (in_array('pre', $item)) { // 上一页
                    $ul_arr[] = $this->getPreviousButton($tag['pre']??lang('Previous page'));
                }
                if (in_array('pageno', $item)) { // 页码
                    $ul_arr[] = $this->getLinks($side, $dots);
                }
                if (in_array('next', $item)) { // 下一页
                    $ul_arr[] = $this->getNextButton($tag['next']??lang('Next page'));
                }
                if (in_array('last', $item)) { // 末页
                    $ul_arr[] = $this->getLastButton($tag['last']??lang('Last page'));
                }
                if (in_array('info', $item)) { // 数量说明
                    $info = '<div class="pagination_info">'.$this->getPageInfo($infotxt).'</div>';
                }
                if (in_array('jump', $item)) { // 跳转指定页码
                    $jump = '<div class="pagination_jump">'.$this->getPageJump().'</div>';
                }
                return sprintf(
                    '<div class="pagination-block"><ul class="pagination">%s</ul>%s%s</div>',
                    implode('', $ul_arr),$info,$jump
                );
            }
        } else {
            if (!$hasemp) {
                return '';
            }
            $info = '<div class="pagination_info">'.$this->getPageInfo($emptxt).'</div>';
            return sprintf(
                '<div class="pagination-block">%s</div>', $info
            );
        }
    }

    /**
     * 生成一个可点击的按钮
     *
     * @param  string $url
     * @param  string $page
     * @return string
     */
    protected function getAvailablePageWrapper(string $url, string $page): string
    {
        return '<li><a href="' . htmlentities($url) . '">' . $page . '</a></li>';
    }

    /**
     * 生成一个禁用的按钮
     *
     * @param  string $text
     * @return string
     */
    protected function getDisabledTextWrapper(string $text): string
    {
        return '<li class="disabled"><span>' . $text . '</span></li>';
    }

    /**
     * 生成一个激活的按钮
     *
     * @param  string $text
     * @return string
     */
    protected function getActivePageWrapper(string $text): string
    {
        return '<li class="active"><span>' . $text . '</span></li>';
    }

    /**
     * 生成省略号按钮
     *
     * @return string
     */
    protected function getDots(): string
    {
        return $this->getDisabledTextWrapper('...');
    }

    /**
     * 批量生成页码按钮.
     *
     * @param  array $urls
     * @return string
     */
    protected function getUrlLinks(array $urls): string
    {
        $html = '';

        foreach ($urls as $page => $url) {
            $html .= $this->getPageLinkWrapper($url, $page);
        }

        return $html;
    }

    /**
     * 生成普通页码按钮
     *
     * @param  string $url
     * @param  string    $page
     * @return string
     */
    protected function getPageLinkWrapper(string $url, string $page): string
    {
        if ($this->currentPage() == $page) {
            return $this->getActivePageWrapper($page);
        }

        return $this->getAvailablePageWrapper($url, $page);
    }

    /**
     * 获取页码对应的链接
     *
     * @access protected
     * @param int $page
     * @return string
     */
    protected function url(int $page): string
    {
        if ($page <= 0) {
            $page = 1;
        }

        if ($url = self::diyUrl($page, $this->options)) { // 自定义URL链接规则
            return $url . $this->buildFragment();
        }

        if (strpos($this->options['path'], '[PAGE]') === false) {
            $parameters = [$this->options['var_page'] => $page];
            $path       = $this->options['path'];
        } else {
            $parameters = [];
            $path       = str_replace('[PAGE]', $page, $this->options['path']);
        }

        if (count($this->options['query']) > 0) {
            $parameters = array_merge($this->options['query'], $parameters);
        }

        $url = $path;
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters, '', '&');
        }

        return $url . $this->buildFragment();
    }

    protected static $diyUrResolver;

    /**
     * 自定义回调URL生成
     * @access public
     * @param int $page
     * @param array $options
     * @return string
     */
    public static function diyUrl(int $page, array $options): string
    {
        if (isset(static::$diyUrResolver)) {
            return call_user_func(static::$diyUrResolver, $page, $options);
        }

        return '';
    }

    /**
     * 自定义回调URL生成
     * @param \Closure $resolver
     */
    public static function diyUrlResolver(\Closure $resolver)
    {
        static::$diyUrResolver = $resolver;
    }

    /**
     * 获取自定义
     * @return mixed
     */
    public static function getDiyUrl()
    {
        return isset(static::$diyUrResolver);
    }
}
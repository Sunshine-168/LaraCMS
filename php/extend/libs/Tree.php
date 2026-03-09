<?php
namespace libs;

/**
 * 通用的树型类，可以生成任何树型结构
 */
class Tree {

    protected $config = [
        'id' => 'id',   // 主键
        'pid' => 'parent_id', // 父级ID
    ];

    /**
     * 生成树型结构所需要的2维数组
     * @var array
     */
    public $arr = [];

    /**
     * 生成树型结构所需修饰符号，可以换成图片
     * @var array
     */
    public $icon = ['│', '├', '└'];
    public $nbsp = "&nbsp;";

    /**
     * @access private
     */
    public $ret = '';

    protected static $instance;

    /**
     * 单例模式
     * @param array $options
     * @return static
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 初始化，传入需要生成的原始数组
     * @param array $arr 数组
     * array(
     *      1 => array('id'=>'1','parentid'=>0,'name'=>'一级栏目一'),
     *      2 => array('id'=>'2','parentid'=>0,'name'=>'一级栏目二'),
     *      3 => array('id'=>'3','parentid'=>1,'name'=>'二级栏目一'),
     *      4 => array('id'=>'4','parentid'=>1,'name'=>'二级栏目二'),
     * )
     * @return $this
     */
    public function init($arr = [])
    {
        $this->arr = $arr;
        $this->ret = '';
        return $this;
    }

    /**
     *
     * 得到上级一维数组
     * @param int $myid 传入ID
     * @return array
     */
    public function getParent($myid)
    {
        $pid = 0;
        $newarr = [];
        foreach ($this->arr as $value) {
            if (!isset($value[$this->config['id']])) {
                continue;
            }
            if ($value[$this->config['id']] == $myid) {
                $pid = $value[$this->config['pid']];
                break;
            }
        }
        if ($pid) {
            foreach ($this->arr as $value) {
                if ($value['id'] == $pid) {
                    $newarr = $value;
                    break;
                }
            }
        }

        return $newarr;
    }

    /**
     * 得到子级数组
     * @param int $myid 传入ID
     * @return array|bool
     */
    public function getChild($myid)
    {
        $newarr = array();
        if (is_array($this->arr)) {
            foreach ($this->arr as $id => $value) {
                if ($myid) {
                    if ($value[$this->config['pid']] == $myid)
                        $newarr[$value[$this->config['id']]] = $value;
                } else {
                    if ($value[$this->config['pid']] == 0)
                        $newarr[$value[$this->config['id']]] = $value;
                }
            }
        }
        return $newarr ? $newarr : false;
    }

    /**
     * 获取所有下级的ID
     * @param $myid
     * @return array
     */
    public function getChildIds($myid)
    {
        $arr = [];
        $child = $this->getChild($myid);
        if ($child) {
            foreach ($child as $id => $value) {
                $arr[$id] = $id;
                $temp = $this->getChildIds($id);
                if (!empty($temp)) {
                    $arr = $temp + $arr;
                }
            }
        }
        return $arr;
    }

    /**
     * 得到得到树型结构
     * @param $myid int 该ID的所有下级
     * @param string $option 模板
     * @param int $selectId  设置选中ID
     * @param string $prefix 前缀
     * @return string
     */
    public function getTree($myid, $option='<option value=@id@ @selected@>@spacer@@title@</option>', $selectId = 0, $prefix = '')
    {
        $number = 1;
        //一级栏目
        $child = $this->getChild($myid);
        if (is_array($child)) {
            $total = count($child);
            foreach ($child as $id => $value) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $prefix ? $this->icon[0] : '';
                }
                $value['spacer'] = $prefix ? $prefix . $j : '';
                $value['selected']= $id == $selectId ? 'selected' : '';

                $ret = preg_replace_callback('/@(\w*)@/',function ($matches) use ($value) {
                    if (!isset($matches[1])) {
                        return false;
                    }
                    if (isset($value[$matches[1]])) {
                        return $value[$matches[1]];
                    }
                    return false;
                },$option);
                $this->ret .= $ret;
                $nbsp = $this->nbsp;
                $this->getTree($id, $option, $selectId, $prefix . $k . $nbsp);
                $number++;
            }
        }
        return $this->ret;
    }

    /***
     * 用于JsTree插件的多级结构数组
     * @param $myid
     * @param string $field
     * @param array $selectId
     * @param array $val 增加额外的字段， 示例：['a','b','c']
     * @param array $disabled 禁用特定ID 示例：['a','b','c']
     * @return array
     */
    public function getJsTree($myid, $field='title', $selectId = [], $val=[], $disabled=[])
    {
        $retarray = array();
        $state = ['opened'=>true,'disabled'=>false,'selected'=>false];
        $child = $this->getChild($myid);
        if (is_array($child)) {
            foreach ($child as $id => $value) {
                $retarray[$id]['id'] = $value[$this->config['id']];
                $retarray[$id]['text'] = $value[$field];
                $retarray[$id]['state'] = $state;

                // 增加额外字段
                if (!empty($val)) {
                    foreach ($val as $v) {
                        $retarray[$id]['data'][$v] = $value[$v];
                    }
                }
                // 禁用指定ID
                if (!empty($val) && in_array($retarray[$id]['id'], $disabled)) {
                    $retarray[$id]['state']['disabled'] = true;
                }

                if (isset($value['icon'])) {
                    $retarray[$id]['icon'] = $value['icon'];
                }

                // 设置选中
                if ($selectId && is_array($selectId)) {
                    if (in_array($value[$this->config['id']], $selectId) && !$this->getChild($value[$this->config['id']])) {
                        $retarray[$id]['state']['selected'] = true;
                    }
                }
                $retarray[$id]["children"] = $this->getJsTree($id, $field, $selectId,$val,$disabled);
            }
        }
        return array_values($retarray);
    }

    /**
     * 得到树型结构数组
     * @param $myid
     * @param $prefix
     * @return array
     */
    public function getTreeArray($myid, $prefix='')
    {
        $number = 1;
        $retarray = array();
        //一级栏目数组
        $child = $this->getChild($myid);
        if (is_array($child)) {
            $total = count($child);
            foreach ($child as $id => $value) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $prefix ? $this->icon[0] : '';
                }
                $value['spacer'] = $prefix ? $prefix . $j : '';

                @extract($value);
                $retarray[$id] = $value;
                $nbsp = $this->nbsp;
                $retarray[$id]["child"] = $this->getTreeArray($id, $prefix . $k . $nbsp);
            }
        }
        return $retarray;
    }

    /**
     * 得到得到树型结构,但允许多选
     * @param int $myid 获取该ID的所有下级
     * @param string $option 模板
     * @param int $selectId 选中多个，逗号分隔
     * @param string $prefix
     * @return string
     */
    public function getTreeMulti($myid, $option='<option value=@id@ @selected@>@spacer@@title@</option>', $selectId = 0, $prefix = '')
    {
        $number = 1;
        $child = $this->getChild($myid);
        if (is_array($child)) {
            $total = count($child);
            foreach ($child as $id => $value) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $prefix ? $this->icon[0] : '';
                }
                $value['spacer'] = $prefix ? $prefix . $j : '';
                $value['selected'] = $this->have($selectId, $id) ? 'selected' : '';

                $ret = preg_replace_callback('/@(\w*)@/',function ($matches) use ($value) {
                    if (!isset($matches[1])) {
                        return false;
                    }
                    if (isset($value[$matches[1]])) {
                        return $value[$matches[1]];
                    }
                    return false;
                },$option);
                $this->ret .= $ret;
                $nbsp = $this->nbsp;
                $this->getTreeMulti($id, $option, $selectId, $prefix . $k . $nbsp);
                $number++;
            }
        }
        return $this->ret;
    }

    /**
     * 结合getTreeArray，返回二维数组形式上下级关系
     * @param array  $data
     * @param string $field
     * @return array
     */
    public function getTreeList($data=[], $field='title')
    {
        $arr = [];
        foreach ($data as $k => $v) {
            $childlist = isset($v['child']) ? $v['child'] : [];
            unset($v['child']);
            $v['raw_'.$field] = $v[$field];
            $v[$field] = $v['spacer'].' '.$v[$field];
            $v['haschild'] = $childlist ? 1 : 0;
            if ($v['id']) {
                $arr[] = $v;
            }
            if ($childlist) {
                $arr = array_merge($arr, $this->getTreeList($childlist, $field));
            }
        }
        return $arr;
    }

    private function have($list, $item)
    {
        return(strpos(',,' . $list . ',', ',' . $item . ','));
    }
}
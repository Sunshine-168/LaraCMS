<?php
// +----------------------------------------------------------------------
// | 数据访问
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

namespace app\common\dao;

use app\common\model\BaseModel;

abstract class BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    abstract protected function setModel(): string;

    /**
     * 获取模型
     * @return BaseModel
     */
    protected function getModel(): BaseModel
    {
        return app()->make($this->setModel());
    }

    /**
     * 获取主键
     * @return array|string
     */
    public function getPk()
    {
        return $this->getModel()->getPk();
    }

    /**
     * 是否存在
     * @param int|string|array $where 数组条件、或只传参数1默认主键匹配传参数2其他字段匹配
     * @param string $field
     * @return bool
     */
    public function isExist($where, string $field = "*"): bool
    {
        if (!is_array($where) && empty($field)) {
            $where = [$this->getPk()=>$where];
        } else if (!is_array($where)) {
            $where = [$field=>$where];
        }
        return $this->getModel()->where($where)->count()>0;
    }

    /**
     * 搜索器查询数据
     * @param array $where
     * @return BaseModel
     */
    public function search(array $where = [])
    {
        if ($where) {
            return $this->getModel()->withSearch(array_keys($where), $where);
        } else {
            return $this->getModel();
        }
    }

    /**
     * 获取单列数组
     * @param array $where
     * @param string|null $field
     * @return array
     */
    public function column(array $where, ?string $field = null): array
    {
        return $this->getModel()->where($where)->column(is_null($field) ? $this->getPk() : $field);
    }

    /**
     * 获取值
     * @param array $where
     * @param string|null $field
     * @return mixed
     */
    public function getValue(array $where, ?string $field = null)
    {
        return $this->getModel()->where($where)->value(is_null($field) ? $this->getPk() : $field);
    }

    /**
     * 获取一条记录
     * @param string | array $where
     * @param string $field
     * @param array $with
     * @return BaseModel|array|mixed|\think\Model|null
     */
    public function getOne($where, string $field = "*", array $with = [])
    {
        if (!is_array($where)) {
            $where = [$this->getPk()=>$where];
        }
        return $this->getModel()->where($where)->with($with)->field($field)->find();
    }

    /**
     * 新增
     * @param array $data
     * @return BaseModel|\think\Model
     */
    public function create(array $data)
    {
        return $this->getModel()->create($data);
    }

    /**
     * 删除
     * @param array | string $id
     * @param string|null $field
     * @return bool
     */
    public function delete($id, ?string $field = null): bool
    {
        if (is_array($id)) {
            $where = $id;
        }  else {
            $where = [$field ?:$this->getPk()=>$id];
        }
        return $this->getModel()->where($where)->delete();
    }

    /**
     * 更新
     * @param $where
     * @param array $data
     * @return BaseModel
     */
    public function update($where, array $data)
    {
        if (!is_array($where)) {
            $where = [$this->getPk()=>$where];
        }
        return $this->getModel()->where($where)->update($data);
    }

    /**
     * 开启事务，异常回滚
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        return $this->getModel()->transaction($callback);
    }

    /**
     * 构建where条件，用于有扩展字段场景
     * @param array $field 字段
     * @param array $op 操作符
     * @param array $allowField 允许的字段
     * @return array
     */
    public function buildWhere(array $field, array $op, array $allowField = [])
    {
        $where = [];
        $raw = [];
        $bind = [];
        $index = 0;
        foreach ($field as $key=>$value) {
            if (!empty($allowField) && !in_array($key, $allowField)) {
                continue;
            }
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $key)) {
                continue;
            }
            // 操作符：=、<>、>、>=、<、<=、LIKE、NOT LIKE、BETWEEN、NOT BETWEEN、IN、NOT IN、FIND_IN_SET
            // null/not null：设置$value值为NULL和NOT NULL，空字符串：设置$value值为""、''
            $sym = $op[$key] ?? '=';
            if (!is_array($value)) {
                if (in_array(strtoupper($value), ['NULL', 'NOT NULL'])) {
                    $sym = strtoupper($value);
                }
                if (in_array($value, ['""', "''"])) {
                    $value = '';
                    $sym = '=';
                }
                $value = trim($value);
            }
            $sym = strtoupper($sym);
            switch ($sym) {
                case '=':
                case '<>':
                case 'BETWEEN':
                case 'NOT BETWEEN':
                case 'BETWEEN TIME':
                case 'NOT BETWEEN TIME':
                case 'IN':
                case 'NOT IN':
                case '> TIME':
                case '< TIME':
                case '>= TIME':
                case '<= TIME':
                    $where[] = [$key, $sym, $value];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$key, $sym, intval($value)];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                    $where[] = [$key, $sym, "%{$value}%"];
                    break;
                case 'LIKE ...%':
                case 'NOT LIKE ...%':
                    $where[] = [$key, trim(str_replace('...%', '', $sym)), "{$value}%"];
                    break;
                case 'LIKE %...':
                case 'NOT LIKE %...':
                    $where[] = [$key, trim(str_replace('%...', '', $sym)), "%{$value}"];
                    break;
                case 'FIND_IN_SET':
                case 'FIND_IN_SET_AND':
                    $value = is_array($value) ? $value : explode(',', str_replace(' ', ',', $value));
                    $findArr = array_values($value);
                    foreach ($findArr as $idx => $item) {
                        $bindName = "item_" . $index . "_" . $idx;
                        $bind[$bindName] = $item;
                        $raw[] = "FIND_IN_SET(:{$bindName}, `" . str_replace('.', '`.`', $key) . "`)";
                    }
                    $raw = implode($sym==='FIND_IN_SET_AND' ? ' AND ' : ' OR ', $raw);
                    break;
                default:
                    break;
            }
            $index++;
        }
        return [$where, $raw, $bind];
    }

    /**
     * 构建排序
     * @param $sortBy
     * @param string $sortType
     * @return array | null
     */
    public function buildOrder($sortBy, string $sortType = 'asc')
    {
        if (empty($sortBy)) {
            return null;
        }
        // 数组多个字段排序，['id'=>'desc', 'create_time'=>'asc']
        if (is_array($sortBy)) {
            $newSortBy = [];
            foreach ($sortBy as $key=>$value) {
                $key = trim($key);
                if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $key)) {
                    continue;
                }
                $newSortBy[$key] = $value=='asc' ? 'asc' : 'desc';
            }
            return $newSortBy;
        }
        $sortType = $sortType == 'asc'? 'asc' : 'desc';
        // 多个字段排序，以逗号分隔，如：id,create_time
        $sortArr = explode(',', $sortBy);
        foreach ($sortArr as &$item) {
            $item = trim($item) . ' '. $sortType;
        }
        return $sortArr;
    }
}
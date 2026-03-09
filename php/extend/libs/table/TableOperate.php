<?php
// +----------------------------------------------------------------------
// | HkCms 表格操作管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace libs\table;

use think\facade\Db;

class TableOperate
{
    protected static $instance;

    /**
     * 创建表格配置
     * @var array
     */
    protected $config = [
        'tablename' => '',
        'prefix'    => '',
        'model_id'  => 1,
        'sql_file' => []
    ];

    /**
     * 修改表的选项
     * @var array
     */
    protected $options = [];

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
        self::$instance->setConfig($options);
        return self::$instance;
    }

    public function __construct($config = [])
    {
        $this->setConfig($config);
    }

    /**
     * 初始化配置
     * @param array $config
     */
    public function setConfig($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 表是否存在，true-存在
     * @param $table
     * @return bool
     */
    public function isTableExists($table): bool
    {
        $bl = Db::query("SHOW TABLES LIKE '{$table}'");
        if (empty($bl)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 表格创建
     * @param bool $single true-单表，false-多表
     * @param bool $allowSingle true-多表下，支持独立表，false-多表
     * @return bool|string
     */
    public function createTables($single = false, $allowSingle = false)
    {
        if (empty($this->config['tablename'])) {
            return '表格名称不能为空';
        }

        $table = strtolower($this->config['tablename']);
        if ($this->isTableExists($this->config['prefix'].$table)) {
            return $this->config['prefix'].$table.',表已存在';
        }
        //if ($single==false && $allowSingle==false) { // 多表下检查副表是否存在
        //    if ($this->isTableExists($this->config['prefix'].$table.'_data')) {
        //        return $this->config['prefix'].$table.'_data'.',表已存在';
        //    }
        //}

        // 基础模板、文件是否存在
        $sqlStr = '';
        foreach ($this->config['sql_file'] as $key=>$value) {
            $temp = root_path().$value;
            $temp = str_replace('\\','/', $temp);
            if (!is_file($temp)) {
                return $temp.',文件不存在';
            }
            $sqlStr = $sqlStr.file_get_contents($temp)."\n";
        }
        $sqlStr = rtrim($sqlStr, "\n");

        //表前缀，表名，模型id替换
        $sqlSplit = str_replace(array('@prefix@', '@tablename@', '@model_id@'), array($this->config['prefix'], $table, $this->config['model_id']), $sqlStr);
        $sqlSplit = rtrim($sqlSplit, "\n");
        $sqlSplit = rtrim($sqlSplit, ';');
        try {
            $sql = explode(';',$sqlSplit);
            foreach ($sql as $key=>$value) {
                if (!empty($value)) {
                    Db::execute($value);
                }
            }
            return true;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * 修改表名称
     * @param $old
     * @param $new
     * @return bool|string
     */
    public function renameTable($old, $new)
    {
        $sql = "RENAME TABLE {$old} TO {$new};";
        try {
            Db::execute($sql);
            return true;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * 删除数据表
     * @param $table
     * @return bool|string
     */
    public function dropTable($table)
    {
        $sql = "DROP TABLE {$table} ; ";
        try {
            Db::execute($sql);
            return true;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * 设置修改的表格名称
     * @param string $name
     * @return $this
     */
    public function setTable(string $name)
    {
        $this->options['table'] = $name;
        return $this;
    }

    /**
     * 字段
     * @param $name
     * @return $this
     */
    public function setField(string $name)
    {
        $this->options['field'] = $name;
        return $this;
    }

    /**
     * 旧字段，用于修改字段名称
     * @param string $name
     * @return $this
     */
    public function setOldField(string $name)
    {
        $this->options['old_field'] = $name;
        $this->options['edit_type'] = $name==$this->options['field']?'MODIFY':'CHANGE';
        return $this;
    }

    /**
     * 设置数据类型
     * @param string $type
     * @return $this
     */
    public function setType(string $type)
    {
        switch ($type) {
            case "text":
                $this->options['type'] = 'CHAR';
                break;
            case "radio":
            case "select":
                $this->options['type'] = 'ENUM';
                break;
            case "checkbox":
            case "selects":
                $this->options['type'] = 'SET';
                break;
            case "editor":
            case "array":
            case "images":
            case "downfiles":
                $this->options['type'] = 'TEXT';
                break;
            case "number":
                $this->options['type'] = 'INT';
                break;
            case "datetime":
            case "date":
                $this->options['type'] = $type;
                break;
            default:
                $this->options['type'] = 'VARCHAR';
                break;
        }
        return $this;
    }

    /**
     * 设置字段长度
     * @param int $length
     * @return $this
     */
    public function setLength(int $length)
    {
        $this->options['length'] = $length;
        return $this;
    }

    /**
     * 设置enum数据列表
     * @param $str
     * @return $this
     */
    public function setDataList($str)
    {
        $this->options['data_list'] = $str;
        return $this;
    }

    /**
     * 设置数字类型整数位
     * @param $int
     * @return $this
     */
    public function setDecimals($int)
    {
        $this->options['decimals'] = $int;
        return $this;
    }

    /**
     * 默认值
     * @param string|int $str
     * @return $this
     */
    public function setDefault($str)
    {
        $this->options['default'] = $str;
        return $this;
    }

    /**
     * 注释
     * @param $comment
     * @return $this
     */
    public function setComment($comment)
    {
        $this->options['comment'] = $comment;
        return $this;
    }

    /**
     * @return bool|string
     */
    private function checkSql()
    {
        $isnull = 'NOT NULL';
        if ('INT' == $this->options['type']) {
            // 判断小数与长度
            if (!empty($this->options['decimals']) && $this->options['decimals']>0) {
                $this->options['type'] = 'decimal';
                $this->options['length'] = "({$this->options['length']},{$this->options['decimals']})";
            } else {
                $this->options['length'] = empty($this->options['length'])?"":"({$this->options['length']})";
            }

            $isnull = !empty($this->options['default']) ? 'NOT NULL' : 'NULL';
            //$isnull = ' NOT NULL';
            $this->options['default'] = $this->options['default']==='' ? null : $this->options['default'];
        } else if (in_array($this->options['type'],['datetime','date'])) {
            $this->options['length'] = '';
            $isnull = !empty($this->options['default']) ? 'NOT NULL' : 'NULL';
            $this->options['default'] = empty($this->options['default']) ? 'NULL' : $this->options['default'];
        } else if ('SET' == $this->options['type']) {
            $arr = json_decode($this->options['data_list'], true);
            $keys = array_keys($arr);
            $this->options['length'] = "('".implode("','", $keys)."')";

            if (empty($this->options['default'])) {
                $this->options['default'] = null;
                $isnull = null;
            } else {
                // 多选的默认值比较
                $tempArr = explode(',', $this->options['default']);
                $tempRes = array_intersect($tempArr,$keys);
                $tempStr = implode(',', $tempRes);
                if ($tempStr != $this->options['default']) {
                    return '默认值不在选项中.';
                }
            }
        } else if ('ENUM' == $this->options['type']) {
            $arr = json_decode($this->options['data_list'], true);
            $keys = array_keys($arr);
            $this->options['length'] = "('".implode("','", $keys)."')";
            if (!empty($this->options['default']) && !in_array($this->options['default'], $keys)) {
                return '默认值不在选项中.';
            } else if (empty($this->options['default'])) {
                $this->options['default'] = null;
                $isnull = null;
            }
        } else if ('TEXT' == $this->options['type']) {
            $this->options['length'] = '';
            $this->options['default'] = 'NULL';
            $isnull = 'NULL';
        } else { // char、VARCHAR
            $this->options['length'] = "({$this->options['length']})";
        }

        $this->options['isnull'] = $isnull;
        $this->options['default'] = $this->options['default']===null ? '' : "DEFAULT ".($this->options['default']=='NULL'?'null':"'{$this->options['default']}'")."";
        return true;
    }

    /**
     * 增加字段
     * @return bool|string
     */
    public function addField()
    {
        if (($bl = $this->checkSql())!==true) {
            return $bl;
        }
        $sql = "ALTER TABLE {$this->options['table']} 
                ADD COLUMN `{$this->options['field']}` 
                {$this->options['type']}{$this->options['length']} 
                {$this->options['isnull']}  
                {$this->options['default']} 
                COMMENT '{$this->options['comment']}'";

        Db::execute($sql);
        return true;
    }

    /**
     * 修改字段
     * @return bool|string
     */
    public function editField()
    {
        if (($bl = $this->checkSql())!==true) {
            return $bl;
        }

        $editType = $this->options['edit_type'] == 'MODIFY' ? "MODIFY {$this->options['field']}":"CHANGE {$this->options['old_field']} {$this->options['field']}";

        $sql = "ALTER TABLE {$this->options['table']} 
                {$editType} 
                {$this->options['type']}{$this->options['length']} 
                {$this->options['isnull']}  
                {$this->options['default']} 
                COMMENT '{$this->options['comment']}'";

        Db::execute($sql);
        return true;
    }

    /**
     * 删除字段
     * @return bool
     */
    public function deleteField()
    {
        $sql = "ALTER TABLE {$this->options['table']} DROP COLUMN `{$this->options['field']}`;";
        Db::execute($sql);
        return true;
    }
}
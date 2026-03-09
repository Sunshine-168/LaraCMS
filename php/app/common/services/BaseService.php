<?php

namespace app\common\services;

abstract class BaseService
{
    /**
     * @var object
     */
    protected $dao;

    /**
     * 调用Dao层
     * @param $name
     * @param $params
     * @return mixed
     */
    public function __call($name, $params)
    {
        return call_user_func_array([$this->dao, $name], $params);
    }
}
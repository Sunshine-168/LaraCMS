<?php

namespace addons\address\controller;

use think\addons\Controller;

class Index extends Controller
{
    public function index()
    {
        $config = $this->getConfig();
        $this->assign('config', $config);
        $this->fetch();
    }
}
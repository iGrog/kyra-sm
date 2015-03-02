<?php

namespace kyra\sm;

use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    const VERSION = '2.1';

    public $adminLayout= '//admin';
    public $staticLayout = '//susy';
    public $staticView = '//page/view';
    public $searchNamspaces = ['@app/controllers'];
    public $accessRoles = ['admin'];

    public $renderer = 'kyra\common\Json2HtmlRenderer';
}
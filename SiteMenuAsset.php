<?php

    namespace kyra\sm;

    use yii\web\AssetBundle;
    use yii\web\View;

    class SiteMenuAsset extends AssetBundle
    {
        public $sourcePath = '@vendor/kyra/sm/assets';
        public $js = [
                'jquery-ui-1.10.3.custom.min.js',
                'knockout.js',
                'knockout.mapping.js',
                'nestedSortable.js',
                'jquery.gritter.min.js'
        ];
        public $css = [
                'sitemenu.css'
        ];
        public $depends = [
                'yii\web\JqueryAsset',
        ];

        public $jsOptions = array(
                'position' => View::POS_HEAD
        );
    }
<?php

    namespace kyra\sm\controllers;

    use kyra\common\controllers\HeaderImageController;
    use kyra\sm\models\Page;
    use kyra\sm\models\SiteMenu;
    use yii\web\Controller;
    use yii\web\NotFoundHttpException;
    use Yii;

    class PageController extends HeaderImageController
    {
        public $useCustomHeaderKey = true;

        public function GetCustomHeaderImageKey($action)
        {
            return 'HEADER_IMAGE_STATIC_'.strtoupper(@$_GET['key']);
        }

        public function actionView($key)
        {
            $page = Page::findOne(['UrlKey' => $key]);
            if (empty($page)) throw new NotFoundHttpException(404);


            $this->pageTitle = $page['Title'];
            $sm = new SiteMenu(Yii::$app->db);
            $parents = $sm->GetParents($page['PageID']);
            foreach($parents as $parent)
            {
                if($parent['PageID'] != $page['PageID'])
                    $this->breadcrumbs[] = ['label' => $parent['Title'],
                        'url' => $sm->CreateUrl($parent)];
            }

            $this->breadcrumbs[] = $page['Title'];
            $this->layout = $this->module->staticLayout;
            return $this->render($this->module->staticView, ['data' => $page]);
        }

    }
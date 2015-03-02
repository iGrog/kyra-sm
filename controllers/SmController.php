<?php

    namespace kyra\sm\controllers;

    use kyra\common\BaseController;
    use kyra\sm\models\SiteMenu;
    use yii\data\ActiveDataProvider;
    use yii\debug\models\search\Base;
    use yii\filters\AccessControl;
    use yii\filters\VerbFilter;
    use yii\helpers\FileHelper;
    use yii\web\Controller;
    use kyra\sm\models\Page;
    use Yii;
    use yii\web\NotFoundHttpException;
    use yii\web\Response;
    use yii\widgets\ActiveForm;

    class SmController extends BaseController
    {
        public function behaviors()
        {
            return [
                'access' => [
                    'class' => AccessControl::className(),
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['new', 'edit', 'update-menu', 'create-new', 'menu', 'list', 'add-to-menu', 'load-sp', 'preview', 'remove'],
                            'roles' => $this->module->accessRoles,
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'remove' => ['post'],
                    ],
                ],
            ];
        }


        public function actionRemove($id)
        {
            $page = Page::findOne($id);
            $msg = 'Страница успешно удалена';
            if(empty($page))
                $msg = 'Нет такой страницы';
            else if(!$page->CanUserDelete)
                $msg = 'Эту страницу нельзя удалить';
            else
                $page->delete();

            Yii::$app->session->setFlash('admin.flash', $msg);
            return $this->redirect(['/sm/sm/list']);
        }

        public function actionNew()
        {
            $this->layout = $this->module->adminLayout;
            $model = new Page;
            $model->scenario = 'new';
            $model->load($_POST);
            if (Yii::$app->request->isAjax)
            {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }

            if (Yii::$app->request->isPost)
            {
                $renderer = Yii::createObject($this->module->renderer);

                $model->ContentHTML = $renderer->Render($model->ContentJSON);
                if ($model->save())
                {
                    Yii::$app->session->setFlash('admin.flash', 'Страница записана');
                    return $this->redirect(['edit', 'id' => $model->PageID]);
                }
            }

            $this->pageTitle = 'Новая страница';
            $this->breadcrumbs[] = $this->pageTitle;

            return $this->render('new', ['model' => $model]);
        }

        public function actionEdit($id)
        {
            $this->layout = $this->module->adminLayout;
            $model = Page::findOne(['PageID' => $id]);
            if (empty($model))
                throw new NotFoundHttpException();

            $model->scenario = 'edit';
            $model->load($_POST);
            if (Yii::$app->request->isAjax)
            {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }

            if (Yii::$app->request->isPost)
            {
                $renderer = Yii::createObject($this->module->renderer);
                $html = $renderer->Render($model->ContentJSON);
                $model->ContentHTML = $html;
                if ($model->save())
                {
                    $sm = new SiteMenu(Yii::$app->db);
                    $sm->UpdateMenuTitle($id, $model->MenuTitle);
                    Yii::$app->session->setFlash('admin.flash', 'Страница записана');
                    return $this->redirect(['edit', 'id' => $model->PageID]);
                }
            }

            $this->pageTitle = 'Редактировать страницу';
            $this->breadcrumbs[] = ['label' => 'Все страницы', 'url' => ['/sm/sm/list']];
            $this->breadcrumbs[] = $this->pageTitle;

            return $this->render('new', ['model' => $model]);
        }

        public function actionUpdateMenu()
        {
            $menu = @$_POST['menu'];
            $sm = new SiteMenu(Yii::$app->db);
            $ret = $sm->UpdateMenu($menu);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $ret;
        }

        public function actionCreateNew()
        {
            $page = new Page();
            $id = $page->CreateEmpty();
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['hasError' => !($id > 0), 'PageID' => $id];
        }

        public function actionMenu()
        {
            $this->layout = $this->module->adminLayout;

            $sm = new SiteMenu(Yii::$app->db);
            $tree = $sm->GetMenuTree();

            return $this->render('menu', ['tree' => $tree]);
        }

        public function actionList()
        {
            $this->layout = $this->module->adminLayout;
            $dp = new ActiveDataProvider([
                    'query' => Page::find(),
                    'sort' => ['defaultOrder' => ['Title' => SORT_ASC]],
                    'pagination' => ['pageSize' => 20],
            ]);

            $this->pageTitle = 'Все страницы';
            $this->breadcrumbs[] = $this->pageTitle;

            return $this->render('list', ['dp' => $dp]);
        }

        public function actionAddToMenu()
        {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $type = $_POST['Type'];
            if(!SiteMenu::IsValidType($type))
            {
                return ['hasError' => true, 'error' => 'Wrong Type'];
            }

            $sm = new SiteMenu(Yii::$app->db);
            $ret = $sm->AddMenu($_POST);
            if($ret === false)
                return ['hasError' => true, 'error' => 'Failed to add menu item'];
            else
                return ['hasError' => false, 'SMID' => $ret];

        }

        public function actionLoadSp()
        {
            $page = new Page;
            $allPages = $page->GetAllPages();

            foreach($this->module->searchNamspaces as $ns)
            {
                $path = Yii::getAlias($ns);
                $files = FileHelper::findFiles($path, ['fileTypes' => ['php']]);
                foreach($files as $file) require_once($file);
            }
            $data = get_declared_classes();
            foreach($data as $name)
            {
                if(strpos($name, 'Controller') !== false)
                {
                    $ata = class_implements($name);
                    if(in_array('kyra\sm\models\ISiteMenu', $ata))
                    {
                        $obj = new \ReflectionClass($name);
                        $obj = $obj->newInstanceWithoutConstructor();
                        $cMenu = $obj->GetSiteMenu();
//                        echo print_r($cMenu,true);
                        foreach($cMenu as $menu)
                        {
                            $menu['Type'] = SiteMenu::TYPE_CONTROLLER;
                            array_push($allPages, $menu);
                        }
                    }
                }
            }
//            exit;

            $sm = new SiteMenu(Yii::$app->db);
            $flat = $sm->GetMenuFlat();
            foreach($flat as $already)
            {
                foreach($allPages as $idx=>$p)
                {
                    if(empty($already['PageID']))
                    {
                        if($p['Type'] == SiteMenu::TYPE_CONTROLLER && $p['Url'][0] == $already['Url'])
                        {
                            unset($allPages[$idx]);
                        }

                    }
                    else
                    {
                        if($p['Type'] == SiteMenu::TYPE_STATIC && $p['PageID'] == $already['PageID'])
                            unset($allPages[$idx]);
                    }
                }
            }

            $allPages = array_values($allPages);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $allPages;
        }

        public function actionPreview($id)
        {
            var_dump($_POST);
        }
    }
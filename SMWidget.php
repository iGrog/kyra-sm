<?php

    namespace kyra\sm;

    use kyra\sm\models\SiteMenu;
    use yii\base\InvalidConfigException;
    use yii\base\Widget;
    use Yii;
    use yii\bootstrap\Nav;
    use yii\helpers\ArrayHelper;
    use yii\helpers\Html;

    class SMWidget extends Widget
    {
        public $parentClassIfHaveChildren;
        public $emptyItemTemplate = '<span>{label}</span>';
        public $emptyItemLiClass = '';
        public $secondLevelUlClass = '';
        public $thirdLevelUlClass = '';
        public $levels = [];
        public $extraMenuClass = '';
        public $extraMenuFunction = '';
        public $activateParents = true;
        public $encodeLabels = false;
        protected $items = [];
        public $options = [];
        public $route;
        public $params;
        public $activateItems = true;

        public function init()
        {
            parent::init();
            if ($this->route === null && Yii::$app->controller !== null)
                $this->route = Yii::$app->controller->getRoute();

            if ($this->params === null)
                $this->params = Yii::$app->request->getQueryParams();
        }


        private function LoadItems()
        {
            $sm = new SiteMenu(Yii::$app->db);
            $tree = $sm->GetMenuTree();
            $items = $sm->ConvertToMenuItems($tree);
            return $items;
        }

        public function run()
        {
            $this->items = $this->LoadItems();
            echo $this->renderItems($this->items, 0);
        }

        public function renderItems($list, $level=0)
        {
            if(empty($list)) return '';
            $items = [];
            foreach ($list as $i => $item)
            {
                if (isset($item['visible']) && !$item['visible'])
                {
                    unset($items[$i]);
                    continue;
                }
                $items[] = $this->renderItem($item, $level+1);
            }

            $options = $level == 0 ? $this->options : [];
            return Html::tag('ul', implode("\n", $items), $options);
        }

        public function renderItem($item, $level)
        {
            if (is_string($item))  return $item;
            if (!isset($item['label'])) {
                throw new InvalidConfigException("The 'label' option is required.");
            }
            $encodeLabel = isset($item['encode']) ? $item['encode'] : $this->encodeLabels;
            $label = $encodeLabel ? Html::encode($item['label']) : $item['label'];
            $options = ArrayHelper::getValue($item, 'options', []);
            $items = ArrayHelper::getValue($item, 'items');
            $url = ArrayHelper::getValue($item, 'url', '#');
            $linkOptions = ArrayHelper::getValue($item, 'linkOptions', []);

            if (isset($item['active']))
                $active = ArrayHelper::remove($item, 'active', false);
            else
                $active = $this->isItemActive($item);


            if ($items !== null)
            {
                if (is_array($items))
                {
                    if(count($items) > 0)
                    {
                        $label .= ' ' . Html::tag('b', '', ['class' => 'caret']);
                    }
                    if ($this->activateItems)
                    {
                        $items = $this->isChildActive($items, $active);
                    }
                    $items = $this->renderItems($items, $level+1);
                }
            }

            if ($this->activateItems && $active) Html::addCssClass($options, 'active');

            return Html::tag('li', Html::a($label, $url, $linkOptions) . $items, $options);
        }

        protected function isItemActive($item)
        {
            if (isset($item['url']) && is_array($item['url']) && isset($item['url'][0]))
            {
                $route = $item['url'][0];
                if ($route[0] !== '/' && Yii::$app->controller)
                {
                    $route = Yii::$app->controller->module->getUniqueId() . '/' . $route;
                }
                if (ltrim($route, '/') !== $this->route)
                {
                    return false;
                }
                unset($item['url']['#']);
                if (count($item['url']) > 1) {
                    foreach (array_splice($item['url'], 1) as $name => $value)
                    {
                        if ($value !== null && (!isset($this->params[$name]) || $this->params[$name] != $value))
                        {
                            return false;
                        }
                    }
                }

                return true;
            }

            return false;
        }

        protected function isChildActive($items, &$active)
        {
            foreach ($items as $i => $child) {
                if (ArrayHelper::remove($items[$i], 'active', false) || $this->isItemActive($child)) {
                    Html::addCssClass($items[$i]['options'], 'active');
                    if ($this->activateParents) {
                        $active = true;
                    }
                }
            }
            return $items;
        }




    }
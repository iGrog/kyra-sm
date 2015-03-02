<?php
    use yii\grid\GridView;
    use yii\helpers\Url;

    echo GridView::widget([
            'dataProvider' => $dp,
            'columns' => [
                    'PageID',
                    'Title',
                    'MenuTitle',
                    'UrlKey',
                    ['class' => 'yii\grid\ActionColumn',
                     'buttons' =>
                         ['view' => function ($url, $model, $key)
                         {
                             return '<a href="'.Url::to(['/sm/page/view', 'key' => $model->UrlKey]).'"><span class="glyphicon glyphicon-eye-open"></span></a>';
                         },
                         'update' => function ($url, $model, $key)
                         {
                             return '<a href="'.Url::to(['/sm/sm/edit', 'id' => $model->PageID]).'"><span class="glyphicon glyphicon-pencil"></span></a>';
                         },
                         'delete' => function ($url, $model, $key)
                         {
                             if(isset($model->CanUserDelete) && $model->CanUserDelete)
                                return '<a href="'.Url::to(['/sm/sm/remove', 'id' => $model->PageID]).'"
                                data-method="post" data-confirm="Удаление нельзя будет отменить. Вы уверены?"
                                data-pjax="1"><span class="glyphicon glyphicon-trash"></span></a>';
                             else
                                 return '';
                         },
                         ]
                    ],
            ],
    ]);
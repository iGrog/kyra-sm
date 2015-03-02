<?php

namespace kyra\sm\models;

use yii\db\ActiveRecord;
use Yii;

class Page extends ActiveRecord
{
    public static function tableName()
    {
        return 'page';
    }

    public function scenarios()
    {
        return [
            'new' => ['Title', 'MenuTitle', 'UrlKey', 'ContentJSON'],
            'edit' => ['Title', 'MenuTitle', 'UrlKey', 'ContentJSON', 'PageID']
        ];
    }

    public function rules()
    {
        return [
            [['Title', 'MenuTitle', 'UrlKey', 'ContentJSON'], 'required', 'on' => 'new'],
            [['PageID', 'Title', 'MenuTitle', 'UrlKey', 'ContentJSON'], 'required', 'on' => 'edit'],
            [['UrlKey'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'Title' => 'Заголовок',
            'MenuTitle' => 'Название в меню',
            'UrlKey' => 'Имя в URL',
            'ContentJSON' => 'Содержимое'
        ];
    }

    public function GetAllPages()
    {
        $sql = 'SELECT PageID, Title, UrlKey, '.SiteMenu::TYPE_STATIC.' AS Type FROM page ORDER BY Title';
        $rows = Yii::$app->db->createCommand($sql)->queryAll();
        return $rows;
    }

    public function CreateEmpty()
    {
        $p = new Page();
        $p->save(false);
        return $p->PageID;
    }
}
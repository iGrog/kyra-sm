<?php

use yii\db\Schema;
use yii\db\Migration;

class m140726_053008_sitemenu extends Migration
{
    public function up()
    {
        $this->createTable('sitemenu', [
            'SMID' => 'pk',
            'ParentID' => Schema::TYPE_INTEGER.' NULL',
            'PageID' => Schema::TYPE_INTEGER.' NULL',
	    'ObjectID' => Schema::TYPE_INTEGER.' NULL',
            'MenuType' => Schema::TYPE_INTEGER.' NULL',
            'Title' => Schema::TYPE_STRING . ' NOT NULL',
            'Url' => Schema::TYPE_STRING,
            'SortOrder' => Schema::TYPE_INTEGER,
            'Params' => Schema::TYPE_TEXT.' NULL'
        ]);
    }

    public function down()
    {
        echo "m140726_053008_sitemenu cannot be reverted.\n";
        return false;
    }
}

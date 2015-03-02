<?php

use yii\db\Schema;
use yii\db\Migration;

// yii migrate/up --migrationPath=@vendor/kyra/sm/migrations

class m140708_122831_create_page_table extends Migration
{
    public function up()
    {
        $this->createTable('page', [
            'PageID' => 'pk',
            'Title' => Schema::TYPE_STRING . ' NOT NULL',
            'MenuTitle' => Schema::TYPE_STRING.' NOT NULL',
            'UrlKey' => Schema::TYPE_STRING.' NULL',
            'ContentJSON' => Schema::TYPE_TEXT,
            'ContentHTML' => Schema::TYPE_TEXT,
        ]);
    }

    public function down()
    {
        $this->dropTable('page');
    }

}

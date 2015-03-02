<?php

use yii\db\Schema;
use yii\db\Migration;

class m141005_060406_CanUserDelete extends Migration
{
    public function up()
    {
        $this->addColumn('page', 'CanUserDelete', 'SMALLINT DEFAULT 1');
    }

    public function down()
    {
        echo "m141005_060406_CanUserDelete cannot be reverted.\n";

        return false;
    }
}

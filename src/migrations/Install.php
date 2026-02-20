<?php

namespace homm\hommformviewer\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable(
            '{{%homm_formviewer_submissions}}',
            [
                'id' => $this->primaryKey(),
                'formId' => $this->string(),
                'payload' => $this->json()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
            ]
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%homm_formviewer_submissions}}');

        return true;
    }
}

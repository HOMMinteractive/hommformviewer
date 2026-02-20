<?php

namespace homm\hommformviewer\migrations;

use Craft;
use craft\db\Migration;

/**
 * m260217_132119_add_form_submissions_table migration.
 */
class m260217_132119_add_form_submissions_table extends Migration
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

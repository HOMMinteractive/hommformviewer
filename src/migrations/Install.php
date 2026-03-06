<?php

namespace homm\hommform\migrations;

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
            '{{%homm_form_submissions}}',
            [
                'id' => $this->primaryKey(),
                'formId' => $this->string(),
                'receivers' => $this->text(),
                'replyto' => $this->text(),
                'subject' => $this->text(),
                'payload' => $this->json()->notNull(),
                'ip' => $this->string(),
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
        $this->dropTableIfExists('{{%homm_form_submissions}}');

        return true;
    }
}

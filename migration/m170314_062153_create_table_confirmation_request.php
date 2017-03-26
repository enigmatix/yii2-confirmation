<?php

use yii\db\Migration;

/**
 * Handles the creation for table `{{%confirmation_request}}`.
 */
class m170314_062153_create_table_confirmation_request extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%confirmation_request}}', [

            'id' => $this->primaryKey()->unsigned()->notNull(),
            'model' => $this->string(255),
            'object_id' => $this->integer(11)->unsigned(),
            'object' => $this->text(),
            'release_token' => $this->string(255),
            'created_at' => $this->integer(11),
            'updated_at' => $this->integer(11),
            'values' => $this->text(),
            'created_by' => $this->integer(11)->unsigned(),
            'updated_by' => $this->integer(11)->unsigned(),

        ]);
 
        // creates index for column `created_by`
        $this->createIndex(
            'confirmation_request_ibfk_2',
            '{{%confirmation_request}}',
            'created_by'
        );

        // add foreign key for table `user`
        $this->addForeignKey(
            'confirmation_request_ibfk_2',
            '{{%confirmation_request}}',
            'created_by',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // creates index for column `updated_by`
        $this->createIndex(
            'confirmation_request_ibfk_3',
            '{{%confirmation_request}}',
            'updated_by'
        );

        // add foreign key for table `user`
        $this->addForeignKey(
            'confirmation_request_ibfk_3',
            '{{%confirmation_request}}',
            'updated_by',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        // drops foreign key for table `user`
        $this->dropForeignKey(
            'confirmation_request_ibfk_2',
            '{{%confirmation_request}}'
        );

        // drops index for column `created_by`
        $this->dropIndex(
            'confirmation_request_ibfk_2',
            '{{%confirmation_request}}'
        );

        // drops foreign key for table `user`
        $this->dropForeignKey(
            'confirmation_request_ibfk_3',
            '{{%confirmation_request}}'
        );

        // drops index for column `updated_by`
        $this->dropIndex(
            'confirmation_request_ibfk_3',
            '{{%confirmation_request}}'
        );

        $this->dropTable('{{%confirmation_request}}');
    }
}

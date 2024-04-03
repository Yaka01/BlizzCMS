<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNewsComments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'auto_increment' => true,
                'constraint' => 20,
                'unsigned' => true
            ],
            'news_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true
            ],
            'user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true
            ],
            'comment_content' => [
                'type' => 'MEDIUMTEXT'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('news_id');
        $this->forge->addKey('user_id');

        $this->forge->addForeignKey('news_id', 'news', 'id', 'CASCADE', 'CASCADE', 'news_comments_news_id_foreign');

        $this->forge->createTable('news_comments', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropForeignKey('news_comments', 'news_comments_news_id_foreign');
        $this->forge->dropTable('news_comments');
    }
}

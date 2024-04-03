<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


class CreateNews extends Migration
{

    /**
     * @inheritDoc
     */
    public function up()
    {
        $model = new \App\Models\News();
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'summary' => [
                'type' => 'TEXT'
            ],
            'content' => [
                'type' => 'MEDIUMTEXT',
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'image' => [
                'type' => 'TEXT'
            ],
            'comments' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 0
            ],
            'views' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 0
            ],
            'meta_description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'default' => ''
            ],
            'meta_robots' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'default' => 'index, follow'
            ],
            'discuss' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'unsigned' => true,
                'default' => 0
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
        $this->forge->createTable('news', false, ['ENGINE' => 'InnoDB']);

        $model->insert([
            'title' => 'Hello world!',
            'summary' => 'Welcome to your new website with BlizzCMS. To edit or delete this first news article sign in with your account and go to the admin panel.',
            'content' => '<p>Welcome to your new website with <strong>BlizzCMS</strong>. To edit or delete this first news article sign in with your account and go to the admin panel. Don\'t forget that if you have any problems you can open an <a href="https://github.com/WoW-CMS/BlizzCMS/issues">issue</a> in our repository or contact us in our <a href="https://discord.wow-cms.com">discord</a>.</p>', 'slug' => 'hello-world',
            'image' => '2024/03/410943a905e887277d0d803bdee2e2f5.jpg',
            'meta_robots' => 'index, follow',
            'discuss' => 1
        ]);
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->forge->dropTable('news');
    }
}

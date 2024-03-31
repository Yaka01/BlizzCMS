<?php

namespace App\Database\Migrations;

use App\Models\Slide as SlideModel;
use CodeIgniter\Database\Migration;

class CreateSlides extends Migration
{
    public function up()
    {
        $model = new SlideModel();

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'auto_increment' => true,
                'unsigned' => true,
                'constraint' => 20,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'description' => [
                'type' => 'TEXT'
            ],
            'type' => [
                'type' => 'ENUM("image", "video", "iframe")',
                'default' => 'image'
            ],
            'path' => [
                'type' => 'TEXT'
            ],
            'sort' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 0
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('slides', false, ['ENGINE' => 'InnoDB']);

        $model->insertBatch([
            ['title' => 'BlizzCMS', 'description' => 'Check our constant updates!', 'type' => 'image', 'path' => '2024/03/95897962ade4959153b9d29b2528947b.jpg', 'sort' => 1],
            ['title' => 'Vote Now', 'description' => 'Each vote will be rewarded!', 'type' => 'image', 'path' => '2024/03/3e0af6fc9ce5a60ca50dba3869cbc716.jpg', 'sort' => 2]
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('slides');
    }
}

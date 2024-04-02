<?php

use CodeIgniter\CodeIgniter;

/**
 * Returns 
 */
function config_item(string $item)
{
    $db = \Config\Database::connect();
    if (!$db->tableExists('settings')) {
        return null;
    }
    $settingsModel = new \App\Models\Setting();
    return isset($settingsModel->find($item)->value) ? $settingsModel->find($item)->value : null;
}

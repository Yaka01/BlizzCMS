<?php

namespace App\Controllers;

use App\Controllers\BaseController;

/**
 * BlizzCMS
 *
 * @author WoW-CMS
 * @copyright Copyright (c) 2019 - 2023, WoW-CMS (https://wow-cms.com)
 * @license https://opensource.org/licenses/MIT MIT License
 */

class News extends BaseController
{
    public function index()
    {
        $inputPage = $this->request->getVar('page') ?? 1;
        $page = ctype_digit((string) $inputPage) ? (int) $inputPage : 1;

        $newsModel = new \App\Models\News();

        $perPage = configItem('articles_per_page') ?? 25;

        $data = [
            'articles' => $newsModel->paginate($perPage),
            'pagination' => $newsModel->pager->makeLinks($page, $perPage, $newsModel->countAll(), 'foundation_full'),
        ];

        $this->template->setTitle(lang('General.news'), configItem('app_name'));
        return $this->template->build('articles', $data);
    }
}

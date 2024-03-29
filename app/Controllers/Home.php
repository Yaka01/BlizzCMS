<?php

namespace App\Controllers;

use App\Models\News;

class Home extends BaseController
{
    public function index(): string
    {
        $newsModel = model(News::class);

        $data = [
            'articles' => $newsModel->findAll(5),
            'realms' => null,
        ];

        $this->template->setTitle('BlizzCMS');
        $this->template->setSeoMetas([
            'description'   => 'BlizzCMS is a content management system for World of Warcraft private servers.',
            'robots'        => 'index, follow',
            'url'           => current_url(),
            'type'          => 'website',
        ]);

        return $this->template->build('home', $data);
    }

    /**
     * Change site language
     * 
     * @param string $locale
     * @return void
     */
    public function lang($locale = null)
    {
        $this->multilanguage->setLanguage($locale);


        $agent = $this->request->getUserAgent();
        return redirect()->to($agent->getReferrer());
    }
}

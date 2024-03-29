<?php

/**
 * Library for handling the template
 *
 * 
 * @author Philip Sturgeon
 * @author WoW-CMS
 * @copyright Copyright (c) 2011 - 2019, Philip Sturgeon (https://phil.tech)
 * @copyright Copyright (c) 2019 - 2024, WoW-CMS (https://wow-cms.com)
 * @link      https://wow-cms.com/
 * @license http://dbad-license.org DBAD License
 * @license https://opensource.org/licenses/MIT MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace App\Libraries;

use App\Models\Setting;
use \Config\Database;
use \CodeIgniter\Router\Router;

class Template
{
    /**
     * The controller name.
     * 
     * @var string
     */
    private $_controller = '';

    /**
     * The method name.
     * 
     * @var string
     */
    private $_method = '';

    /**
     * The theme to use for rendering.
     * 
     * @var string
     */
    private $_theme = null;

    /**
     * The path to the theme.
     * 
     * @var string
     */
    private $_themePath = null;

    /**
     * Flag indicating whether to use a layout.
     * 
     * @var bool
     */
    private $_layout = false;

    /**
     * The subdirectory for layouts and partials.
     * 
     * @var string
     */
    private $_layoutSubdir = '';

    /**
     * The title of the page.
     * 
     * @var string
     */
    private $_title = '';

    /**
     * Array of head tags.
     * 
     * @var array
     */
    private $_headTags = [];

    /**
     * Array of body tags.
     * 
     * @var array
     */
    private $_bodyTags = [];

    /**
     * Array of partials to include.
     * 
     * @var array
     */
    private $_partials = [];

    /**
     * Array of breadcrumbs items.
     * 
     * @var array
     */
    private $_breadcrumbs = [];

    /**
     * The separator to use for the title.
     * 
     * @var string
     */
    private $_titleSeparator = ' — ';

    /**
     * Flag indicating whether parsing is enabled.
     * 
     * @var bool
     */
    private $_parserEnabled = true;

    /**
     * The parser to use.
     * 
     * @var null|\CodeIgniter\View\Parser
     */
    private $_parser = null;

    /**
     * Flag indicating whether body parsing is enabled.
     * 
     * @var bool
     */
    private $_parserBodyEnabled = true;

    /**
     * Array of theme locations.
     * 
     * @var array
     */
    private $_themeLocations = [];

    /**
     * Flag indicating whether the template is for mobile.
     * 
     * @var bool
     */
    private $_isMobile = false;

    /**
     * The cache lifetime in minutes.
     * 
     * @var int
     */
    private $cacheLifetime = 0;

    /**
     * Array of additional data to pass to the view.
     * 
     * @var array
     */
    private $_data = [];

    /**
     * Response object.
     * 
     * @var \CodeIgniter\HTTP\ResponseInterface
     */
    private $_response;

    /**
     * Site name for SEO.
     * 
     * @var string
     */
    private $_siteName = '';

    /**
     * Flag indicating if exist seo metas.
     * 
     * @var bool
     */
    private $_seoMetas = false;


    /**
     * Flag indicating if exist seo og metas.
     * 
     * @var bool
     */
    private $_seoOgMetas = false;

    public function __construct(array $config = [])
    {
        $this->_response = \Config\Services::response();
        if (!empty($config)) {
            $this->initialize($config);
        }

        log_message('info', 'Template Class Initialized');
    }

    /**
     * Initialize the template library with
     * preference to passed with configs.
     * 
     * @param array $config
     * @return void
     * 
     */
    private function initialize(array $config)
    {
        foreach ($config as $key => $val) {
            $this->{'_' . $key} = $val;
        }

        // No locations set in config?
        if ($this->_themeLocations === []) {
            // Let's use this obvious default
            $this->_themeLocations = [APPPATH . 'Themes/'];
        }

        $db = Database::connect();

        if ($db->tableExists('settings')) {
            $settings_model = new Setting();

            $this->_theme = $settings_model->find('app_theme')->value;
            $this->_siteName = $settings_model->find('app_name')->value;
            $this->_seoMetas = $settings_model->find('seo_tags')->value;
            $this->_seoOgMetas = $settings_model->find('seo_og_tags')->value;
        }

        if ($this->_theme) {
            $this->setTheme($this->_theme);
        }

        if ($this->_parserEnabled === true) {
            $this->_parser = \Config\Services::parser();
        }

        $router = \Config\Services::router();

        $this->_controller = $router->controllerName();
        $this->_method = $router->methodName();

        $request = \Config\Services::request();
        $agent = $request->getUserAgent();

        $this->_isMobile = $agent->isMobile();
    }

    /**
     * Build the entire HTML output combining partials,
     * layouts and views.
     * 
     * @param string $view
     * @param array $data
     * @param bool $return
     * @return string
     */
    public function build(string $view, array $data = [], bool $return = false)
    {
        // Set whatever values are given. These will be available to all view files.
        is_array($data) or $data = (array) $data;

        // Merge in what we already have with the specific data.
        $this->_data = array_merge($this->_data, $data);
        unset($data);

        if (empty($this->_title)) {
            $this->_title = $this->_guessTitle();
        }

        //Output template variables to the template
        $template['title'] = $this->_title;
        $template['site_name'] = $this->_siteName;
        $template['breadcrumbs'] = $this->_breadcrumbs;

        $template['head_tags'] = empty($this->_headTags) ? '' : implode("    ", $this->_headTags);
        $template['body_tags'] = empty($this->_bodyTags) ? '' : implode("    ", $this->_bodyTags);

        $template['location']   = base_url('app/Themes/' . $this->getTheme() . '/');
        $template['assets']     = base_url('assets/');
        $template['uploads']    = base_url('uploads/');

        $template['partials'] = [];

        // Assing by reference, as all loaded views will need access to partials.
        $this->_data['template'] = &$template;

        foreach ($this->_partials as $name => $partial) {
            // We can only work with data arrays
            is_array($partial['data']) or $partial['data'] = (array) $partial['data'];

            // If it uses a view, load it
            if (isset($partial['view'])) {
                $template['partials'][$name] = $this->_findView($partial['view'], $partial['data']);
            } else {
                if ($this->_parserEnabled === true) {
                    $partial['string'] = $this->_parser->setData($this->_data + $partial['data'])->renderString($partial['string']);
                }

                $template['partials'][$name] = $partial['string'];
            }
        }

        // Disable sodding IE7's constant caching
        $this->_response->setHeader('Last-Modified',  gmdate('D, d M Y H:i:s', time()) . ' GMT');
        $this->_response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, no-transform, max-age=0, post-check=0, pre-check=0');
        $this->_response->setHeader('Pragma', 'no-cache');

        // Let CI do the caching instead of the browser
        $this->_response->setCache(['max-age' => $this->cacheLifetime]);

        // Test to see if this file
        $this->_body = $this->_findView($view, [], $this->_parserBodyEnabled);

        // Want this file wrapped with a layout file?
        if ($this->_layout) {
            $template['body'] = $this->_body;

            // Find the main body an 3rd param means parse if its a theme view (only if parser is enabled)
            $this->_body = $this->_loadView('layouts/' . $this->_layout, $this->_data, true, $this->_findViewFolder());
        }

        return $this->_body;
    }

    /**
     * Find layout files, if they exist.
     * They could be mobile or web specific.
     * 
     * @return string
     */
    private function _findViewFolder()
    {
        $viewFolder = APPPATH . 'Views/';

        // Using a theme? Put the theme path in before the view folder
        if (!empty($this->_themePath)) {
            $viewFolder = $this->_themePath . 'views/';
        }

        // Would they like the mobile version?
        if ($this->_isMobile === true && is_dir($viewFolder . 'mobile/')) {
            $viewFolder .= 'mobile/';
        } elseif (is_dir($viewFolder . 'web/')) {
            $viewFolder .= 'web/';
        }

        // Things like views/admin/web/view admin = subdir
        if ($this->_layoutSubdir) {
            $viewFolder .= $this->_layoutSubdir . '/';
        }

        return $viewFolder;
    }

    /**
     * Find a view file.
     * A module view file can be overriden in a theme
     * 
     * @param string $view
     * @param array $data
     * @param bool $parseView
     * @return string
     */
    private function _findView(string $view, array $data = [], bool $parseView = true)
    {
        // Only bother looking in themes if there is a theme
        if (!empty($this->_theme)) {
            foreach ($this->_themeLocations as $location) {
                $themeViews = [
                    $this->_theme . '/views/' . $view
                ];

                foreach ($themeViews as $themeView) {
                    if (file_exists($location . $themeView . $this->_ext($themeView))) {
                        return $this->_loadView($themeView, $this->_data + $data, $parseView, $location);
                    }
                }
            }
        }

        return $this->_loadView($view, $this->_data + $data, $parseView);
    }

    /**
     * Load a view file.
     * 
     * @param string $view
     * @param array $data
     * @param bool $parseView
     * @return string
     */
    private function _loadView(string $view, array $data, bool $parseView = true, string $overrideViewPath = null)
    {
        // Sevear hackery to load views from custom places AND maintain compatibility with modular extensions.
        if ($overrideViewPath !== null) {
            if ($this->_parserEnabled === true && $parseView === true) {
                // Load content and pass through the parser
                $content = $this->_parser->setData($data)->render($view);
            } else {
                $content = view($view, $data);
            }
        } else {
            // Grab the content of the view (parser or loaded)
            $content = ($this->_parserEnabled === true && $parseView === true)
                ? $this->_parser->setData($data)->render($view)
                : view($view, $data);
        }

        return $content;
    }

    /**
     * Guess the title of the page.
     * 
     * @return string
     */
    private function _guessTitle()
    {
        helper('inflector');

        $titleParts = [];

        // If the method is something other than index, we should add that to the title.
        if ($this->_method !== 'index') {
            $titleParts[] = $this->_method;
        }

        // Make sure controller name is not the same as the method name
        if (!in_array($this->_controller, $titleParts)) {
            $titleParts[] = $this->_controller;
        }

        $title = humanize(implode($this->_titleSeparator, $titleParts));

        return $title;
    }

    /**
     * Magic get function to get data.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }

    /**
     * Magic set function to set data.
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->_data[$name] = $value;
    }

    /**
     * Set data using a chainable method.
     * Provide two strings or an array of data.
     * 
     * @param string|array $name
     * @param mixed $value
     * @return void
     */
    public function set($name, $value = null)
    {
        if (is_array($name) || is_object($name)) {
            foreach ($name as $key => $value) {
                $this->_data[$key] = $value;
            }
        } else {
            $this->_data[$name] = $value;
        }
    }


    /**
     * Set the title of the page.
     * 
     * @return void
     */
    public function setTitle()
    {
        // If we have some segments passed
        if (func_num_args() >= 1) {
            $titleSegments = func_get_args();
            $this->_title = implode($this->_titleSeparator, $titleSegments);
        }
    }

    /**
     * Get the current theme
     * 
     * @return string
     */
    public function getTheme()
    {
        return $this->_theme;
    }

    /**
     * Set a theme for the template library to use.
     * 
     * @param string $theme
     * @return void
     */
    public function setTheme(string $theme = null)
    {
        $this->_theme = $theme;

        foreach ($this->_themeLocations as $location) {
            if ($this->_theme && file_exists($location . $this->_theme)) {
                $this->_themePath = rtrim($location . $this->_theme . '/');
                break;
            }
        }
    }

    /**
     * Get the current theme path
     * 
     * @return string
     */
    public function getThemePath()
    {
        return $this->_themePath;
    }

    /**
     * Set a theme layout for the template library to use.
     * 
     * @param string $layout
     * @param string $subdir
     * @return void
     */
    public function setLayout(string $layout, string $subdir = '')
    {
        $this->_layout = $layout;

        $subdir and $this->_layoutSubdir = $subdir;
    }

    /**
     * Set a view partial
     * 
     * @param string $name
     * @param string $view
     * @param array $data
     * @return void
     */
    public function setPartial(string $name, string $view, array $data = [])
    {
        $this->_partials[$name] = ['view' => $view, 'data' => $data];
    }

    /**
     * Inject a partial string
     * 
     * @param string $name
     * @param string $string
     * @param array $data
     * @return void
     */
    public function setPartialString(string $name, string $string, array $data = [])
    {
        $this->_partials[$name] = ['string' => $string, 'data' => $data];
    }

    /**
     * Helps build custom breadcrumbs trails.
     * 
     * @param string $name
     * @param string $uri
     * @return void
     */
    public function setBreadcrumbs(string $name, string $uri = '')
    {
        $this->_breadcrumbs[] = ['name' => $name, 'uri' => $uri];
    }

    /**
     * Set a the cache lifetime in minutes.
     * 
     * @param int $minutes
     * @return void
     */
    public function setCache(int $minutes)
    {
        $this->cacheLifetime = $minutes;
    }

    /**
     * Enable parser
     * 
     * @return void
     */
    public function enableParser()
    {
        $this->_parserEnabled = true;
    }

    /**
     * Enable parser for body
     * 
     * @return void
     */
    public function enableParserBody()
    {
        $this->_parserBodyEnabled = true;
    }

    /**
     * List the locations where themes are stored.
     * 
     * @return array
     */
    public function getThemeLocations()
    {
        return $this->_themeLocations;
    }

    /**
     * Set another location for themes to be looked in
     * 
     * @param string $location
     * @return void
     */
    public function addThemeLocation(string $location)
    {
        $this->_themeLocations[] = $location;
    }

    /**
     * Check if a theme exists
     * 
     * @param string $theme
     * @return bool
     */
    public function themeExists(string $theme = null)
    {
        $theme or $theme = $this->_theme;

        foreach ($this->_themeLocations as $location) {
            if (is_dir($location . $theme)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all current layouts
     * 
     * @return array
     */
    public function getLayouts()
    {
        $layouts = [];

        foreach (glob($this->_findViewFolder() . 'layouts/*.*') as $layout) {
            $layouts[] = pathinfo($layout, PATHINFO_BASENAME);
        }

        return $layouts;
    }

    /**
     * Get all layouts of theme
     * 
     * @param string $theme
     * @return array
     */
    public function getLayoutsByTheme(string $theme = null)
    {
        $theme or $theme = $this->_theme;

        $layouts = [];

        foreach ($this->_theme_locations as $location) {
            // Get special web layouts
            if (is_dir($location . $theme . '/views/web/layouts/')) {
                foreach (glob($location . $theme . '/views/web/layouts/*.*') as $layout) {
                    $layouts[] = pathinfo($layout, PATHINFO_BASENAME);
                }
                break;
            }

            // So there are no web layouts, assume all layouts are web layouts
            if (is_dir($location . $theme . '/views/layouts/')) {
                foreach (glob($location . $theme . '/views/layouts/*.*') as $layout) {
                    $layouts[] = pathinfo($layout, PATHINFO_BASENAME);
                }
                break;
            }
        }

        return $layouts;
    }

    /**
     * Check if a theme layout exists
     * 
     * @param string $layout
     * @return bool
     */
    public function layoutExists(string $layout)
    {
        // If there is a theme, check it exists in there
        if (!empty($this->_theme) && in_array($layout, $this->getLayoutsByTheme())) {
            return true;
        }

        // Otherwise look in the normal places
        return file_exists($this->_findViewFolder() . 'layouts/' . $layout . self::_ext($layout));
    }

    /**
     * Load views form theme paths if they exist.
     * 
     * @param string $view
     * @param array $data
     * @return string
     */
    public function loadView(string $view, array $data = [])
    {
        return $this->_findView($view, $data);
    }

    /**
     * Add head tags before all head data.
     * 
     * @param string $line
     * @return void
     */
    public function prependMetadata(string $line)
    {
        array_unshift($this->_headTags, $line);
    }

    /**
     * Add head tags after all head data
     * 
     * @param string $line
     * @return void
     */
    public function appendMetadata(string $line)
    {
        $this->_headTags[] = $line;
    }

    /**
     * Include a meta data in the header.
     * 
     * @param string $name
     * @param string $content
     * @param string $type
     * @return void
     */
    public function addMetadata(string $name, string $content, string $type = 'name')
    {
        $type = !in_array($type, ['name', 'property'], true) ? 'name' : $type;
        $this->_headTags[] = '<meta ' . $type . '="' . $name . '" content="' . $content . '">' . PHP_EOL;
    }

    /**
     * Include a CSS file in the header
     * 
     * @param string $href
     * @return void
     */
    public function addCss(string $file)
    {
        $this->_headTags[] = '<link rel="stylesheet" type="text/css" href="' . $file . '">' . PHP_EOL;
    }

    /**
     * Include a JS file in the header/body
     * 
     * @param string|array $src
     * @param string $position
     * @return void
     */
    public function addJs($src, string $position = 'head')
    {
        $src = is_string($src) ? ['src' => $src] : $src;
        $position = $position === 'body' ? '_bodyTags' : '_headTags';

        $attributes = [];

        foreach ($src as $key => $val) {
            $name = preg_replace('/[^a-z0-9-]+/i', '', $key);
            $value = $val === null ? '' : '=' . strip_tags($val) . '"';

            $attributes[] = $name . $value;
        }

        $this->$position[] = '<script ' . implode(' ', $attributes) . '></script>' . PHP_EOL;
    }

    /**
     * Set SEO metas of the page
     * 
     * @param array $metas
     * @return void
     */
    public function setSeoMetas($metas)
    {
        $sitename = $this->_siteName;

        if ($this->_seoMetas) {
            if (array_key_exists('description', $metas)) {
                $this->addMetadata('description', $metas['description']);
            }

            if (array_key_exists('robots', $metas)) {
                $this->addMetadata('robots', $metas['robots']);
            }
        }

        if ($this->_seoOgMetas) {
            $this->addMetadata('og:site_name', $sitename, 'property');
            $this->addMetadata('og:title', array_key_exists('title', $metas) && $metas['title'] !== $sitename ? $metas['title'] . $this->_titleSeparator . $sitename : $sitename, 'property');

            if (array_key_exists('type', $metas)) {
                $this->addMetadata('og:type', $metas['type'], 'property');
            }

            if (array_key_exists('description', $metas)) {
                $this->addMetadata('og:description', $metas['description'], 'property');
            }

            if (array_key_exists('url', $metas)) {
                $this->addMetadata('og:url', $metas['url'], 'property');
            }

            if (array_key_exists('image', $metas)) {
                $this->addMetadata('og:image', $metas['image'], 'property');
            }
        }
    }

    /**
     * Get the extension of a file.
     * 
     * @param string $file
     * @return string
     */
    private function _ext(string $file)
    {
        return pathinfo($file, PATHINFO_EXTENSION) ? '' : '.php';
    }
}

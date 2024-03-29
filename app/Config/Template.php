<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Template extends BaseConfig
{
    /**
     * Parser Enabled
     *
     * Should the Parser library be used for the entire page?
     *
     * Can be overridden with $this->template->enableParser();
     *
     * Default: true
     */
    public bool $parserEnabled = false;

    /**
     * Parser Enabled for Body
     *
     * If the parser is enabled, do you want it to parse the body or not?
     *
     * Can be overridden with $this->template->enableParserBody();
     *
     * Default: true
     */
    public bool $parserBodyEnabled = false;

    /**
     * Title Separator
     *
     * What string should be used to separate title segments sent 
     * via $this->template->title('Foo', 'Bar');
     *
     * Default: ' — '
     */
    public string $titleSeparator = ' — ';

    /**
     * Layout
     *
     * Which layout file should be used? When combined with theme it will be a layout file in that theme
     *
     * Change to 'main' to get /app/Views/layouts/main.php
     *
     * Default: 'default'
     */
    public string $layout = 'layout';

    /**
     * Theme
     *
     * Which theme to use by default?
     *
     * Can be overriden with $this->template->set_theme('foo');
     *
     * Default: ''
     *
     */
    public string $theme = '';

    /**
     * Theme Locations
     *
     * Where should we expect to see themes?
     *
     * Default: [APPPATH.'themes/' => '../themes/']
     *
     */
    public array $themeLocations = [
        APPPATH . 'themes/' => '../themes/'
    ];
}

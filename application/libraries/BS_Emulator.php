<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use Symfony\Component\Yaml\Yaml;

class BS_Emulator {
    private $CI;  // CodeIgniter instance
    private $emulator;  // Emulator name
    private $emulator_config;  // Emulator configuration loaded from YAML

    const EMULATOR_CONFIG_PATH = APPPATH . 'emulators/'; // Base path for emulator configurations

    /**
     * Constructor to initialize the emulator configuration.
     * @throws Exception If emulator is not set.
     */
    public function __construct() {
        $this->CI =& get_instance();  // Get the CodeIgniter instance
        if ($this->CI->config->item('installation_active')) {
            return;
        }
        $this->emulator = $this->CI->setting_model->get_value('app_emulator') ?? null;  // Fetch emulator setting
        
        if ($this->emulator === null) {
            throw new Exception("No emulator set in configuration.");
        }

        $this->initialize_emulator($this->emulator);
    }

    /**
     * Initialize the emulator by loading the corresponding configuration.
     * 
     * @param string $emulator The name of the emulator to load.
     * @throws Exception If the configuration file is not found or the emulator name is invalid.
     */
    public function initialize_emulator($emulator) {
        // Validate emulator name
        if (!$this->is_valid_emulator_name($emulator)) {
            throw new Exception("Invalid emulator name: " . $emulator);
        }

        // Load emulator configuration from its corresponding folder
        $config_path = $this->find_emulator_config($emulator);

        if ($config_path !== null && file_exists($config_path)) {
            $this->emulator_config = $this->parse_yaml($config_path);
        } else {
            throw new Exception("Config file not found for emulator: " . $emulator);
        }
    }

    /**
     * Find the emulator configuration file within the emulator directories.
     * 
     * @param string $emulator The emulator name.
     * @return string|null The path to the YAML configuration file or null if not found.
     */
    private function find_emulator_config($emulator) {
        $directories = glob(self::EMULATOR_CONFIG_PATH . '*', GLOB_ONLYDIR);

        foreach ($directories as $dir) {
            $config_path = $dir . '/' . $emulator . '.yml';
            if (file_exists($config_path)) {
                return $config_path;
            }
        }
        return null;
    }

    /**
     * Validate the emulator name.
     * 
     * @param string $emulator The emulator name to validate.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_emulator_name($emulator) {
        return preg_match('/^[a-zA-Z0-9_]+$/', $emulator);
    }

    /**
     * Parse a YAML file into an associative array.
     * 
     * @param string $file_path Path to the YAML file.
     * @return array Parsed configuration data.
     * @throws Exception If there is an error parsing the YAML file.
     */
    private function parse_yaml($file_path) {
        try {
            return Yaml::parseFile($file_path);
        } catch (\Exception $e) {
            throw new Exception("Error parsing YAML file: " . $file_path . ' - ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a value from the emulator configuration.
     * 
     * @param string $key The key in the configuration to retrieve.
     * @param string $subsection The subsection in the configuration to retrieve (e.g., 'queries', 'config').
     * @return mixed The configuration value.
     * @throws Exception If the key is not defined in the emulator configuration.
     */
    private function get_emulator_value($key, $subsection) {
        if (isset($this->emulator_config[$subsection][$key])) {
            return $this->emulator_config[$subsection][$key];
        }
        throw new Exception(ucfirst($subsection) . " not defined for emulator: " . $this->emulator);
    }

    /**
     * Get a SQL query from the emulator configuration.
     * 
     * @param string $query_name The name of the query to retrieve.
     * @return string The SQL query.
     * @throws Exception If the query is not defined in the emulator configuration.
     */
    public function get_query($query_name) {
        return $this->get_emulator_value($query_name, 'queries');
    }

    /**
     * Get a specific configuration value from the emulator configuration.
     * 
     * @param string $config The name of the configuration to retrieve.
     * @return mixed The configuration value.
     * @throws Exception If the configuration is not defined in the emulator configuration.
     */
    public function get_config($config) {
        return $this->get_emulator_value($config, 'config');
    }

    /**
     * Get columns configuration for a given table.
     * 
     * @param string $table The name of the table.
     * @return array The columns configuration.
     * @throws Exception If the table is not defined in the configuration.
     */
    public function get_columns($table) {
        return $this->get_emulator_value($table, 'columns');
    }
}

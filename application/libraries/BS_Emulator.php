<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use Symfony\Component\Yaml\Yaml;

class BS_Emulator {
    private $CI;  // CodeIgniter instance
    private $emulator;  // Emulator name
    private $emulator_config;  // Emulator configuration loaded from YAML

    /**
     * Constructor to initialize the emulator configuration.
     */
    public function __construct() {
        $this->CI =& get_instance();  // Get the CodeIgniter instance
        $this->emulator = $this->CI->setting_model->get_value('app_emulator') ?? null;  // Fetch emulator setting
        $this->load_emulator_config($this->emulator);  // Load the emulator-specific configuration
    }

    /**
     * Load the emulator-specific configuration.
     * 
     * @param string $emulator The name of the emulator to load.
     * @throws Exception If the configuration file is not found or the emulator name is invalid.
     */
    public function load_emulator_config($emulator) {
        // Validate the emulator name to prevent directory traversal or other attacks
        if (preg_match('/^[a-zA-Z0-9_]+$/', $emulator)) {
            $config_path = APPPATH . 'emulators/' . $emulator . '_emulator.yaml';  // Path to the emulator config file
            if (file_exists($config_path)) {
                $this->emulator_config = $this->parse_yaml($config_path);  // Parse YAML file
            } else {
                throw new Exception("Config file not found: " . $config_path);  // Handle missing config file
            }
        } else {
            throw new Exception("Invalid emulator name: " . $emulator);  // Handle invalid emulator name
        }
    }
    
    /**
     * Parse a YAML file into an associative array.
     * 
     * @param string $file_path Path to the YAML file.
     * @return array Parsed configuration data.
     */
    private function parse_yaml($file_path) {
        try {
            return Yaml::parseFile($file_path);
        } catch (\Exception $e) {
            throw new Exception("Error parsing YAML file: " . $e->getMessage());
        }
    }

    /**
     * Get a SQL query from the emulator configuration.
     * 
     * @param string $query_name The name of the query to retrieve.
     * @return string The SQL query.
     * @throws Exception If the query is not defined in the emulator configuration.
     */
    public function get_query($query_name) {
        if (isset($this->emulator_config['queries'][$query_name])) {
            return $this->emulator_config['queries'][$query_name];
        }
        throw new Exception("Query not defined for emulator: " . $this->emulator);
    }

    /**
     * Get the emulator configuration.
     * 
     * @param string $query_name The name of the query to retrieve.
     * @return string The SQL query.
     * @throws Exception If the query is not defined in the emulator configuration.
     */
    public function get_config($config) {
        if (isset($this->emulator_config['config'][$query_name])) {
            return $this->emulator_config['config'][$query_name];
        }
        throw new Exception("Config not defined for emulator: " . $this->emulator);
    }

    /**
     * Get columns configuration for a given table.
     * 
     * @param string $table The name of the table.
     * @return array The columns configuration.
     * @throws Exception If the table is not defined.
     */
    public function get_columns($table) {
        if (isset($this->emulator_config['columns'][$table])) {
            return $this->emulator_config['columns'][$table];
        }
        throw new Exception("Columns configuration not defined for table: $table");
    }
}

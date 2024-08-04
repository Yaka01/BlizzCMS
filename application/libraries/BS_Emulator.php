<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class BS_Emulator {
    private $CI;  // CodeIgniter instance
    private $emulator;  // Emulator name
    private $config;  // Configuration array for the emulator

    // Allowed function names for security
    private $allowed_functions = ['password_hash'];

    /**
     * Constructor to initialize the emulator configuration.
     */
    public function __construct() {
        $this->CI =& get_instance();  // Get the CodeIgniter instance
        $this->emulator  = $this->CI->setting_model->get_value('app_emulator') ?? null;  // Fetch emulator setting
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
            $config_path = APPPATH . 'emulators/' . $emulator . '_emulator.php';  // Path to the emulator config file
            if (file_exists($config_path)) {
                $this->emulator_config = include($config_path);
            } else {
                throw new Exception("Config file not found: " . $config_path);  // Handle missing config file
            }
        } else {
            throw new Exception("Invalid emulator name: " . $emulator);  // Handle invalid emulator name
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

    /**
     * Build an SQL query for inserting data into a table.
     * 
     * @param string $table The name of the table.
     * @return string The SQL query.
     */
    public function build_insert_query($table) {
        $columns = $this->get_columns($table);
        $column_names = array_values($columns);
        $placeholders = array_fill(0, count($column_names), '?');
        return "INSERT INTO $table (" . implode(', ', $column_names) . ") VALUES (" . implode(', ', $placeholders) . ")";
    }

    /**
     * Build an SQL query for updating data in a table.
     * 
     * @param string $table The name of the table.
     * @param string $where_condition The WHERE condition for the update.
     * @return string The SQL query.
     */
    public function build_update_query($table, $where_condition) {
        $columns = $this->get_columns($table);
        $set_clause = [];
        foreach ($columns as $key => $column) {
            $set_clause[] = "$column = ?";
        }
        return "UPDATE $table SET " . implode(', ', $set_clause) . " WHERE $where_condition";
    }

    /**
     * Get a function from the emulator configuration.
     * 
     * @param string $function_name The name of the function to retrieve.
     * @return callable The function.
     * @throws Exception If the function is not defined in the emulator configuration.
     */
    public function get_function($function_name) {
        if (isset($this->emulator_config['config']['functions'][$function_name]) && 
            is_callable($this->emulator_config['config']['functions'][$function_name])) {
            return $this->emulator_config['config']['functions'][$function_name];
        }
        throw new Exception("Function '$function_name' not defined for emulator: " . $this->emulator);
    }
}

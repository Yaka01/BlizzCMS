<?php

/**
 * SQL Queries Configuration for TrinityCore Database
 *
 * This file contains configuration and SQL queries used for interacting with a TrinityCore database.
 * The SQL queries and configuration are organized into associative arrays for easy retrieval and use.
 */

// Hash and functions configuration
$config = [
    /**
     * Type of hash used for passwords.
     * 
     * 'srp6' is the default hash type.
     */
    'hash' => 'srp6',
    
    /**
     * Functions related to password hashing.
     * 
     * Includes a function to generate the password hash using the specified hash type.
     */
    'functions' => [
        'password_hash' => function($username, $password, $salt = null) {
            // Check if the client_pwd_hash function is available
            if (!function_exists('client_pwd_hash')) {
                throw new Exception("Function client_pwd_hash not available.");
            }
            
            // Generate a new salt if not provided
            if ($salt === null) {
                $salt = random_bytes(32);
            }
            
            // Return the password hash using the specified hash type
            return client_pwd_hash($username, $password, 'srp6', $salt);
        }
    ]
];

// Column configuration for tables
$columns = [
    /**
     * Column configuration for the 'account' table.
     * 
     * Maps database column names to their names in the code.
     */
    'account' => [
        'id'          => 'id',          // Unique identifier for the account
        'username'    => 'username',    // Username of the account
        'salt'        => 'salt',        // Salt used for password hashing
        'verifier'    => 'verifier',    // Password verifier
        'email'       => 'email',       // Email associated with the account
        'joindate'    => 'joindate',    // Account creation date
        'last_ip'     => 'last_ip',     // Last IP used to access the account
        'last_login'  => 'last_login',  // Last login date
        'expansion'   => 'expansion',   // Game expansion associated with the account
    ],
    
    /**
     * Column configuration for the 'battlenet_accounts' table.
     * 
     * Maps database column names to their names in the code.
     */
    'battlenet_accounts' => [
        'id'            => 'id',            // Unique identifier for the Battle.net account
        'email'         => 'email',         // Email associated with the Battle.net account
        'srp'           => 'srp_version',   // SRP version used for the Battle.net account
        'salt'          => 'salt',          // Salt used for the Battle.net password hash
        'verifier'      => 'verifier',      // Password verifier for the Battle.net account
    ]
];

// SQL Queries - Define specific SQL queries if needed
$queries = [
    // Add specific SQL queries here if needed
    // Example:
    // 'select_user_by_id' => 'SELECT * FROM account WHERE id = ?',
    // 'update_user_email' => 'UPDATE account SET email = ? WHERE id = ?',
];

// Return the complete configuration
return [
    'columns' => $columns,
    'queries' => $queries,
    'config'  => $config
];

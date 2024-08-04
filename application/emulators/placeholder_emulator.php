<?php

/**
 * Placeholder Emulator Configuration
 *
 * This file serves as a placeholder configuration for an emulator.
 * It includes basic settings for hashing, columns, and SQL queries.
 * Customize this file according to the requirements of your emulator.
 */

// Hash and functions configuration
$config = [
    /**
     * Type of hash used for passwords.
     * 
     * This is a placeholder value. Replace it with the actual hash type used by your emulator.
     */
    'hash' => 'default_hash',
    
    /**
     * Functions related to password hashing.
     * 
     * Includes a placeholder function for generating the password hash.
     */
    'functions' => [
        'password_hash' => function($username, $password, $salt = null) {
            // Placeholder hash function; replace with actual implementation
            if ($salt === null) {
                $salt = random_bytes(16); // Default salt length
            }
            
            // This is a placeholder hash function; replace with the actual hash logic
            return hash('sha256', $username . $password . $salt);
        }
    ]
];

// Column configuration for tables
$columns = [
    /**
     * Column configuration for the 'account' table.
     * 
     * This is a placeholder configuration. Adjust the column names according to your database schema.
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
     * This is a placeholder configuration. Adjust the column names according to your database schema.
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
    // 'insert_user' => 'INSERT INTO account (username, email, password, joindate) VALUES (?, ?, ?, ?)',
];

// Return the complete configuration
return [
    'columns' => $columns,
    'queries' => $queries,
    'config'  => $config
];

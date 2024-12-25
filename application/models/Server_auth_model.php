<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Server_auth_model extends CI_Model
{
    /**
     * Class constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Connect to auth DB
     *
     * @return object
     */
    public function connect()
    {
        $database = $this->load->database('auth', true);

        if ($database->conn_id === false) {
            show_error(lang('error_auth_connection'));
        }

        return $database;
    }

    /**
     * Create account
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @return int
     */
    public function create_account($username, $email, $password)
    {
        $database = $this->connect();

        $columns = $this->bs_emulator->get_columns('account');
        $type    = $this->bs_emulator->get_config('type');

        $account = $this->prepare_account_data($username, $email, $password, $columns, $type);

        $database->insert($columns['table'], $account);

        $id = $database->insert_id();

        if ($this->should_create_bnet_account($database)) {
            $this->create_bnet_account($database, $id, $email, $account['salt'], $account['verifier']);
            $this->update_account_with_bnet($database, $columns, $id);
        }

        return $id;
    }

    /**
     * Prepara los datos de la cuenta, incluyendo hash y salt si es necesario.
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @param array $columns
     * @param string $type
     * @return array
     */
    private function prepare_account_data($username, $email, $password,  $columns, $type)
    {
        $account = [
            $columns['username']  => $username,
            $columns['email']     => $email,
            $columns['expansion'] => config_item('app_expansion'),
            $columns['joindate']  => date('Y-m-d H:i:s'),
            $columns['last_ip']   => '',
            $columns['last_login']=> date('Y-m-d H:i:s')
        ];
    
        if ($this->requires_hashing($type)) {
            $salt = random_bytes(32);
            $hashed_password = client_pwd_hash($username, $password, $type, $salt);
    
            $account[$columns['salt']] = $salt;
            $account[$columns['verifier']] = $hashed_password;
        }
    
        return $account;
    }

    /**
     * Creates a new BNET account in the database with the provided data.
     *
     * @param object $database        Database instance.
     * @param int    $id              Unique ID of the account.
     * @param string $email           Email address of the BNET account.
     * @param string $salt            Salt used for authentication.
     * @param string $hashed_password Encrypted password of the account.
     * @return void
     */
    private function create_bnet_account($database, int $id, string $email, string $salt, string $hashed_password): void
    {
        $bnet_columns = $this->bs_emulator->get_columns('battlenet_accounts');

        $bnet_account = [
            $bnet_columns['id']       => $id,
            $bnet_columns['email']    => $email,
            $bnet_columns['salt']     => $salt,
            $bnet_columns['verifier'] => $hashed_password,
        ];

        $database->insert($bnet_columns['table'], $bnet_account);
    }

    /**
     * Updates the main account in the database with the BNET account data.
     *
     * @param object $database Database instance.
     * @param array  $columns  Array containing the main table's columns.
     * @param int    $id       Unique ID of the main account.
     * @return void
     */
    private function update_account_with_bnet($database, array $columns, int $id): void
    {
        $update_columns = [
            'battlenet_account' => $id,
            'battlenet_index'   => 1,
        ];

        $database->update($columns['table'], $update_columns, [$columns['id'] => $id]);
    }

    /**
     * 
     * @param string $type
     * @return bool
     */
    private function requires_hashing($type) 
    {
        $types_requiring_hashing = ['srp6', 'srp6v1', 'srp6v2'];

        return in_array($type, $types_requiring_hashing);
    }

    /**
     * Verifica si se debe crear una cuenta BNET.
     *
     * @param object $database
     * @return bool
     */
    private function should_create_bnet_account($database)
    {
        return config_item('app_emulator_bnet') && $database->table_exists('battlenet_accounts');
    }

    

    /**
     * Get account
     *
     * @param int $id
     * @return mixed
     */
    public function account($id)
    {
        return $this->connect()
            ->where('id', $id)
            ->get('account')
            ->row();
    }

    /**
     * Get the account id by searching a value in a column
     *
     * @param string $value
     * @param string $column
     * @return int
     */
    public function account_id($value, $column = 'username')
    {
        if (! in_array($column, ['username', 'email'], true)) {
            return 0;
        }

        $query = $this->connect()
            ->where($column, $value)
            ->get('account')
            ->row('id');

        return empty($query) ? 0 : (int) $query;
    }

    /**
     * Check if an account with a column value exists
     *
     * @param string $value
     * @param string $column
     * @return bool
     */
    public function account_exists($value, $column = 'username')
    {
        if (! in_array($column, ['username', 'email'], true)) {
            return false;
        }

        $query = $this->connect()
            ->where($column, $value)
            ->get('account')
            ->num_rows();

        return $query === 1;
    }

    /**
     * Get the gmlevel of an account
     *
     * @param int|null $id
     * @return int
     */
    public function account_gmlevel($id = null)
    {
        $id ??= $this->session->userdata('id');
        $emulator = config_item('app_emulator');
        $database = $this->connect();

        switch ($emulator) {
            case 'trinity':
                $query = $database->where('AccountID', $id)
                    ->get('account_access')
                    ->row('SecurityLevel');
                break;

            case 'cmangos':
            case 'mangos':
                $query = $database->where('id', $id)
                    ->get('account')
                    ->row('gmlevel');
                break;

            case 'azeroth':
            case 'trinity_sha':
                $query = $database->where('id', $id)
                    ->get('account_access')
                    ->row('gmlevel');
                break;
        }

        if (! isset($query) || empty($query)) {
            return 0;
        }

        return (int) $query;
    }

    /**
     * Check if an account is banned
     *
     * @param int|null $id
     * @return bool
     */
    public function is_banned($id = null)
    {
        $id ??= $this->session->userdata('id');
        $database = $this->connect();

        $column = $database->field_exists('account_id', 'account_banned') ? 'account_id' : 'id';
        $query  = $database->from('account_banned')
            ->where([
                $column  => $id,
                'active' => 1
            ])
            ->count_all_results();

        return $query >= 1;
    }

    /**
     * Password verify
     *
     * @param string $password
     * @param int $account
     * @return bool
     */
    public function password_verify($password, $account)
    {
        $emulator = config_item('app_emulator');
        $row      = $this->account($account);

        if (empty($row)) {
            return false;
        }

        switch ($emulator) {
            case 'azeroth':
            case 'trinity':
                $validate = ($row->verifier === client_pwd_hash($row->username, $password, 'srp6', $row->salt));
                break;

            case 'cmangos':
                $validate = (strtoupper($row->v) === client_pwd_hash($row->username, $password, 'hex', $row->s));
                break;

            case 'mangos':
            case 'trinity_sha':
                $validate = hash_equals(strtoupper($row->sha_pass_hash), client_pwd_hash($row->username, $password));
                break;
        }

        if (! isset($validate)) {
            return false;
        }

        return $validate;
    }
}

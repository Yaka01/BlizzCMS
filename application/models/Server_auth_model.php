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
        $database = $this->connect(); // Conectar a la base de datos

        // Obtener la configuración de columnas para la tabla de cuentas
        $columns = $this->bs_emulator->get_columns('account');
        $type = $this->bs_emulator->get_config('type');

        // Generar el hash de la contraseña
        $salt = random_bytes(32);
        $hashed_password = client_pwd_hash($username, $password, $type, $salt);

        // Inicializar los datos de la cuenta
        $account = [
            $columns['username']  => $username,
            $columns['email']     => $email,
            $columns['expansion'] => config_item('app_expansion'),
            $columns['salt']      => $salt, // Usar el valor de salt generado por la función de hash
            $columns['verifier']  => $hashed_password, // Usar el hash generado por la función
            $columns['joindate']  => date('Y-m-d H:i:s'), // Fecha actual
            $columns['last_ip']   => '', // Asignar valores predeterminados si es necesario
            $columns['last_login']=> date('Y-m-d H:i:s') // Fecha actual
        ];

        // Utilizar Query Builder para insertar los datos de la cuenta
        $database->insert($columns['table'], $account);

        // Obtener el ID de la cuenta recién creada
        $id = $database->insert_id();

        // Crear una cuenta BNET si está habilitado y la tabla existe
        if (config_item('app_emulator_bnet') && $database->table_exists('battlenet_accounts')) {
            // Obtener la configuración de columnas para la tabla BNET
            $bnet_columns = $this->bs_emulator->get_columns('battlenet_accounts');
            
            // Preparar los datos de la cuenta BNET
            $bnet_account = [
                $bnet_columns['id']            => $id,
                $bnet_columns['email']         => $email,            
                $bnet_columns['salt']          => $salt,
                $bnet_columns['verifier']      => $hashed_password,
            ];
            
            // Utilizar Query Builder para insertar los datos de la cuenta BNET
            $database->insert($bnet_columns['table'], $bnet_account);

            // Actualizar la cuenta principal con el ID de BNET
            $update_columns = [
                'battlenet_account' => $id,
                'battlenet_index'   => 1
            ];
            
            // Utilizar Query Builder para actualizar los datos de la cuenta
            $database->update($columns['table'], $update_columns, [$columns['id'] => $id]);
        }

        return $id;
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

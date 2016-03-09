<?php

/*
  Plugin Name: Icarus Tree API Endpoint
  Description: Adds an API for Wordpress operations.
  Version: 1.0
  Author: Michael Dearman
  Author URL: http://icarustree.co.uk
 */

class IcarusTree_API_Endpoint {

    /**
     * The key for making requests to the API.
     * @var string
     */
    protected $api_key = "Kt>>X[g82z]#8fdj4jXP^xR1|qU<1o";

    /**
     * Character length of generated token.
     * @var int
     */
    protected $token_length = 10;

    /**
     * Number of seconds before session times out.
     * @var int
     */
    protected $session_timeout = 43200;

    /**
     * Hook WordPress
     * @return void
     */
    public function __construct() {

        // Add rewrite rule to htaccess
        add_action('init', array($this, 'icarus_rewrite_rule'), 0);

        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 0);
        
        // Load custom settings
        add_action('init', array($this,'load_settings'));

        // Add all query variables
        add_filter('query_vars', array($this, 'query_vars'), 10, 1);

        // Parse the the request
        add_action('parse_request', array($this, 'handle_requests'), 0);

        // Admin actions
        if (is_admin()) {
            add_action('admin_init', array($this, "register_settings"));
            add_action('admin_menu', array($this, "admin_menu"));
        }
        
        // Activation
        register_activation_hook(__FILE__,array($this,'activate'));
    }
    
    /**
     * Adds custom options on plugin activation.
     * 
     * @return void
     */
    public function activate() {
        add_option("icarus_api_key",$this->api_key);
        add_option("icarus_token_length",$this->token_length);
        add_option("icarus_session_timeout",$this->session_timeout);
    }
    
    /**
     * Enqueues all the scripts.
     * 
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_script('icarus-js',plugins_url('/js/icarus-js.js',__FILE__), 
                array(),'1.0.0',true);
    }
    
    /**
     * Loads custom settings.
     * 
     * @return void
     */
    public function load_settings() {
        if (get_option('icarus_api_key')) {
            $this->api_key = get_option('icarus_api_key');
            $this->token_length = get_option('icarus_token_length');
            $this->session_timeout = get_option('icarus_session_timeout');
        }
    }
    
    /**
     * Register settings and sets custom settings.
     * 
     * @return void
     */
    public function register_settings() {
        register_setting("icarus_tree","icarus_api_key");
        register_setting("icarus_tree","icarus_token_length");
        register_setting("icarus_tree","icarus_session_timeout");
    }
    
    /**
     * Add options page to admin menu.
     * 
     * @return void
     */
    public function admin_menu() {
        add_options_page(
            'Icarus Tree API', 'Icarus Tree API', 
            'manage_options', 'icarus_tree_api_options', 
            array($this,'settings_page')
        );
    }
    
    /**
     * Displays the settings page.
     * 
     * @return void
     */
    public function settings_page() { 
        
        echo '<div class="wrap"><h1>Icarus Tree API</h1>';
        echo '<form method="post" action="options.php">';
        
        settings_fields('icarus_tree');
        do_settings_sections('icarus_tree');
        
        echo '<table class="form-table" style="width:100%">';
            
        $this->config_generate("API Key", "icarus_api_key", 
                "The authorization key when accessing the API.",
                "Generate Random Key");
        
        $this->config_number("Token Length", "icarus_token_length", 
                "The length for the token generated for an authenticated user.");
        
        $this->config_number("Session Timout", "icarus_session_timeout", 
                "The number of seconds until a user's session times out.");
        
        echo '</table>';

        submit_button();
        
        echo '</form></div>';
        
    }
    
    /**
     * Create a text input.
     * 
     * @param string $label The label for the option.
     * @param string $option The option name.
     * @param string $desc The description for the option.
     * 
     * @return void
     */
    public function config_text($label,$option,$desc='')
    {
        echo '<tr valign="top"><th scope="row">'.$label.'</th>';
        echo '<td><input type="text" name="'.$option.'" class="regular-text" value="'; 
        echo esc_attr( get_option($option) );
	echo '"/> <p id="tagline-description" class="description">'.$desc.'</p></td></tr>';
    }
    
    /**
     * Create a text input.
     * 
     * @param string $label The label for the option.
     * @param string $option The option name.
     * @param string $desc The description for the option.
     * @param string $button The button label.
     * 
     * @return void
     */
    public function config_generate($label, $option, $desc = '',$button)
    {
        echo '<tr valign="top"><th scope="row">'.$label.'</th>';
        echo '<td><input type="text" id="'.$option.'" name="'.$option.'" class="regular-text" value="'; 
        echo esc_attr( get_option($option) );
	echo '"/> <p id="tagline-description" class="description">'.$desc.'</p><br>';
        ?>
        <button onclick="event.preventDefault(); generateKey('#<?php echo $option; ?>');">
            <?php echo $button; ?>
        </button>
        <?php
        echo '</td></tr>';
    }
    
    /**
     * Create a number input.
     * 
     * @param string $label The label for the option.
     * @param string $option The option name.
     * @param string $desc Descriptiong for the option.
     * 
     * @return void
     */
    public function config_number($label,$option,$desc='')
    {
        echo '<tr valign="top"><th scope="row">';
	echo '<label for="'.$option.'">'.$label.'</label></th>';
        echo '<td><input type="number" name="'.$option.'" class="regular-text" value="'; 
        echo esc_attr( get_option($option) );
        echo '"/> <p id="tagline-description" class="description">'.$desc.'</p></td></tr>';
    }
    
    /**
     * Create a radio toggle.
     * 
     * @param type $label The label for the option.
     * @param type $option The option name.
     * 
     * @return void
     */
    public function config_toggle($label,$option,$desc='')
    {
        $toggle = get_option($option);
        echo '<tr valign="top"><th scope="row">';
	echo '<label for="'.$option.'">'.$label.'</label></th>';
        echo '<td><p><input type="radio" name="'.$option.'" value="1" ';
        echo ($toggle) ? 'checked=""' : '';
        echo '/> Enabled</p> <p>';
        echo '<input type="radio" name="'.$option.'" value="0" ';
        echo (!$toggle) ? 'checked=""' : '';
        echo '/> Disabled</p> ';
	echo '<p id="tagline-description" class="description">'.$desc.'</p></td></tr>';
    }
    
    /**
     * Create a set of radio options.
     * 
     * @param string $label The label for the option.
     * @param string $option The option name.
     * @param array $array The values of the radio buttons.
     * @param string $desc The description of the option.
     * 
     * @return void
     */
    public function config_radio($label,$option,$array,$desc='')
    {
        echo '<tr valign="top"><th scope="row">'.$label.'</th><td>';
        foreach($array as $l=>$v) {
            echo '<p><input type="radio" name="'.$option.'" value="'.$v.'" ';
            echo (get_option($option) == $v) ? 'checked=""' : '';
            echo '/> '.$l.'</p>';
        }
        echo '<p id="tagline-description" class="description">'.$desc.'</p></td></tr>';
    }

    /**
     * Adds the URL query variables.
     * 
     * @param array $qvars
     * @return string
     */
    public function query_vars($qvars) {

        $qvars[] = 'icarustreeapi';
        $qvars[] = 'endpoint';
        return $qvars;
    }

    /**
     * Adds a rewrite rule to .htaccess in root directory.
     * 
     * @return void
     */
    public function icarus_rewrite_rule() {
        add_rewrite_rule('^icarus/api/([A-Za-z0-9]+)', 'index.php?icarustreeapi=1&endpoint=$1', 'top');
        flush_rewrite_rules();
    }

    /**
     * Serves up the relevant function for each endpoint.
     * 
     * @global type $wp
     * @return void
     */
    public function handle_requests() {
        $json = file_get_contents('php://input');
        $obj = json_decode($json);

        global $wp;

        if (isset($wp->query_vars["icarustreeapi"]) &&
                isset($wp->query_vars["endpoint"])) {

            if ($obj->api_key != $this->api_key) {
                $this->error("API Key not in request.");
            }

            $endpoint = $wp->query_vars["endpoint"];
            switch ($endpoint) {
                case "login": $this->login();
                    break;
                case "post": $this->post();
                    break;
            }
            exit;
        }
    }

    /**
     * Checks a user's login credentials, and prints a access token
     * and a few details about the user.
     * 
     * @return void
     */
    protected function login() {

        $json = file_get_contents('php://input');
        $obj = json_decode($json);
        $arr = (array) $obj;

        if (!empty($obj) && isset($arr["username"]) && isset($arr["password"])) {

            $user = $arr["username"];
            $pass = $arr["password"];

            if ($user != "" && $pass != "") {

                $userobj = wp_authenticate($user, $pass);

                if (is_wp_error($userobj)) {

                    echo '{"error":"Invalid login credentials!"}';
                } else {

                    $token = bin2hex(random_bytes($this->token_length));
                    $date = date("Y-m-d H:i:s");

                    update_user_meta($userobj->ID, 'icarus_token', $token, false);
                    update_user_meta($userobj->ID, 'icarus_session', $date, false);
                    $userobj->token = $token;
                    $userobj->session = $date;

                    unset($userobj->data->user_pass);
                    unset($userobj->data->user_activation_key);
                    unset($userobj->data->user_status);

                    $json = json_encode($userobj->data);
                    print_r($json);
                }
            }
        }
    }

    /**
     * Displays a JSON error string.
     * 
     * @param string $code
     * @return void
     */
    protected function error($code) {

        $error = Array();
        $error["error"] = $code;
        $json = json_encode($error);
        print_r($json);
        exit;
    }

    /**
     * Print array as JSON string.
     * 
     * @param array $array
     * @return void
     */
    protected function send($array) {
        $json = json_encode($array);
        print_r($json);
        exit;
    }

    /**
     * This checks if a access token matches the one in a user's meta 
     * information, and if it hasn't expired.
     * 
     * @param int $id The user ID to check against.
     * @param string $token The access token generated when logged in.
     * @return boolean
     */
    protected function check_token($id, $token) {
        $check = get_user_meta($id, "icarus_token", $token);
        if ($token == $check) {
            $start_date = strtotime(get_user_meta($id, "icarus_session", $token));
            $now = strtotime(date("Y-m-d H:i:s"));
            $interval = ($now - $start_date);
            if ($interval < $this->session_timeout) {
                return true;
            } else {
                $this->error("Your session has timed out. You must log in again.");
                exit;
            }
        } else {
            $this->error("Your token is invalid.");
            exit;
        }
    }

}

new IcarusTree_API_Endpoint();
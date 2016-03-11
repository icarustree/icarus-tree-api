<?php

/*
  Plugin Name: Icarus Tree API Endpoint
  Description: Adds an API for Wordpress operations.
  Version: 1.0
  Author: Icarus Tree
  Author URI: http://icarustree.co.uk
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
     * Endpoints that require a user token and user ID.
     * @var array 
     */
    protected $secure_endpoints = array("post");

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
        
        // Activation & Deactivation
        register_activation_hook(__FILE__,array($this,'activate'));
    }
    
    /**
     * Adds custom options on plugin activation.
     * 
     * @return void
     */
    public function activate() {
        $this->api_key = $this->generate_key(30);
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
     * Generates a secure key.
     * 
     * @param int $length The length of the generated key.
     * @param string $str (Optional) The string to generate a key from.
     * 
     * @return string
     */
    public function generate_key($length,$str = '') {
        
        if ($str == '') {
            $chars = 'abcdefghijklmnopqrstuvwxyz';
            $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $chars .= '0123456789';
            $chars .= '~!@$%^&*()_+-={}[]:;\<>?,./|\\';
        } else {
            $chars = $str;
        }
        
        $result = '';
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[floor(rand(0,strlen($chars)-1))];
        }
        
        return $result;
        
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
        add_rewrite_rule('^icarus/api/?([A-Za-z0-9]+)', 
                'index.php?icarustreeapi=1&endpoint=$matches[1]', 'top');
        flush_rewrite_rules();
    }

    /**
     * Serves up the relevant function for each endpoint.
     * 
     * @global type $wp
     * @return void
     */
    public function handle_requests() {
        
        // If valid path for API
        global $wp;
        if (isset($wp->query_vars["icarustreeapi"]) &&
            isset($wp->query_vars["endpoint"])) {
            
            // Get JSON
            $input = file_get_contents('php://input');
            $json = $this->object_to_array(json_decode($input));
            
            // Check for API Key
            if (isset($json["api_key"])) {
                
                // Check API Key Valid
                if ($json["api_key"] == $this->api_key) {
                    
                    // Remove API key from JSON
                    unset($json["api_key"]);
                    
                    // Get endpoint
                    $endpoint = $wp->query_vars["endpoint"];
                    
                    // Check if endpoint requires token & user ID
                    if (in_array($endpoint,$this->secure_endpoints)) {
                        
                        // Check for token and user ID
                        if (isset($json["token"]) && isset($json["user_id"])) {
                            
                            $userid = $json["user_id"];
                            $token = $json["token"];
                            
                            if ($this->check_token($userid, $token)) {
                                
                                unset($json["user_id"]);
                                unset($json["token"]);
                                
                            }
                            
                        } else {
                            
                            $this->error("This endpoint requires a token and user ID.");
                            
                        }
                        
                    }
                    
                    // Endpoint switch
                    switch($endpoint) {
                        case "login": $this->login($json); break;
                        case "post": $this->post($json,$userid); break;
                        default: $this->error("Not valid endpoint!"); break;
                    }
                    
                } else {
                    
                    $this->error("API key is invalid!");
                    
                }
                
            } else {
                
                $this->error("API key not in request!");
                
            }
            
        }
        
    }

    /**
     * Checks a user's login credentials, and sends token if user exists.
     * 
     * @param array $input The JSON array.
     * 
     * @return void
     */
    protected function login($input) {
        
        // Required JSON
        $req = array(
            "username"      =>  "",
            "password"      =>  ""
        );
        $json = $this->json_req($input,$req);
        
        // Authenticate user
        $user = $json["username"];
        $pass = $json["password"];
        $userobj = wp_authenticate($user, $pass);
        
        // Login Credentials wrong
        if (is_wp_error($userobj)) {
            
            $this->error("Invalid login credentials!");  
            
        } else {
            
            // Generate token
            $token = bin2hex(random_bytes($this->token_length));
            $date = date("Y-m-d H:i:s");
            
            // Put token and session date in database
            update_user_meta($userobj->ID, 'icarus_token', $token, false);
            update_user_meta($userobj->ID, 'icarus_session', $date, false);
            $userobj->token = $token;
            $userobj->session = $date;
            
            // Remove uneccesary values
            unset($userobj->data->user_pass);
            unset($userobj->data->user_activation_key);
            unset($userobj->data->user_status);
            
            // Print results
            $this->send($userobj->data);
            
        }
                
    }
    
    /**
     * Registers a new user on the site.
     * 
     * @param array $input The JSON array.
     * 
     * @return void
     */
    protected function register($input) {
        
        // Required JSON
        $req = array(
            "username"      =>  "",
            "password"      =>  "",
            "email"         =>  FILTER_VALIDATE_EMAIL
        );
        $json = $this->json_req($input,$req);
        
        // Get credentials
        $user_id = username_exists($json["username"]);
        $user_pwd = $json["password"];
        $user_email = $json["email"];
        
        // Check user does not exist already
        if (!$user_id and email_exists($user_email) == false) {
            
            $user_id = wp_create_user($json["username"],$user_pwd,$user_email);
            $response = array("user_id" => $user_id);
            $this->send($response);
            
        } else {
            
            $this->error("User already exists!");  
            
        }
        
    }
    
    /**
     * Insert a post on the blog.
     * 
     * @param array $input The JSON array.
     * @param int $userid The user/author ID.
     * 
     * @return void
     */
    protected function post($input,$userid) {
        
        // Required JSON
        $req = array(
            "post_title"        =>  "",
            "post_content"      =>  ""
        );
        
        // Optional JSON
        $opt = array (
            "post_content_filtered"     => "",
            "post_excerpt"              => "",
            "post_status"               => "",
            "post_type"                 => "",
            "post_date"                 => "",
            "post_password"             => "",
            "post_name"                 => "",
            "post_parent"               => FILTER_VALIDATE_INT,
            "menu_order"                => FILTER_VALIDATE_INT,
            "tax_input"                 => FILTER_REQUIRE_ARRAY,
            "meta_input"                => FILTER_REQUIRE_ARRAY
        );
        
        $json = $this->json_req($input,$req,$opt);
        
        // Set user ID
        $json["post_author"] = $userid;
        $user = get_userdata($userid);
        $role = $user->roles;
        
        // Can post?
        if ($role[0] == "subscriber") {
            $this->error("You do not have the correct permissions to post!");
        }
        
        // Can publish?
        if ($role[0] == "contributor") {
            unset($json["post_status"]);
        }
        
        // Insert post
        $post = wp_insert_post($json);
        
        // Response
        if ($post) {
            
            $response = array("post_id" => $post);
            $this->send($response);
            
        } else {
            
            $this->error("Could not insert post!");
            
        }
        
    }
    
    /**
     * Check submitted JSON for required and optional parameters.
     * 
     * @param array $input JSON to check.
     * @param array $req An array of required parameters and filter type.
     * @param array $opt An array of optional parameters and filter type.
     * 
     * @return array
     */
    protected function json_req($input,$req,$opt = array()) {
        
        // Check for required values
        foreach($req as $rk => $rv) {
            if (!isset($input[$rk])) {
                $this->error($rk . " is required!");
            }
        }
        
        // For each JSON value
        foreach($input as $k => $v) {
            
            // If required value set
            if (isset($req[$k])) {
                
                // If not blank
                if ($v != "") {
                    
                    // Check filter / validation type
                    if ($req[$k] != "") {
                        
                        if ((is_array($v) && $opt[$k] !== FILTER_REQUIRE_ARRAY) ||
                           (!is_array($v) && filter_var($v,$opt[$k]) === false)) {

                            $this->error($k . " is invalid!");

                        }
                    }
                    
                } else {
                    
                    $this->error($k . " cannot be blank!");
                    
                }
                
            } else 
            
            // If value accepted as optional
            if (isset($opt[$k])) {
                
                // If not blank
                if ($v != "") {
                    
                    // Check filter / validation type
                    if ($opt[$k] != "") {
                        
                        if ((is_array($v) && $opt[$k] !== FILTER_REQUIRE_ARRAY) ||
                           (!is_array($v) && filter_var($v,$opt[$k]) === false)) {

                            $this->error($k . " is invalid!");

                        }
                        
                    }
                    
                } else {
                    unset($input[$k]); 
                }
                
            } else {
                unset($input[$k]);  
            }
            
        }
        
        // Return JSON
        return $input;
        
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
    
    /**
     * Convert object to array.
     * 
     * @param object $obj The object to convert.
     * 
     * @return array
     */
    protected function object_to_array($obj) {
        if(is_object($obj)) {
            $obj = (array) $obj;
        }
        if(is_array($obj)) {
            $new = array();
            foreach($obj as $key => $val) {
                $new[$key] = $this->object_to_array($val);
            }
        } else {
            $new = $obj;
        }
        return $new;       
    }

}

new IcarusTree_API_Endpoint(); 
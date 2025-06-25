<?php
/**
 * Plugin Name: Restrict Access to Environment
 * Description: Restricts access to test environments with password protection and blocks SEO indexing.
 * Version: 1.0.1
 * Author: Raluca Manea
 */

if (!defined('ABSPATH')) exit;

class RestrictAccessToEnv {
    private $is_test_env = false;
    private $session_key = 'rae_authenticated';
    
    public function __construct() {
        $this->is_test_env = $this->is_test_environment();
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        if ($this->is_test_env) {
            $this->setup_protection();
        }
    }
    
    private function is_test_environment() {
        // Check for manual override first
        $manual_override = get_option('rae_manual_enable', false);
        if ($manual_override) {
            return true;
        }
        
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Check for test environments
        $is_test = (strpos($host, 'test') !== false);
        
        // Check for localhost environments
        $localhost_patterns = array(
            'localhost',
            '127.0.0.1',
            '::1',
            '.local',
            '.test',
            '.dev'
        );
        
        $is_localhost = false;
        foreach ($localhost_patterns as $pattern) {
            if (strpos($host, $pattern) !== false) {
                $is_localhost = true;
                break;
            }
        }
        
        return $is_test || $is_localhost;
    }
    
    private function setup_protection() {
        add_action('init', array($this, 'start_session'), 1);
        add_action('template_redirect', array($this, 'check_authentication'), 1);
        add_action('wp_head', array($this, 'block_seo'), 1);
        add_filter('wp_robots', array($this, 'add_noindex_robots'));
        add_action('wp_ajax_nopriv_rae_login', array($this, 'handle_login'));
        add_action('wp_ajax_rae_login', array($this, 'handle_login'));
        add_action('wp_ajax_rae_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_nopriv_rae_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_nopriv_rae_test', array($this, 'handle_test'));
        add_action('wp_ajax_rae_test', array($this, 'handle_test'));
        
        // Add debug info for admins
        if (current_user_can('manage_options')) {
            add_action('wp_footer', array($this, 'debug_info'));
        }
    }
    
    public function init() {
        wp_register_style('rae-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.1');
        wp_register_script('rae-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.1', true);
    }
    
    public function start_session() {
        if (!session_id()) {
            session_start();
        }
        
        // Handle session cleanup on logout
        if (isset($_GET['rae_logout'])) {
            $this->handle_logout();
        }
        
        // Handle debug session clearing
        if (isset($_GET['clear_session']) && current_user_can('manage_options')) {
            unset($_SESSION[$this->session_key]);
            error_log('RAE: Session cleared for debugging');
        }
    }
    
    private function is_authenticated() {
        // Check session first
        $session_auth = isset($_SESSION[$this->session_key]) && $_SESSION[$this->session_key] === true;
        
        // Add some debugging for admins
        if (current_user_can('manage_options') && isset($_GET['debug'])) {
            error_log('RAE Debug - Session Auth: ' . ($session_auth ? 'true' : 'false'));
            error_log('RAE Debug - Session ID: ' . session_id());
            error_log('RAE Debug - Session Data: ' . print_r($_SESSION, true));
        }
        
        return $session_auth;
    }
    
    public function check_authentication() {
        // Allow admin, ajax, cron, and REST API requests
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        
        // Allow specific WordPress files
        $allowed_files = array('wp-login.php', 'wp-admin');
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($allowed_files as $file) {
            if (strpos($request_uri, $file) !== false) {
                return;
            }
        }
        
        // Allow administrators to bypass password protection
        if (current_user_can('manage_options')) {
            error_log('RAE: Administrator detected, bypassing password protection');
            return;
        }
        
        // Check if user is authenticated via our password system
        if ($this->is_authenticated()) {
            return;
        }
        
        $this->show_login_form();
        exit;
    }
    
    private function show_login_form() {
        wp_enqueue_style('rae-style');
        wp_enqueue_script('rae-script');
        
        wp_localize_script('rae-script', 'rae_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rae_nonce'),
            'home_url' => home_url()
        ));
        
        $site_name = get_bloginfo('name');
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('rae_nonce');
        $home_url = home_url();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="robots" content="noindex, nofollow">
            <title>Access Restricted - <?php echo esc_html($site_name); ?></title>
            <?php wp_head(); ?>
            <style>
                .rae-debug { margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 5px; font-size: 12px; font-family: monospace; }
                .rae-error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin: 10px 0; }
                .rae-success { background: #e8f5e8; color: #2e7d32; padding: 10px; border-radius: 5px; margin: 10px 0; }
                .loading { opacity: 0.6; pointer-events: none; }
                .rae-container { max-width: 400px; margin: 50px auto; padding: 20px; font-family: Arial, sans-serif; }
                .rae-login-form { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .rae-login-form h1 { text-align: center; margin-bottom: 20px; color: #333; }
                .rae-login-form label { display: block; margin-bottom: 5px; font-weight: bold; }
                .rae-login-form input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; box-sizing: border-box; }
                .rae-login-form button { width: 100%; padding: 12px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
                .rae-login-form button:hover { background: #005a87; }
                .rae-login-form button:disabled { background: #ccc; cursor: not-allowed; }
            </style>
        </head>
        <body class="rae-login-page">
            <div class="rae-container">
                <div class="rae-login-form">
                    <h1><?php echo esc_html($site_name); ?></h1>
                    <p>Test Environment - Access Restricted</p>
                    
                    <!-- JavaScript Detection -->
                    <noscript>
                        <div class="rae-error">
                            <strong>JavaScript Required:</strong> This login form requires JavaScript to be enabled.
                        </div>
                    </noscript>
                    
                    <form id="rae-login-form" method="post">
                        <label for="rae-password">Password:</label>
                        <input type="password" id="rae-password" name="password" required>
                        <button type="submit" id="rae-submit-btn">Access Site</button>
                        <input type="hidden" name="action" value="rae_login">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('rae_nonce'); ?>">
                    </form>
                    
                    <p><strong>Environment:</strong> Test/Development</p>
                    
                    <!-- Debug Information -->
                    <div class="rae-debug">
                        <strong>Debug Information:</strong><br>
                        Current URL: <?php echo esc_html($_SERVER['REQUEST_URI'] ?? ''); ?><br>
                        Session ID: <?php echo session_id(); ?><br>
                        Authenticated: <?php echo $this->is_authenticated() ? 'Yes' : 'No'; ?><br>
                        Password Hash: <?php echo substr(md5(get_option('rae_admin_password', 'test123')), 0, 8); ?>...<br>
                        AJAX URL: <?php echo admin_url('admin-ajax.php'); ?><br>
                        Home URL: <?php echo home_url(); ?><br>
                        WordPress Version: <?php echo get_bloginfo('version'); ?><br>
                        jQuery Loaded: <span id="jquery-status">Checking...</span><br>
                        JavaScript Status: <span id="js-status">Checking...</span><br>
                        Plugin JS Loaded: <span id="plugin-js-status">Checking...</span><br>
                        <a href="?debug=1&clear_session=1">Clear Session & Debug</a>
                    </div>
                </div>
            </div>
            
            <!-- Inline JavaScript for immediate functionality -->
            <script>
                // Immediate JavaScript test
                document.getElementById('js-status').textContent = 'Working';
                
                // Global variables for AJAX
                var raeAjaxUrl = '<?php echo $ajax_url; ?>';
                var raeNonce = '<?php echo $nonce; ?>';
                var raeHomeUrl = '<?php echo $home_url; ?>';
                
                // Inline AJAX function as fallback
                function raeLogin(password) {
                    var form = document.getElementById('rae-login-form');
                    var button = document.getElementById('rae-submit-btn');
                    
                    // Show loading
                    button.disabled = true;
                    button.textContent = 'Authenticating...';
                    
                    // Create XMLHttpRequest
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', raeAjaxUrl, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    // Show success and redirect
                                    var successDiv = document.createElement('div');
                                    successDiv.className = 'rae-success';
                                    successDiv.textContent = '✓ Access granted! Redirecting...';
                                    form.parentNode.insertBefore(successDiv, form);
                                    
                                    setTimeout(function() {
                                        window.location.href = raeHomeUrl + '?authenticated=' + Date.now();
                                    }, 1500);
                                } else {
                                    // Show error
                                    var errorDiv = document.createElement('div');
                                    errorDiv.className = 'rae-error';
                                    errorDiv.textContent = '✗ ' + (response.data.message || 'Invalid password');
                                    form.parentNode.insertBefore(errorDiv, form);
                                    
                                    // Reset form
                                    button.disabled = false;
                                    button.textContent = 'Access Site';
                                    document.getElementById('rae-password').value = '';
                                }
                            } catch (e) {
                                // Show parsing error
                                var errorDiv = document.createElement('div');
                                errorDiv.className = 'rae-error';
                                errorDiv.textContent = '✗ Server response error: ' + xhr.responseText.substring(0, 100);
                                form.parentNode.insertBefore(errorDiv, form);
                                
                                button.disabled = false;
                                button.textContent = 'Access Site';
                            }
                        }
                    };
                    
                    // Send request
                    var data = 'action=rae_login&password=' + encodeURIComponent(password) + '&nonce=' + encodeURIComponent(raeNonce);
                    xhr.send(data);
                }
                
                // Form submission handler
                document.getElementById('rae-login-form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var password = document.getElementById('rae-password').value;
                    if (!password) {
                        alert('Please enter a password');
                        return false;
                    }
                    
                    // Remove any previous messages
                    var errors = document.querySelectorAll('.rae-error, .rae-success');
                    for (var i = 0; i < errors.length; i++) {
                        errors[i].remove();
                    }
                    
                    // Use inline AJAX function
                    raeLogin(password);
                    
                    return false;
                });
                
                // jQuery and plugin detection
                window.addEventListener('load', function() {
                    if (typeof jQuery !== 'undefined') {
                        document.getElementById('jquery-status').textContent = 'Available (v' + jQuery.fn.jquery + ')';
                    } else {
                        document.getElementById('jquery-status').textContent = 'NOT AVAILABLE';
                    }
                    
                    // Check if plugin script loaded
                    if (typeof jQuery !== 'undefined' && jQuery.fn.jquery) {
                        // Wait a bit for plugin script to load
                        setTimeout(function() {
                            if (window.rae_ajax) {
                                document.getElementById('plugin-js-status').textContent = 'Loaded';
                            } else {
                                document.getElementById('plugin-js-status').textContent = 'Using fallback';
                            }
                        }, 1000);
                    } else {
                        document.getElementById('plugin-js-status').textContent = 'Using fallback';
                    }
                });
                
                console.log('RAE: Inline JavaScript loaded');
                console.log('RAE: AJAX URL:', raeAjaxUrl);
                console.log('RAE: Home URL:', raeHomeUrl);
                
                // Test AJAX connectivity immediately
                setTimeout(function() {
                    console.log('RAE: Testing AJAX connectivity...');
                    var testXhr = new XMLHttpRequest();
                    testXhr.open('POST', raeAjaxUrl, true);
                    testXhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    
                    testXhr.onreadystatechange = function() {
                        if (testXhr.readyState === 4) {
                            console.log('RAE: Test AJAX Status:', testXhr.status);
                            console.log('RAE: Test AJAX Response:', testXhr.responseText);
                            
                            if (testXhr.status === 200) {
                                try {
                                    var response = JSON.parse(testXhr.responseText);
                                    console.log('RAE: AJAX test successful:', response);
                                } catch (e) {
                                    console.log('RAE: AJAX response not JSON:', testXhr.responseText);
                                }
                            } else {
                                console.log('RAE: AJAX test failed with status:', testXhr.status);
                            }
                        }
                    };
                    
                    var testData = 'action=rae_test&nonce=' + encodeURIComponent(raeNonce);
                    testXhr.send(testData);
                }, 500);
            </script>
            
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
    
    public function handle_login() {
        // Add debugging
        error_log('RAE: handle_login called');
        error_log('RAE: POST data: ' . print_r($_POST, true));
        
        // Verify nonce first
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'rae_nonce')) {
            error_log('RAE: Nonce verification failed. Expected: rae_nonce, Got: ' . $nonce);
            wp_send_json_error(array('message' => 'Security check failed. Please refresh and try again.'));
            return;
        }
        
        $password = $_POST['password'] ?? '';
        $correct_password = get_option('rae_admin_password', 'test123');
        
        // Add debugging for password comparison
        error_log('RAE: Password check - Entered: ' . substr($password, 0, 3) . '... - Expected: ' . substr($correct_password, 0, 3) . '...');
        error_log('RAE: Password match: ' . ($password === $correct_password ? 'YES' : 'NO'));
        
        if ($password === $correct_password) {
            // Ensure session is started
            if (!session_id()) {
                session_start();
            }
            
            // Set authentication
            $_SESSION[$this->session_key] = true;
            
            // Force session write
            session_write_close();
            session_start();
            
            error_log('RAE: Login successful, session set');
            
            // Send success response
            wp_send_json_success(array(
                'redirect' => home_url(),
                'message' => 'Login successful',
                'session_id' => session_id()
            ));
        } else {
            error_log('RAE: Login failed - password mismatch');
            wp_send_json_error(array('message' => 'Invalid password. Please try again.'));
        }
    }
    
    public function handle_logout() {
        if (session_id()) {
            unset($_SESSION[$this->session_key]);
            session_destroy();
        }
        
        if (!wp_doing_ajax()) {
            wp_redirect(home_url());
            exit;
        } else {
            wp_send_json_success(array('message' => 'Logged out successfully'));
        }
    }
    
    public function handle_test() {
        error_log('RAE: Test AJAX handler called');
        wp_send_json_success(array(
            'message' => 'AJAX is working!',
            'timestamp' => time(),
            'post_data' => $_POST
        ));
    }
    
    public function debug_info() {
        if (isset($_GET['debug']) && current_user_can('manage_options')) {
            echo '<!-- RAE Debug Info: 
            Session ID: ' . session_id() . '
            Authenticated: ' . ($this->is_authenticated() ? 'true' : 'false') . '
            Session Data: ' . print_r($_SESSION, true) . '
            -->';
        }
    }
    
    public function block_seo() {
        echo '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">' . "\n";
    }
    
    public function add_noindex_robots($robots) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
        return $robots;
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Restrict Access Settings',
            'Restrict Access',
            'manage_options',
            'restrict-access-settings',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('rae_settings', 'rae_admin_password');
        register_setting('rae_settings', 'rae_manual_enable');
        
        add_settings_section(
            'rae_main_section',
            'Environment Protection Settings',
            array($this, 'section_callback'),
            'rae_settings'
        );
        
        add_settings_field(
            'rae_manual_enable',
            'Manual Override',
            array($this, 'manual_enable_field_callback'),
            'rae_settings',
            'rae_main_section'
        );
        
        add_settings_field(
            'rae_admin_password',
            'Admin Password',
            array($this, 'password_field_callback'),
            'rae_settings',
            'rae_main_section'
        );
    }
    
    public function section_callback() {
        echo '<p>Configure password protection for test environments and localhost development.</p>';
        
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $manual_override = get_option('rae_manual_enable', false);
        
        // Show environment detection details
        echo '<div class="notice notice-info inline">';
        echo '<p><strong>Environment Detection:</strong></p>';
        echo '<p>Current Host: <code>' . esc_html($host) . '</code></p>';
        
        if ($manual_override) {
            echo '<p>Status: <span style="color: orange;">Manually Enabled</span></p>';
        } elseif ($this->is_test_env) {
            echo '<p>Status: <span style="color: red;">Auto-Detected (Test/Localhost)</span></p>';
        } else {
            echo '<p>Status: <span style="color: green;">Production (No Protection)</span></p>';
        }
        echo '</div>';
        
        // Show user access information
        echo '<div class="notice notice-success inline">';
        echo '<p><strong>User Access Rules:</strong></p>';
        echo '<ul style="margin-left: 20px;">';
        echo '<li><strong>Administrators:</strong> <span style="color: green;">Bypass password protection</span></li>';
        echo '<li><strong>Regular Users/Customers:</strong> <span style="color: orange;">Must enter password</span></li>';
        echo '<li><strong>Anonymous Visitors:</strong> <span style="color: red;">Must enter password</span></li>';
        echo '</ul>';
        echo '<p><em>You are currently logged in as an Administrator, so you can access the frontend without entering a password.</em></p>';
        echo '</div>';
        
        // Add current session info for debugging
        if (current_user_can('manage_options')) {
            echo '<div class="notice notice-info inline">';
            echo '<p><strong>Current Session Status:</strong></p>';
            echo '<p>Session ID: ' . (session_id() ? session_id() : 'Not started') . '</p>';
            echo '<p>Authenticated: ' . ($this->is_authenticated() ? 'Yes' : 'No') . '</p>';
            echo '<p>Your Access Level: <strong>Administrator (Bypass Enabled)</strong></p>';
            echo '<p>Current Password Hash: ' . substr(md5(get_option('rae_admin_password', 'test123')), 0, 8) . '...</p>';
            echo '</div>';
        }
    }
    
    public function manual_enable_field_callback() {
        $manual_enable = get_option('rae_manual_enable', false);
        echo '<label>';
        echo '<input type="checkbox" name="rae_manual_enable" value="1"' . checked(1, $manual_enable, false) . ' />';
        echo ' Force enable protection on this environment';
        echo '</label>';
        echo '<p class="description">Check this to enable password protection regardless of the domain. Useful for testing on localhost or production sites.</p>';
        echo '<p class="description"><strong>Warning:</strong> Enabling this on a production site will require all visitors to enter a password!</p>';
    }
    
    public function password_field_callback() {
        $password = get_option('rae_admin_password', 'test123');
        echo '<input type="password" name="rae_admin_password" value="' . esc_attr($password) . '" />';
        echo '<p class="description">Password for test environment access.</p>';
        echo '<p class="description"><strong>Note:</strong> After changing the password, you may need to clear your browser cache and cookies for this site.</p>';
    }
    
    public function admin_page() {
        // Handle session reset if requested
        if (isset($_GET['reset_sessions']) && wp_verify_nonce($_GET['_wpnonce'], 'reset_sessions')) {
            if (session_id()) {
                session_destroy();
            }
            echo '<div class="notice notice-success"><p>All sessions have been reset.</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>Restrict Access to Environment</h1>
            
            <div class="card">
                <h2>Environment Status</h2>
                <p><strong>Current URL:</strong> <?php echo esc_html(home_url()); ?></p>
                <p><strong>Current Host:</strong> <code><?php echo esc_html($_SERVER['HTTP_HOST'] ?? ''); ?></code></p>
                
                <?php 
                $manual_override = get_option('rae_manual_enable', false);
                $host = $_SERVER['HTTP_HOST'] ?? '';
                
                // Check what triggered the environment detection
                $triggers = array();
                if ($manual_override) {
                    $triggers[] = 'Manual Override';
                }
                if (strpos($host, 'test') !== false) {
                    $triggers[] = 'Contains "test"';
                }
                
                $localhost_patterns = array('localhost', '127.0.0.1', '::1', '.local', '.test', '.dev');
                foreach ($localhost_patterns as $pattern) {
                    if (strpos($host, $pattern) !== false) {
                        $triggers[] = 'Localhost pattern: ' . $pattern;
                        break;
                    }
                }
                ?>
                
                <p><strong>Protection Status:</strong> 
                    <?php if ($this->is_test_env): ?>
                        <span style="color: red; font-weight: bold;">ACTIVE</span>
                        <?php if (!empty($triggers)): ?>
                            <br><small>Triggered by: <?php echo implode(', ', $triggers); ?></small>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: green; font-weight: bold;">INACTIVE</span>
                    <?php endif; ?>
                </p>
                
                <p><strong>Current Session:</strong> <?php echo session_id() ? session_id() : 'Not started'; ?></p>
                <p><strong>Frontend Auth Status:</strong> <?php echo $this->is_authenticated() ? 'Authenticated' : 'Not authenticated'; ?></p>
            </div>
            
            <div class="card">
                <h2>Localhost Testing</h2>
                <p>The plugin now automatically detects localhost environments:</p>
                <ul>
                    <li><code>localhost</code></li>
                    <li><code>127.0.0.1</code></li>
                    <li><code>::1</code> (IPv6 localhost)</li>
                    <li>Domains ending with <code>.local</code>, <code>.test</code>, or <code>.dev</code></li>
                </ul>
                <p>You can also use the <strong>Manual Override</strong> option above to force enable protection on any environment.</p>
            </div>
            
            <div class="card">
                <h2>User Access & Testing</h2>
                <p><strong>To test the password protection:</strong></p>
                <ol>
                    <li><strong>As Administrator:</strong> You can visit the frontend directly (no password required)</li>
                    <li><strong>As Regular User:</strong> Log out of WordPress admin, then visit the frontend</li>
                    <li><strong>As Anonymous Visitor:</strong> Use an incognito/private browser window</li>
                </ol>
                
                <p><strong>Testing Different User Types:</strong></p>
                <ul>
                    <li><strong>Test Admin Access:</strong> <a href="<?php echo home_url(); ?>" target="_blank">Visit Frontend as Admin</a></li>
                    <li><strong>Test User Access:</strong> <a href="<?php echo wp_logout_url(home_url()); ?>" target="_blank">Logout & Visit Frontend</a></li>
                    <li><strong>Test Anonymous Access:</strong> Use incognito window to visit <code><?php echo home_url(); ?></code></li>
                </ul>
            </div>
            
            <div class="card">
                <h2>Troubleshooting</h2>
                <p>If you're having login issues:</p>
                <ol>
                    <li>Try clearing your browser cache and cookies</li>
                    <li>Use an incognito/private browsing window</li>
                    <li>Reset all active sessions using the button below</li>
                    <li>Check the debug information on the login page</li>
                    <li>For localhost: Ensure your local development server is properly configured</li>
                    <li><strong>Remember:</strong> Administrators bypass password protection automatically</li>
                </ol>
                <p>
                    <a href="<?php echo wp_nonce_url(add_query_arg('reset_sessions', '1'), 'reset_sessions'); ?>" 
                       class="button button-secondary"
                       onclick="return confirm('This will log out all users from the frontend. Continue?')">
                        Reset All Sessions
                    </a>
                </p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('rae_settings');
                do_settings_sections('rae_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new RestrictAccessToEnv(); 
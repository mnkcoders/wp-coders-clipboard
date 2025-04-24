<?php

defined('ABSPATH') or die;
/* * *****************************************************************************
 * Plugin Name: Coders Clipboard
 * Plugin URI: https://coderstheme.org
 * Description: Hierarchical clipbpard and drag-drop gallery
 * Version: 0.1
 * Author: Coder01
 * Author URI: 
 * License: GPLv2 or later
 * Text Domain: coders_clipboard
 * Domain Path: lang
 * Class: ClipBoard
 * 
 * @author Coder01 <coder01@mnkcoder.com>
 * **************************************************************************** */

final class CodersClipBoard {

    /**
     *
     * @var type CodersClipBoard
     */
    static $_instance = null;

    private final function __construct() {
        //
    }

    /**
     * @param String $name
     * @return boolean
     */
    private final function __is($name) {
        return false;
    }

    /**
     * @param String $name
     * @return boolean
     */
    private final function __has($name) {
        return false;
    }

    /**
     * @param String $name
     * @return boolean
     */
    private final function __value($name) {
        return '';
    }

    /**
     * @param String $name
     * @return boolean
     */
    private final function __input($name, array $args = array()) {
        return sprintf('<input name="" type="text" />', $name);
    }

    /**
     * @param String $name
     * @return boolean
     */
    private final function __count($name) {
        return 0;
    }

    /**
     * @param String $name
     * @return boolean
     */
    private final function __list($name) {
        return array();
    }

    /**
     * @param String $name
     * @param array $args
     */
    private final function __display($name, array $args = array()) {
        $path = sprintf('%s/%s/%s.php', CODERS_CLIPBOARD, is_admin() ? 'admin' : 'public', $name);
        if (file_exists($path)) {
            require $path;
        } else {
            printf('<!-- invalid display %s0 -->', $name);
        }
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return Mixed
     */
    public final function __call($name, $arguments) {
        switch ($name) {
            case preg_match('/^is_/', $name):
                return $this->__is(substr($name, 3));
            case preg_match('/^has_/', $name):
                return $this->__has(substr($name, 4));
            case preg_match('/^count_/', $name):
                return $this->__count(substr($name, 6));
            case preg_match('/^input_/', $name):
                $type = substr($name, 6);
                return $this->__input($name, $arguments);
            case preg_match('/^list_/', $name):
                return $this->__list(substr($name, 5));
            case preg_match('/^value_/', $name):
                return $this->__value(substr($name, 6));
            case preg_match('/^display_/', $name):
                return $this->__display(substr($name, 8), $arguments);
        }
        return sprintf('<!-- INVALID INPUT %s -->', $name);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public final function __get($name) {
        switch ($name) {
            case preg_match('/^is_/', $name):
                return $this->__is(substr($name, 3));
            case preg_match('/^has_/', $name):
                return $this->__has(substr($name, 4));
            case preg_match('/^count_/', $name):
                return $this->__count(substr($name, 6));
            case preg_match('/^list_/', $name):
                return $this->__list(substr($name, 5));
            case preg_match('/^value_/', $name):
                return $this->__value(substr($name, 6));
            case preg_match('/^display_/', $name):
                return $this->__display(substr($name, 8));
        }
    }

    /**
     * @param string $page
     */
    public final function admin($page = 'main') {
        if (is_admin()) {
            $this->__display($page);
        }
    }

    /**
     * @param String $id
     * @return String
     */
    public final function getPath($id = '') {
        $path = preg_replace('/\\\\/', '/', sprintf('%s/coders/clipboard/', wp_upload_dir()['basedir']));
        return strlen($id) ? $path . '/' . $id : $path;
    }

    /**
     * @param String $id
     * @return String
     */
    public final function getUrl($id = '') {
        $url = sprintf('%s/coders/clipboard/', wp_upload_dir()['baseurl']);
        return strlen($id) ? $url . '/' . $id : $url;
    }
    /**
     * @param String $id
     * @return string
     */
    public final function load( $id ){
        $path = $this->getPath($id);
        if(file_exists($path)){
            $buffer = file_get_contents($path);
            if( $buffer !== false ){
                return $buffer;
            }
        }
        return '';
    }
    /**
     * @param String $id
     */
    public final function source( $id ){
        if(strlen($id)){
            $buffer = $this->load($id);
            if(strlen($buffer)){
                header( sprintf("Content-type: image/png; Content-Disposition: inline; filename=%s.png",'image') );
                print $buffer;
            }            
        }
        print ':D';
    }

    /**
     * @return CodersClipBoard
     */
    public static final function instance() {
        if (is_null(self::$_instance)) {
            define('CODERS_CLIPBOARD', preg_replace('/\\\\/', '/', __DIR__));
            self::$_instance = new CodersClipBoard();
        }
        return self::$_instance;
    }
    /**
     * @return String
     */
    public static final function URL(){
        return plugin_dir_url(__FILE__);
    }
}

/**
 * Register hooks
 */
if (is_admin()) {
    add_action('admin_menu', function() {
        add_menu_page(
                __('Clipboard', 'coders_clipboard'),
                __('Clipboard', 'coders_clipboard'),
                'administrator',
                'coders-clipboard',
                function() {
            CodersClipBoard::instance()->admin();
        },
                'dashicons-art', 20);
        add_submenu_page(
                'coders-clipboard',
                __('Settings', 'coders_clipboard'),
                __('Settings', 'coders_clipboard'),
                'administrator',
                'coders-clipboard-settings',
                function() {
            CodersClipBoard::instance()->admin('settings');
        });
    });
}

add_action('init', function() {
    //public
    global $wp;
    $wp->add_query_var('clipboard');
    /* SETUP RESPONSE */
    add_action('template_redirect', function() {
        global $wp_query;
        $id = array_key_exists('clipboard', $wp_query->query) ? $wp_query->query['clipboard'] : '';
        $wp_query->set('is_404', FALSE);
        CodersClipBoard::instance()->source($id);
        exit;
    }, 10);
    
    if(is_admin()){
        // Enqueue JavaScript file for a specific admin page
        function enqueue_custom_admin_script( $hook ) {
            // Check if the current page is the desired admin page
            if ( 'edit.php' === $hook && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'custom_media' ) {
                // Enqueue the JavaScript file
                wp_enqueue_script( 'custom-admin-script', get_template_directory_uri() . '/js/admin-script.js', array( 'jquery' ), '1.0', true );
            }
        }
        add_action( 'admin_enqueue_scripts', function( $hook ){
            $page = filter_input(INPUT_GET, 'page');
            if ( preg_match('/coders-clipboard$/', $hook) && $page === 'coders-clipboard' ) {
                // Enqueue the JavaScript file
                wp_enqueue_script( 'coders-clipboard-script',
                        sprintf('%s/admin/script.js', CodersClipBoard::URL()),
                        array( 'jquery' ), '1.0', true );
            }            
        } );        
    }
}, 10);

register_activation_hook(__FILE__, function( ){
    global $wp_rewrite, $wp;
    $endpoint = 'clipboard';
    $wp_rewrite->add_endpoint($endpoint, EP_ROOT);
    $wp->add_query_var($endpoint);
    $wp_rewrite->add_rule("^/$endpoint/?$", 'index.php?' . $endpoint . '=$matches[1]', 'top');
    $wp_rewrite->flush_rules();
});

register_deactivation_hook(__FILE__, function(){
    //remove endpoint
});
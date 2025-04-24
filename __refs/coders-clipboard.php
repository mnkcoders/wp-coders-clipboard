<?php defined('ABSPATH') or die;
/* * *****************************************************************************
 * Plugin Name: Coders Clipboard
 * Plugin URI: https://coderstheme.org
 * Description: Hierarchical clipbpard and drag-drop gallery
 * Version: 0.2
 * Author: Coder01
 * Author URI: 
 * License: GPLv2 or later
 * Text Domain: coders_clipboard
 * Domain Path: lang
 * Class: ClipBoard
 * 
 * @author Coder01 <coder01@mnkcoder.com>
 * **************************************************************************** */

add_action( 'register_coder_app', 'register_clipboard_app',10,1);
register_activation_hook(__FILE__, 'install_clipboard_app');
register_deactivation_hook(__FILE__, 'uninstall_clipboard_app');


function register_clipboard_app( array &$apps ){
    $apps[] = __DIR__;
    return $apps;
}
function install_clipboard_app() {
    if( class_exists('CodersApp') ){
        CodersApp::install(__DIR__);
    }
}
function uninstall_clipboard_app() {
    if( class_exists('CodersApp') ){
        CodersApp::uninstall(__DIR__ );
    }
}




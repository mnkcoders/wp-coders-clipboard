<?php defined('ABSPATH') or die;
/* * *****************************************************************************
 * Plugin Name: Coders Clipboard (Refactor 1)
 * Description: Multi-Level Drag-Drop media gallery with access control and collection display
 * Version: 0.81
 * Author: Coder01
 * License: GPLv2 or later
 * Text Domain: coders_clipboard
 * Domain Path: lang
 * Class: Clipboard
 * **************************************************************************** */

define('CODER_CLIPBOARD_DIR', plugin_dir_path(__FILE__));
define('CODER_CLIPBOARD_URL', plugin_dir_url(__FILE__));
define('CODER_CLIPBOARD_DATA','clipdata');
define('CODER_CLIPBOARD_VIEW','clipboard');

require_once sprintf('%s/lib/clipboard.php', CODER_CLIPBOARD_DIR);

// Activation Hook
register_activation_hook(__FILE__, function() {
    \CODERS\Clipboard\Clipboard::setup();
});

register_deactivation_hook(__FILE__, function() {
    
    flush_rewrite_rules();
});

//To define a hook to fill the bottom bar
//add_action('coders_sidebar',function($provider = 'clipboard',$context = ''){},10,2);


// Redirect Handler
add_action('template_redirect', function(){
    $clip_id = get_query_var('clip_id');
    if( $clip_id ){
        \CODERS\Clipboard\Clipboard::attach( $clip_id );
        exit;
    }
    $clipboard_id = get_query_var('clipboard_id');
    if( $clipboard_id ){
        \CODERS\Clipboard\Clipboard::board( $clipboard_id );
        exit;
    }
});

add_action('init', function() {

    if(is_admin()){
        require_once sprintf('%s/lib/admin.php', CODER_CLIPBOARD_DIR);
    }
    else{
        // Rewrite Rules
        \CODERS\Clipboard\Clipboard::rewrite();
    }

    add_filter('query_vars', function($vars) {
        $vars[] = 'clip_id';
        $vars[] = 'clipboard_id';
        return $vars;
    });

    //use to test the coder_acl access tier
    add_filter('coder_role', function($role ) {
        return 'silver';
    }, 10, 2);

    /*add_shortcode('clipboard_view', function($atts){
        $atts = shortcode_atts(['id' => ''], $atts);
        ob_start();
        Clipboard::display($atts['id']);
        return ob_get_clean();
    });*/
});






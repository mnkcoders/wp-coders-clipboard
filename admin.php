<?php

defined('ABSPATH') or die;

/**
 * @class ClipBoardAdmin
 */
class ClipboardAdmin extends Clipboard {

    protected function __construct($id = '') {

        parent::__construct($id);

        if (!$this->isValid()) {
            //load the root gallery items
            $this->addItems(self::children());
        }
    }

    /**
     * @param string $name
     * @return string
     */
    protected static function createId($name = '') {
        $seed = $name . microtime(true) . rand();
        return substr(md5($seed), 0, 16); // Shorten if you want fixed-length IDs
    }

    protected function create($name) {
        
    }

    /**
     * @param Stsring $content
     */
    public static final function display($content = '') {
        $id = filter_input(INPUT_GET, 'id');
        $clipboard = new ClipboardAdmin(!is_null($id) ? $id : '');
        $clipboard->page(strlen($content) ? $content : 'default' );
    }

    /**
     * @param String $page
     * @return ClipboardAdmin
     */
    private final function page($page = 'default') {

        $view = $this->view($page, 'admin');

        if (file_exists($view)) {
            include $view;
        } else {
            printf('<p>:( %s</p>', $view);
        }
        return $this;
    }

    /**
     * 
     * @param string $ids
     * @return string
     */
    protected final function getPost($ids = array()) {
        //return parent::getLink($ids);
        return add_query_arg([
            'page' => 'coders-clipboard',
            'id' => $ids[0],
                ], admin_url('admin.php'));
    }
    /**
     * @return string
     */
    protected final function getForm(){
        return admin_url('admin-post.php');
    }
    
    
    public static final function upload(){
        
    }
    
    public static final function FILES(){
        
        $files = isset($_FILES['upload']) ? $_FILES['upload'] : [];
        
        $output = array();
        
        foreach( $files['name'] as $index => $name ){
            if ($files['error'][$index] === UPLOAD_ERR_OK) {
                $file_array = [
                    'name'     => $files['name'][$index],
                    'type'     => $files['type'][$index],
                    'tmp_name' => $files['tmp_name'][$index],
                    'error'    => $files['error'][$index],
                    'size'     => $files['size'][$index]
                ];

                // Use WordPress media handler
                $attachment_id = media_handle_sideload($file_array, 0); // 0 = no post parent

                if (is_wp_error($attachment_id)) {
                    $uploaded[] = ['error' => $attachment_id->get_error_message()];
                } else {
                    $uploaded[] = ['id' => $attachment_id, 'url' => wp_get_attachment_url($attachment_id)];
                }
            }
        }
        
        return $output;
    }

}


add_action('admin_post_cod_clipboard_upload', function(){
    ClipboardAdmin::upload( ClipboardAdmin::FILES() );
});        


add_action('admin_enqueue_scripts', function( $hook ) {


    // Plugin folder URL
    //$plugin_url = plugin_dir_url(__FILE__);
    $plugin_url = ClipboardAdmin::assetUrl();

    // Register and enqueue CSS
    wp_enqueue_style(
            'clipboard-admin-style',
            ClipboardAdmin::assetUrl('html/admin/style.css'),
            [],
            filemtime(ClipboardAdmin::assetPath('html/admin/style.css'))
    );

    // Register and enqueue JS
    wp_enqueue_script(
            'clipboard-admin-script',
            ClipboardAdmin::assetUrl('html/admin/script.js'),
            ['jquery'],
            filemtime(ClipboardAdmin::assetPath('html/admin/script.js')),
            true
    );

    // Optional: Pass variables to JS
    wp_localize_script('clipboard-admin-script', 'ClipboardData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('clipboard_nonce')
    ]);
});


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
    public static function createId($name = '') {
        $seed = $name . microtime(true) . rand();
        return substr(md5($seed), 0, 16); // Shorten if you want fixed-length IDs
    }

    /**
     * @param Stsring $page
     */
    public static final function display($page = '') {
        $id = filter_input(INPUT_GET, 'id');
        $clipboard = new ClipboardAdmin(!is_null($id) ? $id : '');
        $clipboard->page(strlen($page) ? $page : 'default' );
    }

    /**
     * @param String $page
     * @return ClipboardAdmin
     */
    private final function page($page = 'default') {

        $view = $this->__render($page, 'admin');

        if (file_exists($view)) {
            include $view;
        } else {
            printf('<p>:( %s</p>', $view);
        }
        return $this;
    }

    /**
     * @return array
     */
    protected final function listLayouts() {
        return array(
            'default' => __('Default', 'coders_clipboard'),
            'ecomic' => __('e-Comic', 'coders_clipboard'),
            'collection' => __('Collections', 'coders_clipboard'),
            'gallery' => __('Galleries', 'coders_clipboard'),
            'slideshow' => __('Slideshows', 'coders_clipboard'),
            'showcase' => __('Showcase', 'coders_clipboard'),
        );
    }

    /**
     * @return array
     */
    protected final function listRoles() {
        return array(
            'private' => __('Private', 'coders_clipboard'),
            'public' => __('Public', 'coders_clipboard'),
                //add more roles here
        );
    }

    /**
     * 
     * @param string $ids
     * @return string
     */
    protected final function getPost($ids = array()) {
        //return parent::getLink($ids);
        return add_query_arg([
            'page' => 'coders_clipboard',
            'id' => $ids[0],
                ], admin_url('admin.php'));
    }

    /**
     * @return string
     */
    protected final function getForm() {
        return admin_url('admin-post.php');
    }

    /**
     * @global type $wpdb
     * @param array $file
     * @return boolean
     */
    protected static final function save($file = array()) {
        if (count($file)) {
            global $wpdb;

            $table = ClipBoard::table();
            
            $parent_id = isset($file['parent_id']) && strlen($file['parent_id']) ? $file['parent_id'] : null;

            $name = mb_strimwidth($file['name'],0,32);
            $data = array(
                'id' => $file['id'],
                'name' => sanitize_file_name($name),
                'title' => $name,
                'description' => '',
                'type' => $file['type'],
                'parent_id' => $parent_id,
                'acl' => 'private',
                'created_at' => current_time('mysql'),
            );
            // Insert into database
            $result = $wpdb->insert($table, $data);

            if ($result === false) {
                var_dump($wpdb->last_error);
            }

            return $result !== false;
        }
        return false;
    }

    /**
     * @param array $files
     * @param string $id
     * @return boolean
     */
    public static final function upload($files = array(), $id = '') {

        $parent_id = '';
        $count = 0;
        foreach ($files as $file) {
            $file['parent_id'] = strlen($parent_id) > 0 ? $parent_id : $id;
            if (self::save($file)) {
                $count++;
            }
            if(strlen($parent_id) === 0){
                //now set all files into the first uploaded file (collection header)
                $parent_id = $file['id'];
            }
        }
        return $count === count($files);
    }
    /**
     * @param array $input
     */
    public static final function action( $input = array() ){
        $action = array_key_exists('action', $input) ? $input['action'] : '';
        $id = array_key_exists('id', $input) ? $input['id'] : '';
        $output = array();
        switch ($action ){
            case 'upload':
                $count = ClipboardAdminContent::upload( $input['upload'] , $id );
                $output['count'] = $count;
                break;
            case 'update':
                $content = ClipboardAdminContent::load($id);
                if (!is_null($content) && $content->override($input)->update()) {
                    $output[$action] = 'done';
                }
                break;
            case 'remove':
                $content = ClipboardAdminContent::load($id);
                if (!is_null($content) && $content->remove()) {
                    $output[$action] = 'done';
                }
                break;
            case 'sort':
                $content = ClipboardAdminContent::load($id);
                //get here the required position indexes
                if (!is_null($content) && $content->sort()) {
                    $output[$action] = 'done';
                }
                break;
            case 'moveup':
                $content = ClipboardAdminContent::load($id);
                //get here the upper parent Id
                if (!is_null($content) && $content->moveup()) {
                    $output[$action] = 'done';
                }
                break;
            case 'moveto':
                $content = ClipboardAdminContent::load($id);
                //get here the selected container id
                if (!is_null($content) && $content->moveto()) {
                    $output[$action] = 'done';
                }
                break;
            case 'clear_content':
                $output['page'] = 'settings';
                if( ClipboardAdminContent::resetContent() ){
                    $output[$action] = 'done';
                }
                break;
        }
        
        return array();
    }
}

/**
 * 
 */
class ClipboardAdminContent extends ClipBoardContent {

    private $_updated = false;
    
    /**
     * @param array $input
     */
    protected final function __construct($input = array()) {

        parent::__construct($input);
    }

    /**
     * @param array $input
     * @return \ClipboardAdminContent
     */
    protected function override($input = array()) {
        foreach (['id', 'created_at'] as $key)
            unset($input[$key]);
        $this->populate($input);
        $this->_updated = true;
        return $this;
    }

    /**
     * @return string
     */
    protected final function defaultAcl() {
        return 'private';
    }
    /**
     * @return boolean
     */
    protected final function updated(){
        return $this->_updated;
    }

    /**
     * @return boolean
     */
    public final function update() {
        global $wpdb;
        if ($this->isValid() && $this->updated()) {
            $data = array(
                'name' => sanitize_file_name($this->name),
                'title' => sanitize_text_field($this->title),
                'description' => sanitize_textarea_field($this->description),
                'acl' => sanitize_text_field($this->acl),
                'layout' => sanitize_text_field($this->layout),
            );

            $result = $wpdb->update(self::table(), $data, array('id' => $this->id));

            if ( $result !== false ) {
                $this->_updated = false;
                return true;
            }
            error_log('Clipboard update failed: ' . $wpdb->last_error);
        }
        return false;
    }
    /**
     * @return boolean
     */
    public final function remove(){
        return false;
    }
    /**
     * @global wpdb $wpdb
     * @return boolean
     */
    private final function save() {
        if ($this->isValid()) {
            global $wpdb;

            $table = self::table();

            $content = array(
                'id' => $this->id,
                'name' => sanitize_file_name($this->name),
                'title' => $this->name,
                'description' => '',
                'type' => $this->type,
                'parent_id' => strlen($this->parent_id) ? $this->parent_id : null,
                'acl' => $this->defaultAcl(),
                'created_at' => current_time('mysql'),
            );
            // Insert into database
            $result = $wpdb->insert($table, $content);

            if ($result === false) {
                var_dump($wpdb->last_error);
                die;
            }

            return $result !== false;
        }
        return false;
    }

    /**
     * @param string $from Description
     * @param string $id Description
     * @return \ClipboardAdminContent[]
     */
    public static final function upload( $from = 'upload', $id = '' ) {
        $uploaded = array();
        $parent_id = '';
        foreach (ClipboardUploader::upload($from)->files() as $file) {
            //set the first parent id to the parsed ID
            $file['parent_id'] = strlen($parent_id) ? $parent_id : $id;
            //then set the next to the first file ID
            if(strlen($parent_id) === 0){
                $parent_id = $file['id'];
            }
            $content = new ClipboardAdminContent($file);
            if ($content->save()) {
                $uploaded[$content->id] = $content;
            }
        }
        return $uploaded;
    }
    
    /**
     * @global wpdb $wpdb
     * @param type $id
     * @return \ClipBoardContent
     */
    public static final function load( $id = '' ){
        $item = parent::load($id);
        if( !is_null($item)){
            return new ClipboardAdminContent($item->content());
        }
        return null;
    }
    /**
     * @global wpdb $wpdb
     * @return boolean
     */
    public static final function resetContent() {
        global $wpdb;
        $done = 0;
        // 1. Truncate DB table
        $result = $wpdb->query(sprintf('TRUNCATE TABLE `%s`', self::table()));

        if ($result !== false) {
            $done++;
        }


        // 2. Delete files from uploads/clipboard
        $folder = Clipboard::path();

        if (file_exists($folder)) {
            $files = glob($folder . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $done++;
        }
        return $done > 1;
    }

}

/**
 * Upload Manager for new contents
 */
class ClipboardUploader {

    private $_files = array();

    private function __construct($files = array()) {


        $this->_files = $files;
    }

    /**
     * @return array
     */
    public final function files() {
        return $this->_files;
    }

    /**
     * @param string $upload
     * @return array
     */
    private static final function import($upload = 'upload') {

        $files = array_key_exists($upload, $_FILES) ? $_FILES[$upload] : array();
        $output = array();
        if (count($files)) {
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    $output[] = array(
                        'name' => $files['name'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'type' => $files['type'][$i],
                        'error' => $files['error'][$i],
                    );
                }
            } else {
                $output[] = $files;
            }
        }
        return $output;
    }

    /**
     * @param string $error
     * @return boolean
     * @throws \Exception
     */
    private static final function validate($error = '') {
        try {
            switch ($error) {
                case UPLOAD_ERR_CANT_WRITE:
                    throw new \Exception('UPLOAD_ERROR_READ_ONLY');
                case UPLOAD_ERR_EXTENSION:
                    throw new \Exception('UPLOAD_ERROR_INVALID_EXTENSION');
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \Exception('UPLOAD_ERROR_SIZE_OVERFLOW');
                case UPLOAD_ERR_INI_SIZE:
                    throw new \Exception('UPLOAD_ERROR_CFG_OVERFLOW');
                case UPLOAD_ERR_NO_FILE:
                    throw new \Exception('UPLOAD_ERROR_NO_FILE');
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new \Exception('UPLOAD_ERROR_INVALID_TMP_DIR');
                case UPLOAD_ERR_PARTIAL:
                    throw new \Exception('UPLOAD_ERROR_INCOMPLETE');
                case UPLOAD_ERR_OK:
                    return true;
            }
        } catch (Exception $ex) {
            var_dump($ex->getMessage());
        }
        return false;
    }

    /**
     * @return \ClipboardUploader
     */
    public static final function upload($from = 'upload') {

        $input = self::import($from);

        $files = array();

        foreach ($input as $upload) {
            if (self::validate($upload['error'])) {
                $upload['id'] = ClipboardAdmin::createId($upload['name']);
                $upload['path'] = Clipboard::path($upload['id']);
                // Move uploaded file
                if (move_uploaded_file($upload['tmp_name'], $upload['path'])) {
                    //unlink($upload['tmp_name']);
                    $upload['size'] = filesize($upload['path']);
                    unset($upload['tmp_name']);
                    //$files[$upload['id']] = $upload;
                    $files[] = $upload;
                } else {
                    return new WP_Error('upload_failed', 'Failed to move uploaded file.');
                }
            }
        }
        return new ClipboardUploader($files);
    }

}

add_action('admin_post_clipboard_upload', function() {

    $id = filter_input(INPUT_POST, 'parent_id') ?? '';
    $uploaded = ClipboardAdmin::upload( ClipboardUploader::upload()->files(), $id);

    $redirect = array(
        'page' => 'coders_clipboard',
        'upload' => $uploaded > 0 ? 'success' : 'failure',
        'count' => $uploaded,
    );

    if (strlen($id)) {
        $redirect['id'] = $id;
    }

    wp_redirect(add_query_arg($redirect, admin_url('admin.php')));
    exit;
});

add_action('admin_post_clipboard_update', function() {

    $post = filter_input_array(INPUT_POST);
    $id = array_key_exists('id', $post) ? $post['id'] : '';
    $updated = false;
    if(strlen($id)){
        $content = ClipboardAdminContent::load($id);
        if(!is_null($content)){
            if( $content->override($post)->update()){
                //send to admin notifier
                $updated = true;
            }
        }
    }

    $redirect = array(
        'page' => 'coders_clipboard',
        'update' => $updated ? 'success' : 'failure',
    );

    if (strlen($id)) {
        $redirect['id'] = $id;
    }

    wp_redirect(add_query_arg($redirect, admin_url('admin.php')));
    exit;
});

add_action('admin_post_clipboard_action', function() {
    if ( ! current_user_can('upload_files') ) {
        wp_die(__('Unauthorized', 'coders_clipboard'));
    }
    $post = filter_input_array(INPUT_POST) ?? array();
    
    $redirect = ClipboardAdmin::action($post);
    
    if( !array_key_exists('page', $redirect)){
        $redirect['page'] = 'coders_clipboard';
    }
    
    wp_redirect(add_query_arg($redirect, admin_url('admin.php')));
    exit;        
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


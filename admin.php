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
            'page' => 'coders-clipboard',
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
     * @param string $parent_id Description
     * @return boolean
     */
    protected static final function save($file = array(), $parent_id = '') {
        if (count($file)) {
            global $wpdb;

            $table = ClipBoard::table();

            $data = array(
                'id' => $file['id'],
                'name' => sanitize_file_name($file['name']),
                'title' => $file['name'],
                'description' => '',
                'type' => $file['type'],
                'parent_id' => strlen($parent_id) ? $parent_id : null,
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
     * @param string $parent_id
     * @return boolean
     */
    public static final function upload($files = array(), $parent_id = '') {

        $count = 0;
        foreach ($files as $file) {
            if (self::save($file, $parent_id)) {
                $count++;
            }
        }
        return $count === count($files);
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
     * @return \ClipboardAdminContent[]
     */
    public static final function upload() {
        $uploaded = array();
        foreach (ClipboardUploader::upload()->files() as $files) {
            $content = new ClipboardAdminContent($files);
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
    public static final function upload() {

        $input = self::import('upload');

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

    $uploaded = ClipboardAdmin::upload(ClipboardUploader::upload()->files(), $id);
    $redirect = array(
        'page' => 'coders-clipboard',
        'upload' => 'success',
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
    
    if( isset($post['id'])){
        $content = ClipboardAdminContent::load($post['id']);
        if(!is_null($content)){
            if( $content->override($post)->update()){
                //send to admin notifier
            }
        }
    }

    $uploaded = ClipboardAdmin::upload(ClipboardUploader::upload()->files(), $id);
    $redirect = array(
        'page' => 'coders-clipboard',
        'upload' => 'success',
        'count' => $uploaded,
    );

    if (strlen($id)) {
        $redirect['id'] = $id;
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


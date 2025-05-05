<?php

defined('ABSPATH') or die;

/**
 * @class ClipboardAdmin
 */
class ClipboardAdmin extends Clipboard {
    
    const CONTEXT_MAIN = 'main';
    const CONTEXT_SETTINGS = 'settings';
    
    private $_page = self::CONTEXT_MAIN;
    /**
     * 
     * @param String $id
     */
    protected function __construct( $id = '') {

        parent::__construct($id);
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
     * @param Stsring $context_id
     */
    public static final function display($page = '' , $context_id = '') {
        $input = filter_input_array(INPUT_GET) ?? array();
        //ClipboardAdmin::sendMessage('Hello!');
        if(array_key_exists('task', $input)){
            $input = self::task($input);
            //return self::redirect($input);
        }
        $id = array_key_exists('context_id', $input) ? $input['context_id'] : $context_id;
        $clipboard = new ClipboardAdmin( $id );
        $clipboard->__layout(strlen($page) ? $page : 'default','admin');
    }
    /**
     * 
     * @param type $name
     * @param type $arguments
     * @return string
     */
    public final function __call($name, $arguments) {
        switch( true ){
            case $name === 'page':
                return $this->page();
            case preg_match('/^editor_/', $name):
                $this->__editor(substr($name, 7), ... $arguments );
            default:
                return parent::__call($name, $arguments);
        }
    }
    /**
     * @param string $name
     */
    private function __editor( $name ) {
        $content = $this->hasContent() ? $this->content()->description : '';
        $id = 'id_'. $name;
        $settings = [
            'textarea_name' => $name, // The name attribute
            'editor_height' => 250,
            'media_buttons' => true,
            'tinymce'       => true,
            'quicktags'     => true
        ];

        wp_editor($content, $id, $settings);
    }

    /**
     * @param string $action
     * @param array $request
     * @return string
     */
    protected function __link($action, $request = array()) {
        //parent::__link($action, $arguments);
        
        if(!array_key_exists('page', $request)){
            $request['page'] = 'coders_clipboard';
        }
        $request['task'] = $action;
        
        return add_query_arg($request, admin_url('admin.php'));
    }     
    /**
     * @return String
     */
    public function page(){
        return $this->_page;
    }
    /**
     * @return Boolean
     */
    protected function isMain(){
        return $this->page() === self::CONTEXT_MAIN;
    }
    /**
     * @return Boolean
     */
    protected function isSettings(){
        return $this->page() === self::CONTEXT_MAIN;
    }
    /**
     * @return Boolean
     */
    public function isMedia(){
        return $this->hasContent() && $this->content()->isMedia();
    }
    /**
     * @param string $id
     * @param string $parent_id
     * @return string
     */
    protected function actionMove( $id = '', $parent_id = '' ){
        if(strlen($id)){
            $request = array('id'=> $id);
            if(strlen($parent_id)){
                $request['parent_id'] = $parent_id;
            }
            if( $this->hasContent() ){
                $request['context_id'] = $this->id;
            }
            return $this->__link('move',$request);
        }
        return '';
    }
    /**
     * @return string
     */
    protected function actionDelete($id=''){
        $request = array('id'=> $id);
        if( $this->hasContent()){
            $context = $this->id !== $id ? $this->id : $this->parent_id;
            if(strlen($context)){
                $request['context_id'] = $context;                
            }
        }
        return $this->__link('delete',$request);
    }
    /**
     * @return string
     */
    protected function actionPropagate(){
        $request = array('id'=>$this->id);
        return $this->__link('propagate',$request);
    }
    /**
     * @return string
     */
    protected function actionLayout(){
        $request = array('id'=>$this->id);
        return $this->__link('layout',$request);
    }
    /**
     * @return string
     */
    protected function actionRecover(){
        $request = array('id'=>$this->id);
        return $this->__link('recover',$request);
    }
    /**
     * @return string
     */
    protected function actionArrange(){
        $request = array('context_id'=>$this->id);
        return $this->__link('arrange',$request);
    }
    /**
     * @return string
     */
    protected function actionSort( $id = '', $slot = 0 ){
        if(strlen($id) && $slot ){
            $request = array(
                'id' => $id,
                'slot' => $slot,
            );
            if( $this->hasContent() ){
                $request['context_id'] = $this->parent_id;
            }            
            return $this->__link('sort', $request);            
        }
        return '#';
    }
    /**
     * @return string
     */
    protected function actionRenameAll(){
        $request = array('id'=>$this->id,'context_id'=>$this->id);
        return $this->__link('renameall',$request);
    }

        
    /**
     * @return array
     */
    protected function listLayouts() {
        return array(
            'default' => __('Default', 'coders_clipboard'),
            'ecomic' => __('e-Comic', 'coders_clipboard'),
            'collection' => __('Collection', 'coders_clipboard'),
            'gallery' => __('Gallery', 'coders_clipboard'),
            'slideshow' => __('Slideshow', 'coders_clipboard'),
            'portfolio' => __('Portfolio', 'coders_clipboard'),
            'showcase' => __('Showcase', 'coders_clipboard'),
        );
    }

    /**
     * @return array
     */
    protected function listRoles() {
        $roles = array(
            'private' => __('Private', 'coders_clipboard'),
            'public' => __('Public', 'coders_clipboard'),
        );
        $tiers = apply_filters('coders_clipboard_tiers',array());
        foreach($tiers as $tier => $name){
            if( !isset($roles[$tier])){
                $roles[$tier] = $name;
            }
        }
        return $roles;
    }
    /**
     * 
     * @param string $ids
     * @return string
     */
    protected function getPost($ids = array()) {
        $query = array('page'=>'coders_clipboard');
        if(count($ids)){
            $query['context_id'] = $ids[0];
        }
        return add_query_arg($query, admin_url('admin.php'));
    }

    /**
     * @return string
     */
    protected function getForm() {
        return admin_url('admin-post.php?action=clipboard_action');
    }
    /**
     * @return string
     */
    protected function getCss(){
        return $this->hasContent() ? $this->content()->getCss() : 'empty';
    }
    /**
     * @param string $layout
     * @return string
     */
    protected function getCurrentLayout( $layout ){
        $item = is_array($layout) ? $layout[0] : $layout;
        return $this->isValid() && $this->content()->layout === $item ? 'selected' : '';
    }
    /**
     * @param string $role
     * @return string
     */
    protected function getRole( $role ){
        $item = is_array($role) ? $role[0] : $role;
        return $this->isValid() && $this->content()->acl === $item ? 'selected' : '';
    }
    /**
     * @param string $from Description
     * @param string $id Description
     * @return \ClipboardContent[]
     */
    private static function upload( $from = 'upload', $id = '' ) {
        $uploaded = array();
        $slot = ClipboardContent::count($id);
        $parent_id = strlen($id) ? $id : '';
        foreach (ClipboardUploader::upload($from)->files() as $file) {
            //set the first parent id to the parsed ID
            $file['parent_id'] = $parent_id;
            $file['slot'] = ++$slot;
            //if the upload is in the root collection
            if( strlen($parent_id) === 0){
                //set the header file as the parent
                $parent_id = $file['id'];
            }
            
            $content = new ClipboardContent($file);
            $content->tagImageSize();
            if ($content->create()) {
                $uploaded[] = $content->post();
            }
            //then set the next to the first file ID
            if(strlen($id) === 0){
                $id = $file['id'];
            }
        }

        return $uploaded;
    }    
    /**
     * @param array $input
     */
    public static final function redirect( $input = array()){
        if( !array_key_exists('page', $input)){
            $input['page'] = 'coders_clipboard';
        }

        wp_redirect(add_query_arg($input, admin_url('admin.php')));
        exit;
    }
    /**
     * @param array $input
     */
    public static final function task( $input = array() ){
        $task = array_key_exists('task', $input) ? $input['task'] : '';
        $context_id = array_key_exists('context_id', $input) ? $input['context_id'] : '';
        $id = array_key_exists('id', $input) ? $input['id'] : '';
        $parent_id = array_key_exists('parent_id', $input) ? $input['parent_id'] : '';
        $output = array();
        if( strlen($id)){
            $output['id'] = $id;
        }
        if( strlen($context_id)){
            $output['context_id'] = $context_id;
        }
        
        switch ($task ){
            case 'upload':
                $uploaded = self::upload( 'upload' , $id );
                $output['content'] = $uploaded;
                break;
            case 'update':
                $content = ClipboardContent::load($id);
                if (!is_null($content) && $content->override($input)->update()) {
                    $output[$task] = 'done';
                }
                break;
            case 'delete':
                $content = ClipboardContent::load($id);
                if (!is_null($content) && $content->remove()) {
                    $output[$task] = 'done';
                    if( $content->id === $context_id){
                        $context_id = $content->parent_id;
                    }
                    $output['context_id'] = $context_id;
                }
                break;
            case 'sort':
                $item = ClipboardContent::load($id);
                $index = isset($input['slot']) ? $input['slot'] : 0;
                if (!is_null($item)) {
                    $count = $item->sort($index);
                    $output['slot'] = $index;
                    $output['count'] = $count;
                    $output[$task] = 'done';
                }
                break;
            case 'move':
                $content = ClipboardContent::load($id);
                if (!is_null($content) && $content->moveto($parent_id)) {
                    $output[$task] = 'done';
                }
                break;
            case 'moveup':
                $content = ClipboardContent::load($id);
                //get here the upper parent Id
                if (!is_null($content) && $content->moveup()) {
                    $output[$task] = 'done';
                }
                break;
            case 'moveto':
                $content = ClipboardContent::load($id);
                //get here the selected container id
                if (!is_null($content) && $content->moveto()) {
                    $output[$task] = 'done';
                }
                break;
            case 'recover':
                $count = ClipboardContent::fetchLost($id);
                if( $count ){
                    $output[$task] = 'done';
                    $output['count'] = $count;
                }
                break;
            case 'renameall':
                $count = ClipboardContent::renameAll($id);
                if( $count ){
                    $output[$task] = 'done';
                    $output['count'] = $count;
                }
                break;
            case 'propagate':
                $count = ClipboardContent::copyPermissions($id);
                if( $count ){
                    $output[$task] = 'done';
                    $output['count'] = $count;
                }
                break;
            case 'layout':
                $count = ClipboardContent::copyLayouts($id);
                if( $count ){
                    $output[$task] = 'done';
                    $output['count'] = $count;
                }
                break;
            case 'arrange':
                $count = ClipboardContent::arrange($context_id);
                $output[$task] = 'done';
                $output['count'] = $count;
                break;
            case 'nuke':
                $output['page'] = self::CONTEXT_SETTINGS;
                if( ClipboardContent::resetContent() ){
                    $output[$task] = 'done';
                }
                break;
        }
        
        return $output;
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
    private static function import($upload = 'upload') {

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
    private static function validate($error = '') {
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
            ClipboardAdmin::sendMessage($ex->getMessage(),'error');
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
                    ClipboardAdmin::sendMessage(
                            __('Failed to move uploaded file','coders_clipboard') . ' ' . $upload['name'],
                            'error');
                    //return new WP_Error('upload_failed', 'Failed to move uploaded file.');
                }
            }
        }
        return new ClipboardUploader($files);
    }
}

add_action('admin_post_clipboard_action', function() {
    if ( ! current_user_can('upload_files') ) {
        wp_die(__('Unauthorized', 'coders_clipboard'));
    }
    $post = filter_input_array(INPUT_POST) ?? array();
    
    $redirect = ClipboardAdmin::task($post);
    
    if( !array_key_exists('page', $redirect)){
        $redirect['page'] = 'coders_clipboard';
    }

    wp_redirect(add_query_arg($redirect, admin_url('admin.php')));
    exit;        
});

add_action('wp_ajax_clipboard_action', function() {

    if ( ! current_user_can('upload_files') ) {
        wp_die(__('Unauthorized', 'coders_clipboard'));
    }

    $post = filter_input_array(INPUT_POST) ?? array();
    
    //wp_send_json_success($_FILES);
    //return ;
    
    $response = ClipboardAdmin::task($post);
    
    wp_send_json_success($response);
    
    //exit;        
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



add_action('admin_menu', function () {
    add_menu_page(
            __('Clipboard', 'coders_clipboard'),
            __('Clipboard', 'coders_clipboard'),
            'upload_files', // or 'manage_options' if more restricted
            'coders_clipboard',
            function () { ClipboardAdmin::display(); },
            'dashicons-art',80);
    add_submenu_page(
            'coders_clipboard',
            __('Settings', 'coders_clipboard'),
            __('Settings', 'coders_clipboard'),
            'manage_options',
            'coders_clipboard_settings',
            function () { ClipboardAdmin::display('settings'); });
});


add_action('admin_init', function() {

    //ClipboardAdmin::sendMessage('Hello!!');

    /*$get = filter_input_array(INPUT_GET) ?? array();
    
    if( isset($get['page']) && $get['page'] === 'coders_clipboard' ){
        //tasks and redirect
        if( isset($get['task']) ){
            $redirect = ClipboardAdmin::task($get);
            return ClipboardAdmin::redirect($redirect);
        }
        $id =isset($get['context_id']) ? $get['context_id'] : '';
        
        $view = $get['page'] === 'coders_clipboard_settings' ? 'settings' :'default';
        ClipboardAdmin::display($view, $id);
    }*/
});


add_filter('coders_clipboard_tiers', function($tiers = array()) {
    return $tiers;
}, 10, 2);

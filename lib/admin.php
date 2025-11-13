<?php namespace CODERS\ClipBoard\Admin;

defined('ABSPATH') or die;

add_action('admin_post_clipboard_action', function() {
    if ( ! current_user_can('upload_files') ) {
        wp_die(__('Unauthorized', 'coders_clipboard'));
    }

    $redirect = \CODERS\ClipBoard\Admin\Controller::redirect('post');
    
    wp_redirect(add_query_arg($redirect, admin_url('admin.php')));
    exit;        
});

add_action('wp_ajax_clipboard_action', function() {

    if ( ! current_user_can('upload_files') ) {
        wp_die(__('Unauthorized', 'coders_clipboard'));
    }

    $response = \CODERS\ClipBoard\Admin\Controller::redirect('ajax');
    
    wp_send_json_success($response);
    
    //exit;        
});

add_action('admin_enqueue_scripts', function( $hook ) {

    \CODERS\ClipBoard\Admin\View::attachheaders();
});

add_action('admin_menu', function () {

    add_menu_page(
            __('Clipboard', 'coders_clipboard'),
            __('Clipboard', 'coders_clipboard'),
            'upload_files', // or 'manage_options' if more restricted
            'coders_clipboard',
            function () {
                \CODERS\ClipBoard\Admin\Controller::run();
            }, 'dashicons-art',80);
    add_submenu_page(
            'coders_clipboard',
            __('Settings', 'coders_clipboard'),
            __('Settings', 'coders_clipboard'),
            'manage_options',
            'coders_clipboard_settings',
            function () {
                \CODERS\ClipBoard\Admin\Controller::run('settings'); }
            );
});
/**
 * 
 */
abstract class Content{

    /**
     * @param string $name
     * @return string
     */
    abstract function __get($name);
    /**
     * 
     */
    abstract function __set($name,$value);
    
    
    /**
     * @param string $id
     * @return \CODERS\Clipboard\Clipboard
     */
    public static function clipboard(){
        return new \CODERS\Clipboard\Clipboard();
    }
    /**
     * @param string $id
     * @return url|string
     */
    public static function contextlink($id = '') {
        $query = array(
            'page' => 'coders_clipboard',
        );
        if(strlen($id)){
            $query['context_id'] = $id;
        }
        return add_query_arg($query, admin_url('admin.php'));
    }
    /**
     * @param \CODERS\Clipboard\Clip[] $clips
     * @return array
     */
    public static function clipmeta( $clips = array() ){
        return array_map(function( $clip ){
                $meta = $clip->meta();
                $meta['post'] = Content::contextlink($clip->id);
                return $meta;
            
        }, $clips);
    }
}

/**
 * 
 */
class ClipContent extends Content{
    
    private $_clip = null;
    /**
     * @param string $id
     */
    public function __construct( $id = '' ) {
        
        $this->_clip = self::load($id) ?? null;
    }
    /**
     * @param string $name
     * @return string
     */
    public function __get($name) {
        return !is_null($this->clip()) ? $this->clip()->$name : '';
    }
    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name,$value) {
        if(!is_null($this->clip())){
            $this->clip()->$name = $value;
        }
    }
    
    /**
     * @return \CODERS\Clipboard\Clip
     */
    private function clip() {
        return $this->_clip;
    }
    
    /**
     * @param string $id
     * @return \CODERS\Clipboard\Clip
     */
    public static function load( $id = '' ){
        $content = new ClipContent();
        return $content->clipboard()->clip($id);
    }
}
/**
 * 
 */
class SettingsContent extends Content{

    private $_settings = array(
        //add settings here
    );
    /**
     * @param string $name
     * @return string
     */
    public function __get($name): string {
        return $this->has($name) ? $this->_settings[$name] : '';
    }
    /**
     * 
     * @param string $name
     * @param mixed $value
     */
    public function __set($name,$value) {
        if($this->has($name)){
            $this->_settings[$name] = $value;
        }
    }
    /**
     * @param string $name
     * @return bool
     */
    public function has($name) {
        return array_key_exists($name, $this->_settings);
    }
    /**
     * @return bool
     */
    public function resetContent(){
        $data = self::clipboard()->data()->cleanup();
        if($data){
            return $this->cleardrive();
        }
        return false;
    }
    /**
     * 
     * @return bool
     */
    private function cleardrive(){
            $drive = self::clipboard()->drive();
            if (file_exists($drive)) {
                $files = glob($drive . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                return true;
            }
        return false;
    }
}


/**
 * 
 */
abstract class Controller{

    const INPUT_REQUEST = 3;
    const INPUT_GET = INPUT_GET;
    const INPUT_POST = INPUT_POST;
    /**
     * @var array
     */
    static private $_mailbox = array();

    /**
     * @var array
     */
    private $_response = array();

    
    /**
     * 
     */
    protected function __construct() {
        
    }
    /**
     * @param string $key
     * @param mixed $value
     * @return \CODERS\Clipboard\Admin\Controller
     */
    protected function set($key = '' , $value = false ) {
        if(strlen($key)){
            $this->_response[$key] =  $value;
        }
        return $this;
    }
    /**
     * @param array $input
     * @return \CODERS\Clipboard\Admin\Controller
     */
    protected function fill( array $input = array()) {
        foreach($input as $var => $val ){
            $this->_response[$var] = $val;
        }
        return $this;
    }
    /**
     * @return array
     */
    static public function  mailbox(){
        return Controller::$_mailbox;
    }
    /**
     * @param string $message
     * @param string $type
     */
    static public function notify( $message = '' , $type = ''){
        Controller::$_mailbox[] = array(
            'message' => $message,
            'type' => $type,
        );
    }
    /**
     * @param string $action
     * @return boolean
     */
    protected function error( $action = '' ){
        printf('Error %s',$action);
        return false;
    }
    /**
     * @return array
     */
    public function response() {
        return $this->_response;
    }
    /**
     * @param string $task
     * @return bool
     */
    protected function action( ){
        $input = $this->input();
        $task = array_key_exists('action', $input) ? $input['action'] : 'default';
        $action = sprintf('%sAction',$task);
        return method_exists($this, $action) ? $this->$action( $input ) : $this->error($task);
    }
    /**
     * @param array $input
     * @return bool
     */
    abstract protected function defaultAction( array $input = array() ) : bool;
    
    /**
     * @param int $type
     * @return array
     */
    protected function input( $type = self::INPUT_REQUEST ) {
        switch($type){
            case self::INPUT_REQUEST:
                return array_merge(
                        $this->input(self::INPUT_GET),
                        $this->input(self::INPUT_POST)
                );
            case self::INPUT_GET:
            case self::INPUT_POST:
                return filter_input_array($type) ?? array();
            default:
                return array();
        }
    }
    /**
     * @param string $context
     * @return Controller
     */
    protected static final function create( $context = '' ){
        $class = sprintf('%sController', ucfirst($context));
        return class_exists($class) && is_subclass_of($class, self::class ,true ) ? new $class() : null;
    } 
    /**
     * @param string $context
     * @return array | null
     */
    public static function run( $context = 'main' ){
        $controller = self::create($context);
        return !is_null($controller) ? $controller->action() : null;
    }
    /**
     * @param string $context
     * @return array
     */
    public static function redirect($context = 'main') {
        $controller = self::create($context);
        
        return !is_null($controller) && $controller->action() ?
            $controller->response() :
            array(
                //error response
            );
    }
    
    
    public static final function task($input = array()) {
        $task = array_key_exists('task', $input) ? $input['task'] : '';
        $context_id = array_key_exists('context_id', $input) ? $input['context_id'] : '';
        $id = array_key_exists('id', $input) ? $input['id'] : '';
        $parent_id = array_key_exists('parent_id', $input) ? $input['parent_id'] : '';
        $output = array();
        if (strlen($id)) {
            $output['id'] = $id;
        }
        if (strlen($context_id)) {
            $output['context_id'] = $context_id;
        }

        switch ($task) {
            case 'upload':
                $uploaded = self::upload('upload', $id);
                $output['content'] = $uploaded;
                break;
            case 'update':
                $content = ClipContent::load($id);
                if (!is_null($content) && $content->override($input)->update()) {
                    $output[$task] = 'done';
                }
                break;
            case 'delete':
                $content = ClipContent::load($id);
                if (!is_null($content) && $content->remove()) {
                    $output[$task] = 'done';
                    if ($content->id === $context_id) {
                        $context_id = $content->parent_id;
                    }
                    $output['context_id'] = $context_id;
                }
                break;
            case 'sort':
                $item = ClipContent::load($id);
                $index = isset($input['slot']) ? $input['slot'] : 0;
                if (!is_null($item)) {
                    $count = $item->sort($index);
                    $output['slot'] = $index;
                    $output['count'] = $count;
                    $output[$task] = 'done';
                }
                break;
            case 'move':
                $content = ClipContent::load($id);
                if (!is_null($content) && $content->moveto($parent_id)) {
                    $output[$task] = 'done';
                }
                break;
            case 'moveup':
                $content = ClipContent::load($id);
                //get here the upper parent Id
                if (!is_null($content) && $content->moveup()) {
                    $output[$task] = 'done';
                }
                break;
            case 'moveto':
                $content = ClipContent::load($id);
                //get here the selected container id
                if (!is_null($content) && $content->moveto()) {
                    $output[$task] = 'done';
                }
                break;
            case 'recover':
                $lost = self::fetchLost();
                $count = self::fixParents();
                $output['fixed'] = $count;
                $output['items'] = $lost;
                $output[$task] = 'done';
                break;
            case 'renameall':
                $count = ClipContent::renameAll($context_id);
                if ($count) {
                    $output[$task] = 'done';
                    $output['count'] = $count;
                }
                break;
            case 'propagate':
                $count = ClipContent::copyPermissions($context_id);
                if ($count) {
                    $output[$task] = 'done';
                    $output['count'] = $count;
                }
                break;
            case 'layout':
                $count = ClipContent::copyLayouts($context_id);
                if ($count) {
                    $output[$task] = 'done';
                    $output['count'] = $count;
                }
                break;
            case 'arrange':
                $count = ClipContent::arrange($context_id);
                $output[$task] = 'done';
                $output['count'] = $count;
                break;
            case 'nuke':
                $output['page'] = self::CONTEXT_SETTINGS;
                if (ClipContent::resetContent()) {
                    $output[$task] = 'done';
                }
                break;
        }

        return $output;
    }
}
/**
 * 
 */
class MainController extends Controller{
    /**
     * @param array $input
     * @return bool
     */
    protected function defaultAction(array $input = array()): bool {
        
        $content = new ClipContent($input['id'] ?? '');
        
        View::create('main')
                ->setContent( $content )
                ->view('default');
        
        return true;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function uploadAction( array $input = array()) : bool {
        
        $id = array_key_exists('id', $input)?  $input['id'] : '';
        $upload = array_key_exists('upload', $input)?  $input['upload'] : 'upload';
        $clips = Uploader::create( $upload)->items( $id );
        //$meta = Content::clipmeta($clips);
        $this->notify(sprintf('%s items uploaded!','update'),count($clips));
        return $this->defaultAction( $input );
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function updateAction( array $input = array()) : bool {
        return false;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function deleteAction( array $input = array()) : bool {
        return false;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function sortAction( array $input = array()) : bool {
        return false;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function arrangeAction( array $input = array()) : bool {
        return false;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function moveAction( array $input = array()) : bool {
        return false;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function moveupAction( array $input = array()) : bool {
        return false;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function movetoAction( array $input = array()) : bool {
        return false;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function recoverAction( array $input = array()) : bool {
        return false;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function remameAction( array $input = array()) : bool {
        return false;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function propagateAction( array $input = array()) : bool {
        return false;
    }
}
/**
 * 
 */
class ClipController extends Controller{
    /**
     * @param array $input
     * @return bool
     */
    protected function defaultAction(array $input = array()): bool {
       
        View::create('clip')->view('default');
        return true;
    }
}
/**
 * 
 */
class SettingsController extends Controller{
    /**
     * @param array $input
     * @return bool
     */
    protected function defaultAction(array $input = array()): bool {
       
        View::create('settings')->view();
        
        return true;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function nukeAction( ) : bool {
        $content = new ClipContent();
        if( $content->resetContent()){
            $this->notify(__('Clipboard data clear','coders_clipboard'));
        }
        else{
            $this->notify(__('Unable to clear Clipboard data','coders_clipboard'),'warning');            
        }
        return $this->defaultAction();
    }
}

/**
 * 
 */
class PostController extends Controller{
    
    function __construct() {
        parent::__construct();
        $this->set('page', 'coders_clipboard');
    }
    /**
     * @return bool
     */
    protected function action() {
        $input = $this->input(self::INPUT_POST);
        $task = array_key_exists('task', 'default');
        $action = sprintf('%sAction', ucfirst($task));
        return method_exists($this, $action) ? $this->$action($input) : false;
    }


    protected function defaultAction(array $input = []): bool {

        return false;;        
    }
}
/**
 * 
 */
class AjaxController extends Controller{
    
    function __construct() {
        parent::__construct();
    }
    /**
     * @return bool
     */
    protected function action() {
        $input = $this->input(self::INPUT_POST);
        $task = array_key_exists('task', 'default');
        $action = sprintf('%sAction', ucfirst($task));
        return method_exists($this, $action) ? $this->$action($input) : false;
    }

    /**
     * @param array $input
     * @return bool
     */
    protected function defaultAction(array $input = []): bool {

        return true;
    }
}





/**
 * 
 */
class View{
    /**
     * @var \CODERS\Clipboard\Admin\Content
     */
    private $_content = null;
    /**
     * @var string
     */
    private $_context = '';
    
    /**
     * @param string $context
     */
    function __construct( $context = '' ) {
        $this->_context = $context;
    }
    /**
     * @param string $context
     * @return \View
     */
    static public function create($context = 'default') {
        return new View($context);
    }
    /**
     * @param \CODERS\Clipboard\Admin\Content $content
     * @return \View Description
     */
    public function setContent( Content $content = null ){
        $this->_content = $content;
        return $this;
    }
    /**
     * @return \CODERS\Clipboard\Admin\Content
     */
    protected function content(){
        return $this->_content;
    }
    
    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments) {
        $args = $arguments ?? array();
        switch(true){
            case preg_match('/^list_/', $name):
                return $this->__list(substr($name, 5));
            case preg_match('/^is_/', $name):
                return $this->__is(substr($name, 3));
            case preg_match('/^has_/', $name):
                return $this->__has(substr($name, 4));
            case preg_match('/^show_/', $name):
                return $this->__show(substr($name, 5));
            case preg_match('/^action_/', $name):
                return $this->action(substr($name, 7) , ...$args );
            case preg_match('/^link_/', $name):
                return $this->link( substr($name, 5), ...$args );
            case preg_match('/^url_/', $name):
                return $this->url( explode( '_', substr($name, 4)), ...$args );
        }
        
        return !is_null($this->content()) ? $this->content()->$name : '';
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        return $this->$name();
    }
    /**
     * @param string $show
     * @return bool
     */
    protected function __show($show = ''){
        return strlen($show) ? $this->view(sprintf('parts/%s.php',$show)) : false;
    }
    /**
     * @param string $list
     * @return array
     */
    protected function __list($list = ''){
        $call = sprintf('list%s', ucfirst($list));
        return method_exists($this, $call) ? $this->$call() : array();
    }
    /**
     * @param string $has
     * @return bool
     */
    protected function __has($has = '') {
        $call = sprintf('has%s', ucfirst($has));
        return method_exists($this, $call) ? $this->$call() : false;
    }
    /*
     * @param string $has
     * @return bool
     */
    protected function __is( $is = '' ){
        $call = sprintf('is%s', ucfirst($is));
        return method_exists($this, $call) ? $this->$call() : false;
    }
    
    
    
    
    /**
     * 
     * @param string $link
     * @param array $args
     * @return string|url
     */
    protected function link($link = '', array $args = array()) {
        $call = sprintf('link%s', ucfirst($link));
        return method_exists($this, $call) ? $this->$call($args) : $this->url($link,$args);
    }
    /**
     * @param array $path
     * @param array $args
     * @return string
     */
    private function url( $path = array() , array $args = array() ){
        $base_url = site_url( count($path) ? implode('/', $path) : '' );

        $get = array();

        foreach( $args as $var => $val ){
            $get[] = sprintf('%s=%s',$var,$val);
        }

        if( count( $get )){
            $base_url .=  '?' . implode('&', $get);
        }

        return $base_url;
    }
    /**
     * @param array $args
     * @return string|url
     */
    private function adminurl( array $args = array()){
        //$admin_url = menu_page_url('coder-sandbox');
        $admin_url = admin_url('admin.php?page=coders-clipboard');
        $get = array();
        foreach ($args as $var => $val ){
            $get[] = $var . '=' . $val;
        }
        return count($args) ? $admin_url . '&' . implode('&', $get) : $admin_url;
    }
    /**
     * @param string $action
     * @param array $args
     * @return string
     */
    protected function action( $action = '' , array $args = array()){
        $call = sprintf('action%s', ucfirst($action));
        if(method_exists($this, $call)){
            return $this->$call($args);
        }
        if(strlen($action)){
            $args['action'] = $action;
        }
        return $this->adminurl($args);
    }
    /**
     * @param string $name
     * @return string
     */
    protected function path($name = ''){
        return sprintf('%s/html/admin/%s',CODERS_CLIPBOARD_DIR,$name);
    }
    /**
     * @param string $view
     * @return bool
     */
    public function view($view = ''){
        $this->viewMessages(Controller::mailbox() );
        $path = $this->path(sprintf('%s.php', strlen($view) ? $view : $this->_context));
        if(file_exists($path)){
            require $path;
        }
        else{
            require $this->path('error.php');
        }
        return $this;
    }
    /**
     * @param array $messages
     * @return \View
     */
    protected function viewMessages( array $messages = array() ){
        foreach( $messages as $message ){
            printf('<div class="notice is-dismissible %s">%s</div>',$message['type'],$message['content']);
        }
        return $this;
    }    
    /**
     * @return bool
     */
    protected function hasContent(){
        return !is_null( $this->content());
    }


    /**
     * 
     */
    public static function attachheaders(){

        $style_mime = sprintf('%s/html/admin/style.css', CODER_CLIPBOARD_DIR);
        $style = sprintf('%s/html/admin/style.css', CODER_CLIPBOARD_URL);
        $script_mime = sprintf('%s/html/admin/script.js', CODER_CLIPBOARD_DIR);
        $script = sprintf('%s/html/admin/script.js', CODER_CLIPBOARD_URL);
        // Register and enqueue CSS
        wp_enqueue_style('clipboard-admin-style',$style,[],filemtime($style_mime));

        // Register and enqueue JS
        wp_enqueue_script('clipboard-admin-script', $script,['jquery'],filemtime($script_mime),true);

        // Optional: Pass variables to JS
        wp_localize_script('clipboard-admin-script', 'ClipboardData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clipboard_nonce')
        ]);
        
    }
}




/**
 * Upload Manager for new contents
 */
class Uploader {
    /**
     * @var array
     */
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
     * @param string $id
     * @return \CODERS\Clipboard\Clip[]
     */
    public function items( $id = '' ) {
        $clipboard = Content::clipboard();
        $clip = $clipboard->clip($id);
        $slot = !is_null($clip) ? $clip->count() : 0;
        $layout = !is_null($clip) ? $clip->layout : '';
        $acl = !is_null($clip) ? $clip->acl : '';

        $clips = array();
        foreach($this->files() as $file ){
            //set the first parent id to the parsed ID
            $file['parent_id'] = $id;
            $file['slot'] = ++$slot;
            $file['acl'] = $acl;
            $file['layout'] = $layout;

            //then set the next to the first file ID
            if (strlen($id) === 0) {
                $id = $file['id'];
            }
            $clips[$file['id']] = $clipboard->create($file);
        }
        return $clips;
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
            ClipboardAdmin::sendMessage($ex->getMessage(), 'error');
        }
        return false;
    }

    /**
     * @return \Uploader
     */
    public static final function create($from = 'upload') {

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
                    $files[] = $upload;
                }
                else {
                    Controller::notify(
                            __('Failed to move uploaded file', 'coders_clipboard') . ' ' . $upload['name'],
                            'error');
                }
            }
        }
        return new Uploader($files);
    }
    /**
     * @param string $from input name
     * @param string $id parent clipboard id
     * @return \CODERS\Clipboard\Clip[]
     */
    public static function upload($from = 'upload', $id = '') {
        $uploaded = array();
        $clipboard = Content::clipboard();
        $container = $clipboard->clip($id);
        $slot = !is_null($container) ? $container->count() : 0;
        $layout = !is_null($container) ? $container->layout : '';
        $acl = !is_null($container) ? $container->acl : '';
        //attach to parent id, ir leave blank to set it to first clip
        foreach (self::create($from)->files() as $file) {
            //set the first parent id to the parsed ID
            $file['parent_id'] = $id;
            $file['slot'] = ++$slot;
            $file['acl'] = $acl;
            $file['layout'] = $layout;

            $clip = $clipboard->create($file);
            if(!is_null($clip)){
                $uploaded[] = $clip->meta();
                $uploaded['post'] = Content::contextlink($clip->id);
            }
            //then set the next to the first file ID
            if (strlen($id) === 0) {
                $id = $file['id'];
            }
        }

        return $uploaded;
    }
}



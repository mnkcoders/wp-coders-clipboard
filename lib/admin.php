<?php namespace CODERS\Clipboard\Admin;

defined('ABSPATH') or die;

add_action('admin_post_clipboard_action', function() {

    //$response = array(
    //    'response'=>'test1',
    //    'message'=>'Testing post respose');
    
    $response = \CODERS\Clipboard\Admin\Controller::redirect('post');
    
    wp_redirect(add_query_arg($response, admin_url('admin.php')));
    exit;        
});

add_action('wp_ajax_clipboard', function() {
    //$response = array('response'=>'ajax test response ;)',);
    
    $response = \CODERS\Clipboard\Admin\Controller::redirect('ajax');
    
    wp_send_json_success($response);
    exit;
});

add_action('admin_enqueue_scripts', function( $hook ) {

    \CODERS\Clipboard\Admin\View::attachheaders();
});

add_action('admin_menu', function () {

    add_menu_page(
            __('Clipboard', 'coder_clipboard'),
            __('Clipboard', 'coder_clipboard'),
            'upload_files', // or 'manage_options' if more restricted
            'coder_clipboard',
            function () {
                $context = filter_input(INPUT_GET, 'controller') ?? 'main';
                \CODERS\Clipboard\Admin\Controller::run($context);
            }, 'dashicons-art',40);
    add_submenu_page(
            'coder_clipboard',
            __('Settings', 'coder_clipboard'),
            __('Settings', 'coder_clipboard'),
            'manage_options',
            'coder_clipboard_settings',
            function () {
                \CODERS\Clipboard\Admin\Controller::run('settings'); }
            );
});

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
    static private $_log = array();
    /**
     * @var bool
     */
    private $_completed = false;
    /**
     * @var array
     */
    private $_response = array(
        //fill in all required meta
    );
    /**
     * @var array
     */
    private $_input = array( );
    
    /**
     * @param array $input
     */
    protected function __construct( array  $input = array()) {
        $this->_input = $input;
    }
    /**
     * @param string $name
     * @return string
     */
    public function __get($name) {
        return $this->_input[$name] ?? '';
    }
    /**
     * @param string $name
     * @return int
     */
    protected function getInt($name) {
        return array_key_exists($name, $this->data()) ? (int)$this->data()[$name] : 0;
    }
    /**
     * @param string $name
     * @return float
     */
    protected function getFloat($name) {
        return array_key_exists($name, $this->data()) ? (float)$this->data()[$name] : 0.0;
    }
    /**
     * @param string $name
     * @param string $split
     * @return array
     */
    protected function getList($name,$split = ' ') {
        $list = $this->data()[$name] ?? array();
        return is_array($list) ? $list :
                array_filter(explode($split, $list),function($item){
                    return strlen($item);
                }); 
    }
    /**
     * @return bool
     */
    private function completed(){
        return $this->_completed;
    }
    /**
     * @return array
     */
    protected function data(){
        return $this->_input;
    }
    /**
     * @return bool
     */
    protected function canupload(){
        return  current_user_can('upload_files');
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
    static public function log(){
        return Controller::$_log;
    }
    /**
     * @param string $message
     * @param string $type
     */
    static public function notify( $message = '' , $type = 'update'){
        Controller::$_log[] = array(
            'message' => $message,
            'type' => $type,
        );
    }
    /**
     * @return string
     */
    protected function action(){
        return $this->_input['action'] ?? 'default';
    }
    /**
     * @param array $input
     * @return bool
     */
    private function task( array $input = array() ){
        //return $this->data()['action'] ?? 'default';
        $action = $input['action'] ?? 'default';
        $call = sprintf('%sAction',$action);
        return method_exists($this, $call) ? $this->$call( $input ) : $this->error($action);
    }
    /**
     * @return \CODERS\Clipboard\Admin\Controller
     */
    public function request(){
        //$action = $this->action();
        $action = $this->_input['action'] ?? 'default';
        $call = sprintf('%sAction',$action);
        
        $this->_completed = method_exists($this, $call) ?
                $this->$call( ) :
                $this->error($action);

        return $this;
    }
    /**
     * action[] , log[] , ...
     * @return array
     */
    public function response() {
        $this->_response[$this->action()] = $this->completed();
        $this->_response['message'] = $this->log();
        return $this->_response;
    }
    /**
     * @param string $action
     * @return bool
     */
    protected function error( $action = '' ){
        $this->notify(sprintf('Invalid action [%s]',$action),'error');
        $this->set('error', $this->log() );
        return false;
    }
    /**
     * @return bool
     */
    protected function defaultAction( ) : bool{
        //
        return true;
    }
    
    
    /**
     * @param int $type
     * @return array
     */
    protected static function input( $type = self::INPUT_REQUEST ) {
        switch($type){
            case self::INPUT_REQUEST:
                return array_merge(
                        self::input(self::INPUT_GET),
                        self::input(self::INPUT_POST)
                );
            case self::INPUT_GET:
            case self::INPUT_POST:
                return filter_input_array($type) ?? array();
            default:
                return array();
        }
    }
    /**
     * @return array
     */
    protected static function fromajax( ){
        $input = self::input(self::INPUT_POST);
        $input['action'] = $input['task'] ?? 'default';
        unset($input['task']);
        return $input;
    }
    /**
     * @param string $context
     * @param array $input
     * @return \CODERS\Clipboard\Admin\Controller
     */
    protected static final function create( $context = '' , array $input = array()){
        $class = sprintf('\CODERS\Clipboard\Admin\%sController', ucfirst($context));
        return class_exists($class) && is_subclass_of($class, self::class ,true ) ? new $class($input) : null;
    } 
    /**
     * @param string $context
     * @return bool
     */
    public static function run( $context = 'main' ){
        $controller = self::create($context,self::input());
        //$controller = self::create($context);
        return $controller ? $controller->request()->completed() : false;
            //$controller->task(self::input()) :
            //false;
    }
    /**
     * @param string $context
     * @return array
     */
    public static function redirect($context = 'main') {
        $controller = self::create($context,self::fromajax());
        return !is_null($controller) ?
            $controller->request()->response() :
            array(
                //error response
                'response' => 'error',
                'message' => sprintf('Context Error [%s]',$context),
            );
    }
}
/**
 * 
 */
class MainController extends Controller{

    /**
     * @return bool
     */
    protected function defaultAction(): bool {
        
        $content = Content::load( $this->id ,true);
        
        View::create('main')
                ->setContent( $content )
                ->view('default');

        return true;
    }
    /**
     * @return bool
     */
    protected function uploadAction( ) : bool {
        if($this->canupload()){
            $id = $this->id;
            $clips = Uploader::create( 'upload' )->items( $id );
            $this->notify(sprintf('%s items uploaded!','update'),count($clips));
        }
        else{
            $this->notify(__('Not allowed to upload','coder_clipboard'), 'error');
        }
        return $this->defaultAction( $input );
    }
    /**
     * @return bool
     */
    protected function updateAction( ) : bool {
        $id = $this->id;
        $clip = Content::load($id);
        if ( !is_null($clip) && $clip->update($this->data())) {
            $this->notify(sprintf('%s updated!',$clip->name), 'update');
        }
        else{
            $this->notify("Can't update", 'error');
        }

        return $this->defaultAction();
    }
    /**
     * @return bool
     */
    protected function deleteAction( ) : bool {
        $content = Content::load($this->id);
        if ( $content && $content->remove()) {
            $this->notify(sprintf('%s removed!',$content->name),'update');
        }
        else{
            $this->notify('Unable to remove item','error');
        }
        return $this->defaultAction();
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function sortAction( array $input = array()) : bool {

        $item = Content::load($this->id);
        $index = $this->getInt('slot');
        if (!is_null($item)) {
            $count = $item->sort($index);
            $this->notify(sprintf('%s items udpated',$count), 'update');
        }
        return $this->defaultAction();
    }
    /**
     * @return bool
     */
    protected function arrangeAction() : bool {
        $id = $this->id;
        if($id ){
            $count = Content::manager()->db()->arrange($id);
            $this->notify(sprintf('%s items udpated',$count), 'update');
        }
        return $this->defaultAction();
    }
    /**
     * @return bool
     */
    protected function moveAction( ) : bool {
        $clip = Content::load($this->id);
        if( $clip && $clip->moveto($this->parent_id) ){
            $this->notify('Moved!','update');
        }
        return $this->defaultAction();
    }
    /**
     * @return bool
     */
    protected function moveupAction( ): bool {
        $clip = Content::load( $this->id );
        if (!is_null($clip) && $clip->moveup()) {
            $this->notify('Moved!','update');
        }
        return $this->defaultAction();
    }

    /**
     * @return bool
     */
    protected function movetoAction( ) : bool {
        return $this->moveAction();
    }
    /**
     * @return bool
     */
    protected function recoverAction() : bool {
        $lostfiles = Content::findLost();
        $orphen = Content::restoreLost();
        $this->notify('Recovered %s lost items and %s unparented items',$lostfiles,$orphen);
        return $this->defaultAction();
    }
    /**
     * @return bool
     */
    protected function copynamesAction( ) : bool {
        $clip = Content::load( $this->id );
        if($clip){
            $count = $clip->copynames();
            $this->notify(sprintf('%s items updated',$count));
        }
        else{
            $this->notify('Invalid item','error');
        }
        return $this->defaultAction();
    }
    /**
     * @return bool
     */
    protected function propagateAction( ) : bool {
        $clip = Content::load( $this->id );
        if($clip){
            $count = $clip->copyroles();
            $this->notify(sprintf('%s items updated!',$count),'update');
        }
        else{
            $this->notify('Invalid clip','error');
        }
        return $this->defaultAction();
    }
    /**
     * @return bool
     */
    protected function layoutAction( )  : bool{
        $clip = Content::load( $this->id );
        if($clip){
            $count = $clip->copylayouts();
            $this->notify(sprintf('%s items updated!',$count),'update');
        }
        else{
            $this->notify('Invalid clip','error');
        }
        return $this->defaultAction();
    }
}
/**
 * 
 */
class SettingsController extends Controller{
    /**
     * @return bool
     */
    protected function defaultAction( ): bool {
       
        View::create('settings')->view();
        
        return true;
    }
    /**
     * @return bool
     */
    protected function nukeAction( ) : bool {
        $settings = new Settings();
        $data = $settings->cleardata();
        $files = $settings->cleardrive();
        if( $data){
            $this->notify(__('Clipboard data clear','coder_clipboard'));
        }
        else{
            $this->notify(__('Unable to clear Clipboard data','coder_clipboard'),'warning');            
        }
        if( $files){
            $this->notify(sprintf('%s %s',$files,__('files removed','coder_clipboard')),'update');
        }
        else{
            $this->notify(__('Unable to clear Clipboard drive','coder_clipboard'),'warning');            
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
        $this->set('page', 'coder_clipboard');
    }
    /**
     * @return bool
     */
    protected function defaultAction(): bool {
        $this->set('response','ok');
        return false; 
    }
    /**
     * @param array $input
     * @return boolean
     */
    protected function saveAction( ){
        $data = $this->data();
        return false;
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
    protected function testAction( ) {
        var_dump($this->data());
        $task = sprintf('%sAction',$this->action());
        if(method_exists($this, $task)){
            var_dump( $this->$task());
        }
        var_dump($this->response());
        return true;
    }


    /**
     * @return bool
     */
    protected function defaultAction(): bool {
        $this->set('response','ok');
        return true;
    }
    /**
     * @return boolean
     */
    protected function uploadAction( ) : bool{
        if($this->canupload()){
            $id = $this->id;
            $clips = Uploader::create( 'upload' )->items( $id );
            $this->notify(sprintf('%s items uploaded!',$clips));
            $response = array(
                'count' => count($clips),
                'files' => Content::clipmeta($clips),
                );
            $this->fill($response);
            return true;
        }
        else{
            $this->notify('Cannot upload files', 'error');
        }
        return false;
    }
    /**
     * @return boolean
     */
    protected function removeAction( ) : bool{
        
        $clip = Content::load($this->id);
        
        if($clip && $clip->remove()){
                $this->notify(sprintf('%s removed!',$clip->name),'update');
                return true;
        }
        else{
            $this->notify('Invalid file','warning');
        }
        
        return false;
    }
    /**
     * @return boolean
     */
    protected function moveAction( ) : bool{
        $clip = Content::load($this->id);

        if($clip && $clip->moveto($this->parent_id)){
                $this->notify(sprintf('%s moved!',$clip),'update');
                return true;
        }
        else{
            $this->notify('Cannot move','warning');
        }
        
        return false;
    }
    /**
     * @return boolean
     */
    protected function moveupAction( ) : bool{
        $clip = Content::load($this->id);
        if($clip && $clip->moveup()){
            $this->notify('Moved!','update');
            return true;
        }
        else{
            $this->notify('Invalid item','error');
        }
        return false;
    }
    /**
     * @return boolean
     */
    protected function setaclAction( ) : bool {
        $clip = Content::load($this->id);
        if( $clip){
            $count = $clip->copyroles();
            $this->notify(sprintf('%s items updated',$count));
            return true;
        }
        else{
            $this->notify('Invalid item','error');
        }
        return false;
    }
    /**
     * @return boolean
     */
    protected function setlayoutAction( ) :bool{
        $clip = Content::load($this->id);
        if( $clip){
            $count = $clip->copylayouts();
            $this->notify(sprintf('%s items updated',$count));
            return true;
        }
        else{
            $this->notify('Invalid item','error');
        }
        return false;
    }
}


/**
 * 
 */
class Content extends \CODERS\Clipboard\Clip{

    /**
     * @param \CODERS\Clipboard\Clip[] $clips
     * @return array
     */
    public static function clipmeta( $clips = array() ){
        return array_map(function( $clip ){
                $meta = $clip->meta();
                $meta['post'] = View::adminurl(array('id'=>$clip->id));
                return $meta;
            
        }, $clips);
    } 
    /**
     * @return \CODERS\Clipboard\Clipboard
     */
    public static function manager(){
        return \CODERS\Clipboard\Clipboard::instance();
    }
}
/**
 * 
 */
class Settings{
    /**
     * @var array
     */
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
     * @return array
     */
    public function listSettings(){
        return $this->_settings;
    }


    /**
     * @return bool
     */
    public function cleardata(){
        return Content::manager()->db()->cleanup();
    }
    
    
    /**
     * @return int
     */
    public function cleardrive(){
        return Content::manager()->storage()->clear();
    }
}




/**
 * 
 */
class View{
    /**
     * @var object
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
     * @return \CODERS\Clipboard\Admin\View
     */
    static public function create($context = 'default') {
        $view = sprintf('\CODERS\Clipboard\Admin\%sView', ucfirst($context));
        return is_subclass_of($view, self::class) ? new $view($context) : new View($context);
    }
    /**
     * @param object $content
     * @return \View Description
     */
    public function setContent( $content = null ){
        if(is_object($content)){
            $this->_content = $content;
        }
        return $this;
    }
    /**
     * @return object
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
            case preg_match('/^get_/', $name):
                $get = sprintf('get%s', ucfirst(substr($name, 4)));
                return method_exists($this, $get) ? $this->$get(...$args) : '';
            case preg_match('/^count_/', $name):
                $count = sprintf('count%s', ucfirst(substr($name, 6)));
                return method_exists($this, $count) ? $this->$count(...$args) : 0;
            case preg_match('/^list_/', $name):
                return $this->__list(substr($name, 5));
            case preg_match('/^is_/', $name):
                return $this->__is(substr($name, 3));
            case preg_match('/^has_/', $name):
                return $this->__has(substr($name, 4));
            case preg_match('/^action_/', $name):
                return $this->__action(substr($name, 7),$args);
            case preg_match('/^show_/', $name):
                return $this->template(substr($name, 5));
            case preg_match('/^editor_/', $name):
                return $this->__editor( substr($name, 7), ...$args );
            case preg_match('/^link_/', $name):
                return $this->link( substr($name, 5) );
            case preg_match('/^url_/', $name):
                return $this->url( explode( '_', substr($name, 4)), ...$args );
        }
        
        return '';
        //return !is_null($this->content()) ? $this->content()->$name : '';
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        $get = sprintf('get%s', ucfirst($name));
        if( $this->hasContent() && method_exists($this->content(), $get)){
            return $this->content()->$get();
        }
        if( method_exists($this, $get)){
            return $this->$get();
        }
        return !is_null($this->content()) ? $this->content()->$name : '';
    }
    /**
     * @param string $name
     */
    protected function __editor($name) {
        $content = $this->$name;
        $id = 'id_' . $name;
        $settings = [
            'textarea_name' => $name, // The name attribute
            'editor_height' => 250,
            'media_buttons' => true,
            'tinymce' => true,
            'quicktags' => true
        ];

        wp_editor($content, $id, $settings);
    }    
    /**
     * @param string $action
     * @param array $args
     * @return string
     */
    protected function __action($action , array $args = array() ){
        $call = sprintf('action%s', ucfirst($action));
        return method_exists($this, $call) ?
            $this->$call(...$args) :
            self::adminurl(array('action'=>$action));
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
        if( $this->hasContent() && method_exists($this->content(), $call) ){
            return $this->content()->$call();
        }
        return method_exists($this, $call) ? $this->$call() : array();
    }
    /**
     * @param string $has
     * @return bool
     */
    protected function __has($has = '') {
        $call = sprintf('has%s', ucfirst($has));
        if( $this->hasContent()  && method_exists($this->content(), $call) ){
            return $this->content()->$call();
        }
        return method_exists($this, $call) ? $this->$call() : false;
    }
    /*
     * @param string $has
     * @return bool
     */
    protected function __is( $is = '' ){
        $call = sprintf('is%s', ucfirst($is));
        if( $this->hasContent()  && method_exists($this->content(), $call) ){
            return $this->content()->$call();
        }
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
    protected function url( $path = array() , array $args = array() ){
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
    /*protected function adminurl( array $args = array()){
        //$admin_url = menu_page_url('coder-sandbox');
        return View::adminurl($args);
        
        $admin_url = admin_url('admin.php?page=coders-clipboard');
        $get = array();
        foreach ($args as $var => $val ){
            $get[] = $var . '=' . $val;
        }
        return count($args) ? $admin_url . '&' . implode('&', $get) : $admin_url;
    }*/
    /**
     * @param string $action
     * @param array $args
     * @return string
     */
    protected function action( $action = '' , array $args = array()){
        $call = sprintf('action%s', ucfirst($action));
        if(method_exists($this, $call)){
            return $this->$call(...$args);
        }
        if(strlen($action)){
            $args['action'] = $action;
        }
        return View::adminurl($args);
    }
    /**
     * @param string $name
     * @return string
     */
    protected function path($name = ''){
        return sprintf('%s/html/admin/%s',CODER_CLIPBOARD_DIR,$name);
    }
    /**
     * @param string $view
     * @return bool
     */
    public function view($view = ''){
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
     * @param string $view
     * @return bool
     */
    protected function template( $view = '' ){
        printf('<!-- TEMPLATE [%s] -->',$view);
        return strlen($view) && $this->view(sprintf('templates/%s',$view));
    }    
    
    /**
     * @return array
     */
    protected function listMessages( ){
        return Controller::log();
    }    
    /**
     * @return bool
     */
    protected function hasContent(){
        return !is_null( $this->content());
    }
    
    /**
     * @param array $args
     * @return url|string
     */
    public static function adminurl( array $args = array() ) {
        $query = array( 'page' => 'coder_clipboard');
        foreach($args as $key => $val ){
            $query[$key] = $val;
        }
        return add_query_arg($query, admin_url('admin.php'));
    }
    /**
     * 
     */
    public static function attachheaders(){
        
        $style = sprintf('%shtml/admin/content/style.css', CODER_CLIPBOARD_URL);
        $style_path = sprintf('%shtml/admin/content/style.css', CODER_CLIPBOARD_DIR);
        $script = sprintf('%shtml/admin/content/script.js', CODER_CLIPBOARD_URL);
        $script_path = sprintf('%shtml/admin/content/script.js', CODER_CLIPBOARD_DIR);
        // Register and enqueue CSS
        wp_enqueue_style('clipboard-admin-style',$style,[],filemtime($style_path));

        // Register and enqueue JS
        wp_enqueue_script('clipboard-admin-script', $script,['jquery'],filemtime($script_path),true);

        // Optional: Pass variables to JS
        wp_localize_script('clipboard-admin-script', 'ClipboardData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clipboard_nonce')
        ]);
        
    }
}

/**
 * 
 */
class MainView extends View{
    /**
     * @var string
     */
    private $_mode = 'manual';
    
    /**
     * @return \CODERS\Clipboard\Clip[]
     */
    public function listItems(){
        return Content::list();
    }
    /**
     * @return array
     */
    public function listDrives() {
        $storage = array();
        $drives = Content::manager()->storage()->drives();
        foreach($drives as $drive ){
            $storage[$drive] = $drive === $this->getDrive();
        }
        return $storage;
    }
    /**
     * @return array
     */
    public function listTiers(){
        //$tiers = apply_filters('coder_tiers', array());
        $tiers = Content::manager()->acl()->list();
        return is_array($tiers) ? $tiers : array();
    }
    /**
     * @return array
     */
    public function listRoles(){
        $roles = array(
            'private' => __('Private (admin only)', 'coder_clipboard'),
            'public' => __('Public (everyone)', 'coder_clipboard'),
        );
        
        foreach( $this->listTiers() as $tier => $title ){
            $roles[$tier] = sprintf('%s Tier',$title);
        }
        return $roles;
    }
    /**
     * @return array
     */
    public function listLayouts(){
        return array(
            'default' => __('Default', 'coder_clipboard'),
            'ecomic' => __('e-Comic', 'coder_clipboard'),
            'collection' => __('Collection', 'coder_clipboard'),
            'gallery' => __('Gallery', 'coder_clipboard'),
            'slideshow' => __('Slideshow', 'coder_clipboard'),
            'portfolio' => __('Portfolio', 'coder_clipboard'),
            'mosaic' => __('Mosaic', 'coder_clipboard'),
            'showcase' => __('Showcase', 'coder_clipboard'),
        );
    }
    /**
     * @return bool
     */
    public function isEmpty(){
        return !$this->hasContent() && count(Content::list()) === 0;
    }
    /**
     * @return bool
     */
    public function isAjaxmode(){
        return $this->getMode() === 'ajax';
    }
    /**
     * @return bool
     */
    public function hasItems(){
        return $this->countItems() > 0;
    }
    /**
     * @return int
     */
    public function countItems(){
        return $this->hasContent() ? count(Content::list($this->id)) : 0;
    }
    /**
     * @return string
     */
    public function getMode(){
        return $this->_mode;
    }
    /**
     * @return string
     */
    public function getDrive() {
        return 'content';
    }
    /**
     * @param string $id
     * @return string
     */
    public function getBase(){
        return View::adminurl();
    }
    /**
     * @param string $id
     * @return string
     */
    public function getPost( $id = '' ){
        if(strlen($id) === 0){
            $id = $this->id;
        }
        return View::adminurl(array('id'=>$id));
    }
    /**
     * @return string
     */
    protected function getForm( $id = '' ){
        if(strlen($id) === 0){
            $id = $this->id;
        }
        return View::adminurl(array('id'=>$id));
    }    
    /**
     * @param string $id
     * @return string
     */
    public function getUrl( $id = '' ){
        if(strlen($id) === 0){
            $id = $this->id;
        }
        return Content::manager()->clipdata($id);
    }
    /**
     * @param string $id
     * @return string
     */
    public function actionMoveup( $id = ''){
        if(strlen($id) === 0){
            $id = $this->id;
        }
        return View::adminurl(array('id'=>$id,'action'=>'moveup'));
    }
    /**
     * @param string $id
     * @return string
     */
    public function actionMove( $id = '' , $parent_id = ''){
        if(strlen($id) === 0){
            $id = $this->id;
        }
        return View::adminurl(array('action'=>'move','id'=>$id,'parent_id'=>$parent_id));
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
     * @param string $drive
     * @return \CODERS\Clipboard\Storage
     */
    public static function storage($drive = 'content') {
        return Content::manager()->storage($drive);
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
        $container = Content::load($id);
        $slot = !is_null($container) ? $container->count() : 0;
        $layout = !is_null($container) ? $container->layout : '';
        $acl = !is_null($container) ? $container->acl : '';

        $list = array();
        foreach($this->files() as $file ){
            //set the first parent id to the parsed ID
            $file['parent_id'] = $id;
            $file['slot'] = ++$slot;
            $file['acl'] = $acl;
            $file['layout'] = $layout;
            //4cf3baa3c78c32df
            //then set the next to the first file ID
            if (strlen($id) === 0) {
                $id = $file['id'];
            }
            $clip = Content::create($file);
            var_dump($clip);
            $list[$clip->id] = $clip;
        }
        return $list;
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
     * @param string $from
     * @param string $to
     * @return bool
     */
    private static function save( $from ='' , $to = ''){
        return move_uploaded_file($from, $to);
    }
    /**
     * @return \Uploader
     */
    public static final function create($from = 'upload') {

        $drive = self::storage('content');
        $input = self::import($from);
        $files = array();

        foreach ($input as $upload) {
            if (self::validate($upload['error'])) {
                $upload['id'] = $drive->makeid($upload['name']);
                $upload['path'] = $drive->route($upload['id']);
                // Move uploaded file
                if (self::save($upload['tmp_name'], $upload['path'])) {
                    $upload['size'] = filesize($upload['path']);
                    unset($upload['tmp_name']);
                    $files[] = $upload;
                }
                else {
                    Controller::notify(
                            __('Failed to move uploaded file', 'coder_clipboard') . ' ' . $upload['name'],
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
        $clipboard = Content::manager();
        $container = $clipboard->load($id);
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
                $uploaded['post'] = View::adminurl($clip->id);
            }
            //then set the next to the first file ID
            if (strlen($id) === 0) {
                $id = $file['id'];
            }
        }

        return $uploaded;
    }
}


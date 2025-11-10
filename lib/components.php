<?php namespace CODERS\ClipBoard\lib;

defined('ABSPATH') or die;


/**
 * 
 */
interface ContentProvider{
    public function content(): array;
    public function get( $name = '' ) : string;
    public function list( $name = '' ) : array;
    public function is( $name = '' ) : bool;
    public function has( $name = '' ) : bool;
}
/**
 * 
 */
abstract class Controller{
    /**
     * @var array
     */
    static private $_mailbox = array();
    
    /**
     * 
     */
    protected function __construct() {
        
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
     * @param string $action
     * @return bool
     */
    public function action( $action = '' ){
        $call = sprintf('%sAction',$action);
        return method_exists($this, $call) ? $this->$call( self::input()) : $this->error($action);
    }
    /**
     * @param array $input
     * @return bool
     */
    abstract protected function defaultAction( array $input = array() ) : bool;
    /**
     * @param string $context
     * @return Controller
     */
    public static final function create( $context = '' ){
        
        $class = sprintf('%sController', ucfirst($context));
        
        return class_exists($class) && is_subclass_of($class, self::class ,true ) ? new $class() : null;
    } 
}






/**
 * 
 */
class View{
    /**
     * @var ContentProvider
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
     * @param ContentProvider $content
     * @return \View Description
     */
    public function setContent(ContentProvider $content = null ){
        $this->_content = $content;
        return $this;
    }
    /**
     * @return ContentProvider
     */
    protected function content(){
        return $this->_content;
    }
    
    /**
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call(string $name, array $arguments) {
        $args = is_array($arguments) ? $arguments : array();
        switch(true){
            case preg_match('/^get_/', $name):
                return $this->get(substr($name, 5));
            case preg_match('/^list_/', $name):
                return $this->__list(substr($name, 5));
            case preg_match('/^is_/', $name):
                return $this->__is(substr($name, 3));
            case preg_match('/^has_/', $name):
                return $this->__has(substr($name, 4));
            case preg_match('/^show_/', $name):
                return $this->__show(substr($name, 5));
            case preg_match('/^action_/', $name):
                return $this->action(
                        substr($name, 7),
                        isset($args[0]) ? $args[0]: array());
            case preg_match('/^link/', $name):
                return $this->link(
                        substr($name, 5),
                        isset($args[0]) ? $args[0] : array());
            case preg_match('/^url/', $name):
                return $this->url(
                    explode( '_', substr($name, 4)),
                    isset($args[0]) ? $args[0] : array() );
                }
        return $this->get($name);
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        return $this->$name();
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
     * @param string $name
     * @return string
     */
    protected function get($name) {
        $call = sprintf('get%s', ucfirst($name));
        if(method_exists($this, $call)){
            return $this->$call();
        }
        return $this->hasContent() ? $this->content()->get($name) : '';
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
     * @param string $action
     * @param array $args
     * @return string
     */
    protected function __action($action = '' , $args = array() ) {
        return $this->action($action, is_array($args) ? $args : array());
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
     * @return bool
     */
    protected function hasContent(){
        return !is_null( $this->content());
    }
}




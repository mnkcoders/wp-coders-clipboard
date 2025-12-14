<?php namespace CODERS\Clipboard\Pub;

defined('ABSPATH') or die;


add_action( 'coder_clipboard', function( $clipdata = array()){
    
    $id = $clipdata[0] ?? '';
    $context = $clipdata[1] ?? $id;
    $page = $clipdata[2] ?? '';
    \CODERS\Clipboard\Pub\View::create(
            $id,
            $context,
            strlen($page) ? intval($page) : 0
    )->show();
});

/**
 * Make all reqired overrides to publish hte current clipboard
 */
class Content extends \CODERS\Clipboard\Clip{
    
}

/**
 * 
 */
class View{
    /**
     * @var \CODERS\Clipboard\Clip
     */
    private $_clip = null;
    /**
     * @var array
     */
    private $_log = array();
    
    /**
     * @param Clip $clip
     * @param string $context_id
     */
    protected function __construct( Clip $clip = null ) {
        $this->_clip = $clip;
    }
    
    /**
     * @param string $name
     * @return string
     */
    public function __get($name) {
        return $this->hasContent() ? $this->clip()->$name : '';
    }
    /**
     * 
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call( $name,  $arguments ) {
        $args = is_array($arguments) ? $arguments : array();
        switch(true){
            case preg_match('/^get_/', $name):
                $get = sprintf('get%s', ucfirst(substr($name, 4)));
                return method_exists($this, $get) ? $this->$get(...$args) : '';
            case preg_match('/^list_/', $name):
                $list = sprintf('list%s', ucfirst(substr($name, 5)));
                return method_exists($this, $list) ? $this->$list() : array();
            case preg_match('/^count_/', $name):
                $count = sprintf('count%s', ucfirst(substr($name, 6)));
                return method_exists($this, $count) ? $this->$count() :  0;
            case preg_match('/^is_/', $name):
                $is = sprintf('is%s', ucfirst(substr($name, 3)));
                return method_exists($this, $is) ? $this->$is() : false;
            case preg_match('/^has_/', $name):
                $has = sprintf('has%s', ucfirst(substr($name, 4)));
                return method_exists($this, $has) ? $this->$has() : false;
            case preg_match('/^can_/', $name):
                $can = sprintf('can%s', ucfirst(substr($name, 4)));
                return method_exists($this, $can) ? $this->$can() : false;
            case preg_match('/^show_/', $name):
                return $this->template(substr($name, 5));
            case preg_match('/^action_/', $name):
                return $this->action( substr($name, 7), ... $args );
            case preg_match('/^link/', $name):
                return $this->link( explode('_',substr($name, 5)), ... $args );
        }
        return '';
        //return !is_null($this->load()) ? $this->load()->$name : '';
    }
    
    /**
     * @return \CODERS\Clipboard\Clip
     */
    private function clip() {
        return $this->_clip;
    }
    /**
     * @param array $path
     * @param array $args
     * @return string
     */
    protected function link( $path = array() , array $args = array() ){
        $url = site_url( count($path) ? implode('/', $path) : '' );
        $get = array();
        foreach( $args as $var => $val ){
            $get[] = sprintf('%s=%s',$var,$val);
        }
        if( count( $get )){
            $url .=  '?' . implode('&', $get);
        }
        return $url;
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
        $id = $this->id;
        if( $id ){
            if(strlen($action)){
                $args['action'] = $action;
            }
            return $this->link( array(
                CODER_CLIPBOARD_APP,
                $this->id
            ) , $args);
        }
        return '';
    }
    /**
     * @param string $message
     * @param string $type
     * @return \CODERS\Clipboard\View
     */
    private function log( $message = '' , $type = 'info') {
        $this->_log[] = array(
            'content' => $message,
            'type' => $type,
        );
        return $this;
    }
    /**
     * @param string $file
     * @return string
     */
    public static function path( $file = '' ) {
        return sprintf('%shtml/public/%s', CODER_CLIPBOARD_DIR,$file);
    }
    /**
     * @param string $file
     * @return string
     */
    public static function url( $file = '' ) {
        return sprintf('%s/html/public/%s', CODER_CLIPBOARD_URL,$file);
    }
    /**
     * @param string $view
     * @return bool
     */
    protected function template( $view = '' ){
        printf('<!-- TEMPLATE [%s] -->' , $view);
        return strlen($view) && $this->view(sprintf('templates/%s',$view));
    }

    /**
     * @param string $view
     * @return bool
     */
    protected function view($view = '') {
        $path = $this->path($view . '.php' );
        if( file_exists($path)){
            require $path;
            return true;
        }
        return false;
    }
    
    /**
     * 
     */
    protected function header() {
        add_filter( 'body_class', function( $classes ) {
            return array_merge( $classes, array('coders-clipboard') );
        } );
        
        add_action( 'wp_enqueue_scripts' , function(){
            wp_enqueue_style(
                    'coders-clipboard-style',
                    \CODERS\Clipboard\View::url('content/style.css'));
            wp_enqueue_script(
                    'coders-clipboard-script',
                    \CODERS\Clipboard\View::url('content/script.js'));
        });
        
        $layout = $this->layout;
        wp_head();
        //render body class
        printf('<body class="coders-clipboard %s %s">',
                $layout,
                implode(' ', get_body_class()));
    }
    /**
     * 
     */
    protected function content() {
        if( $this->canAccess()){
            $this->view($this->layout ?? 'default' );
        }
        else {
            $this->log( 'Cannot access this clipboard','error');
            $this->view('error');
        }
    }
    /**
     * 
     */
    protected function footer() {
        
            //prepare bottom menu container for user profile
            print '<div class="container bottom menu">';
            //then call the menu contents
            do_action('coders_sidebar','clipboard',$this->id);
            print '</div>';
            //finally, close the
            wp_footer();
            //print '</body></html>';
        
    }

    /**
     * @return bool
     */
    protected function hasContent() {
        return !is_null($this->clip());
    }
    /**
     * @return bool
     */
    protected function canAccess() {
        return $this->hasContent() && !$this->clip()->isDenied();
    }
    /**
     * @return bool
     */
    protected function isMedia() {
        return $this->hasContent() && $this->clip()->isMedia();
    }
    /**
     * @return int
     */
    protected function countItems() {
        return $this->hasContent() ? $this->clip()->countItems() : 0;
    }
    /**
     * @return int
     */
    protected function countAvailable() {
        return count($this->listAvailable());
    }
    /**
     * @return array
     */
    protected function listMessages() {
        return $this->_log;
    }
    /**
     * @return array
     */
    protected function listPath() {
        return $this->hasContent() ? $this->clip()->listPath() : array();
    }
    /**
     * @return \CODERS\Clipboard\Clip[]
     */
    protected function listItems() {
        return $this->hasContent() ? $this->clip()->listItems() : array();
    }
    /**
     * @return \CODERS\Clipboard\Clip[]
     */
    protected function listAvailable(){
        return array_filter( $this->listItems(),function($item){
            return !$item->isDenied();
        });
    }
    /**
     * @return string
     */
    public function getContext() {
        $ids = array_keys($this->listPath());
        return $ids[0] ?? '';
    }
    /**
     * @return string
     */
    protected function getUrl($id = '') {
        return Content::clipdata(strlen($id) ? $id : $this->id);
    }
    /**
     * @param string $id
     * @return string
     */
    protected function getClipboard( $id = '' ) {
        return Content::clipboard(
                strlen($id) ? $id : $this->id,
                $this->getContext()
        );
    }
    
    
    /**
     * 
     */
    public function show(){
        $this->header();
        $this->content();
        $this->footer();
    }
    
    /**
     * @param string $id
     * @param string $root
     */
    public static final function create( $id = '' ,$root = ''){
        return new View( Clip::load($id,true,$root) );
    }
}




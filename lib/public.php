<?php namespace CODERS\Clipboard;

defined('ABSPATH') or die;


add_action( 'coder_clipboard', function( $id = ''){
    \CODERS\Clipboard\Dashboard::create(Clip::load($id));
});


/**
 * 
 */
class Dashboard{
    /**
     * @var \CODERS\Clipboard\Clip
     */
    private $_clip = null;
    /**
     * @var array
     */
    private $_log = array();
    /**
     * @var string
     */
    private $_context = '';
    
    /**
     * @param Clip $clip
     * @param string $context
     */
    protected function __construct( Clip $clip = null , $context = '' ) {
        $this->_clip = $clip;
        $this->_context = $context ?? $this->id;
    }
    
    /**
     * @param string $name
     * @return string
     */
    public function __get($name) {
        
        return !is_null($this->clip()) ? $this->clip()->$name : '';
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
            case preg_match('/^list_/', $name):
                $list = sprintf('list%s', ucfirst(substr($name, 5)));
                return method_exists($this, $list) ? $this->$list() : array();
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
                $view = substr($name, 5);
                return (strlen($view) && $this->view('parts/'.$view)) ?
                    sprintf('<!-- part [%s] -->',$view) :
                    sprintf('<!-- part [%s] not found -->',$view);
            case preg_match('/^action_/', $name):
                return $this->action( substr($name, 7), ... $args );
            case preg_match('/^link/', $name):
                return $this->link( substr($name, 5), ... $args );
            case preg_match('/^url/', $name):
                return $this->url( explode( '_', substr($name, 4)), ...$args);
        }
        return '';
    }
    
    /**
     * @return \CODERS\Clipboard\Clip
     */
    private function clip() {
        return $this->_clip;
    }
    /**
     * @param string $message
     * @param string $type
     * @return \CODERS\Clipboard\Dashboard
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
        return sprintf('%s/html/public/%s', CODER_CLIPBOARD_DIR,$file);
    }
    /**
     * @param string $view
     * @return bool
     */
    protected function view($view = '') {
        $path = $this->path($view . '.php' );
        if(file_exists($path)){
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
                    \CODERS\Clipboard\Dashboard::path('content/style.css'));
            wp_enqueue_script(
                    'coders-clipboard-script',
                    \CODERS\Clipboard\Dashboard::path('content/script.js'));
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
        return $this->hasContent() && !$this->clip()->denied();
    }
    
    
    
    
    
    /**
     * @param \CODERS\Clipboard\Clip $clip
     */
    public static final function create(Clip $clip = null ){
        if( !is_null($clip)){
            $dasboard = new Dashboard($clip);
            $dasboard->header();
            $dasboard->content();
            $dasboard->footer();
        }
    }
}







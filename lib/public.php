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
     * @param Clip $clip
     */
    protected function __construct( Clip $clip = null ) {
        
        $this->_clip = $clip;
    }
    
    /**
     * @param string $name
     * @return string
     */
    public function __get($name) {
        
        
        return !is_null($this->clip()) ? $this->clip()->$name : '';
    }
    
    public function __call(string $name, array $arguments) {
        $args = is_array($arguments) ? $arguments : array();
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
     * @return \CODERS\Clipboard\Clip
     */
    private function clip() {
        return $this->_clip;
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
                    \CODERS\Clipboard\Dashboard::path('style.css'));
            wp_enqueue_script(
                    'coders-clipboard-script',
                    \CODERS\Clipboard\Dashboard::path('script.js'));
        });
        $layout = $this->layout;
            wp_head();
            //render body class
            printf('<body class="coders-clipboard %s %s">',$layout, implode(' ', get_body_class()));
    }
    /**
     * 
     */
    protected function content() {
        if( $this->canAccess()){
            $this->_context = $this->id;
            $this->view($this->layout ?? 'default' );
        }
        else {
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







<?php defined('ABSPATH') or die;
/* * *****************************************************************************
 * Plugin Name: Coders Clipboard
 * Description: Hierarchical Drag-Drop media gallery with access control and collection display
 * Version: 0.5
 * Author: Coder01
 * License: GPLv2 or later
 * Text Domain: coders_clipboard
 * Domain Path: lang
 * Class: Clipboard
 * **************************************************************************** */


// Clipboard Class
class Clipboard {
    
    /**
     * @var \ClipboardContent
     */
    private $_content = null;
    
    /**
     * 
     */
    private $_data = array(
        'id' => '',
        'name' => '',
        'type' => '',
        'title' => '',
        'description' => '',
        'acl' => '',
        'layout' => 'default',
        'created_at' => '',
        'parent_id' => '',
    );
    /**
     * @type {String[]} Item cache
     */
    private $_items = [];
    
    /**
     * @param String $id 
     */
    //protected function __construct( \ClipBoardContent $content = null ){
    protected function __construct( $id  = ''){
        
        $this->_data['created_at'] = self::timestamp();
        
        if(strlen($id)){
            $this->populate(self::load($id) );
            $this->addItems(self::children($this->id));
            //$this->_items = $this->children();            
        }
    }
    /**
     * @return String 
     */
    public static final function timestamp(){
        return date('Y-m-d H:i:s');
    }
    
    protected function populate( $input = [] ){
        foreach( $input as $field => $value ){
            if( isset($this->_data[$field])){
                $this->_data[$field] = $value;
            }
        }
    }
    /**
     * @param String[] $items
     * @return Clipboard
     */
    protected function addItems( $items = array() ){
        foreach( $items as $id  => $atts ){
            $this->_items[$id] = $atts;
        }
        return $this;
    }
    /**
     * @return String
     */
    public function __get( $name ){
        return $this->has($name) ? $this->_data[$name] : '';
    }
    /**
     * @param string $name
     * @param array $arguments
     * @return string|array|bool|int
     */
    public function __call($name, $arguments) {
        switch (true) {
            case preg_match('/^is_/', $name):
                return $this->__run('is'.substr($name, 3), false, $arguments);
                //return $this->is(substr($name, 3),$arguments);
            case preg_match('/^has_/', $name):
                return $this->__run('has'.substr($name, 4), false, $arguments);
                //return $this->has(substr($name, 4));
            case preg_match('/^count_/', $name):
                return $this->__run('count'.substr($name, 6), 0, $arguments);
                //return $this->count(substr($name, 6));
            case preg_match('/^list_/', $name):
                return $this->__run('list'.substr($name, 5), array(), $arguments);
                //return $this->list(substr($name, 5));
            case preg_match('/^get_/', $name):
                return $this->__run('get'.substr($name, 4), '', $arguments);
                //return $this->get(substr($name, 4),$arguments);
            case preg_match('/^view_/', $name):
                return $this->__render(substr($name, 5), is_admin() ? 'admin' : 'public') ;
            case preg_match('/^part_/', $name):
                return $this->__part(substr($name, 5), is_admin() ? 'admin' : 'public') ;
        }
        return sprintf('<!-- INVALID INPUT %s -->', $name);
    }
    /**
     * @param string $call
     * @param mixed $default
     * @param array $arguments
     * @return mixed
     */
    protected final function __run( $call , $default = false , $arguments = array()){
        return method_exists($this, $call) ? $this->$call( $arguments ) : $default;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return string|int|array|bool
     */
    private final function get( $name , $arguments = array()){
        $call = 'get' . $name;
        if(method_exists($this, $call)){
            return $this->$call( $arguments );
        }
        return $this->__get($name);
    }


    /**
     * @param String $name
     * @return int
     */
    private final function list($name) {
        $call = 'list' . $name;
        return method_exists($this,$call) ? $this->$call() : array();
    }
    /**
     * @param String $name
     * @return int
     */
    private final function count($name) {
        $call = 'count' . $name;
        return method_exists($this,$call) ? $this->$call() : 0;
    }
    /**
     * @param String $name
     * @param array $args
     * @return boolean
     */
    private final function is($name , $args = array()) {
        $call = 'is' . $name;
        return method_exists($this,$call) ? $this->$call($args) : false;
    }
    /**
     * @return Boolean
     */
    private function has( $name ){
        return isset($this->_data[$name]);
    }
    /**
     * @return  STring Description
     */
    public final function getLink( $ids = array() ){
        return self::LINK( count($ids) ? $ids[0] : $this->id );
    }
    /**
     * @return  STring Description
     */
    public final function getUrl( ){
        return self::LINK( $this->id );
    }
    /**
     * @return Boolean
     */
    public final function isValid(){
        return strlen($this->id) > 0;
    }
    /**
     * @return Boolean
     */
    public final function isReady(){
        return !is_null($this->_content);
    }
    /**
     * @return Boolean
     */
    public function isImage( $args = array() ){
        return stripos(count($args) ? $args[0] : $this->type, 'image/') === 0;
    }
    /**
     * @return Boolean
     */
    private function isDenied(){
        return $this->acl !== 'public' && !$this->isAdmin();
    }
    /**
     * @return String[]
     */
    public function listItems(){
        return $this->_items;
    }
    /**
     * @return array
     */
    public function listPath(){
        return $this->hierarchy();
    }
    /**
     * @return Int
     */
    public function countItems(){
        return count($this->listItems());
    }
    /**
     * @return array
     */
    protected function hierarchy(){
        if( $this->isValid()){
            if(strlen($this->parent_id) ){
                $parent = new Clipboard($this->parent_id);
                $path = $parent->hierarchy();
                $path[ $this->id] = $this->title;
                return $path;
            }
            else{
                return array(
                    $this->id => $this->title );
            }
        }
        return array();
    }

    /**
     * @return Array
     */
    protected static function load($id) {
        global $wpdb;
        $table = Clipboard::table();
        $results = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table` WHERE `id`='%s'",$id));
        return !is_null($results) ? $results : array();
    }
    /**
     * @global wpdb $wpdb
     * @param string $id
     * @return array
     */
    protected static function parent( $id ){
        global $wpdb;
        $table = Clipboard::table();
        $results = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table` WHERE `parent_id`='%s'",$id));
        return !is_null($results) ? $results : array();
    }

    /**
     * @return String[]
     */
    protected static function children( $id = '' ){
            global $wpdb;
            $table = Clipboard::table();
            $list = $wpdb->get_results( strlen($id) ?
                    //$wpdb->prepare("SELECT `id`,`title` FROM `$table` WHERE `parent_id`='%s'", $id):
                    $wpdb->prepare("SELECT * FROM `$table` WHERE `parent_id`='%s'", $id):
                    //$wpdb->prepare("SELECT `id`,`title` FROM `$table` WHERE `parent_id` IS NULL")
                    $wpdb->prepare("SELECT * FROM `$table` WHERE `parent_id` IS NULL")
                ,ARRAY_A );
            
            if( !is_null($list)){
                $output = array();
                foreach($list as $row){
                    $output[$row['id']] = $row;
                }
                return $output;
            }
    }
    /**
     * @param string $part
     * @param string $path
     * @return string
     */
    protected function __part( $part = '' , $path = 'public'){
        require $this->__render( $part , $path . '/parts');
    }

    /**
     * @param String $view
     * @param Boolean $path
     * @return String 
     */
    protected function __render( $view = 'default' , $path = 'public' ) {
        return self::assetPath(sprintf('html/%s/%s.php', $path , $view));
    }
    /**
     * 
     */
    public function error404(){
            status_header(404);
            wp_die(__('Clipboard item not found.', 'coders_clipboard'));
    }
    /**
     * 
     */
    public function errorDenied(){
            status_header(403);
            echo __('Access denied.', 'coders_clipboard');
            exit;
    }
    /**
     * 
     */    
    public function errorInvalid(){
            status_header(404);
            echo __('File not found.', 'coders_clipboard');
            exit;
    }

    /**
     * 
     */
    public function content() {
        if (!$this->isValid()) {
            return $this->error404();
        }
        if ( $this->isDenied()) {
            return $this->errorDenied();
        }
        if (!$this->exists()) {
            return $this->errorInvalid();
        }
        $this->serve();
    }    
    /**
     * @return Boolean
     */
    public function inline(){
        $inline_types = ['image/', 'text/', 'application/pdf'];
        foreach ($inline_types as $type_prefix) {
            if (stripos($this->type, $type_prefix) === 0) {
                return true;
            }
        }
        return false;
    }
    /**
     * 
     */
    private function serve(){
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $this->type);
        header('Content-Disposition: ' . $this->disposition() . '; filename=' . $this->filename());
        header('Content-Length: ' . $this->size());
        readfile($this->getPath());
        exit;
    }
    /**
     * @return String
     */
    private function filename(){
        $extension = explode('/', $this->type)[1] ?? 'bin';
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', basename($this->name)) . '.' . $extension;
    }
    /**
     * @return String
     */
    private function getPath(){
        return self::path($this->id);
        //return CLIPBOARD_UPLOAD_DIR . '/' . $this->id;
    }
    /**
     * @return Int
     */
    public function size(){
        return $this->exists() ? filesize($this->getPath()) : 0;
    }
    /**
     * @return String
     */
    public function disposition(){
        return $this->inline() ? 'inline' : 'attachment';
    }
    /**
     * @return Boolean
     */
    protected function exists(){
        return file_exists($this->getPath());
    }
    /**
     * @param string $id
     */
    public static function attach( $id ){
        $clipboard = new ClipBoard( $id );
        $clipboard->content();
    }
    /**
     * @param string $content
     */
    public static function display( $content = '' ){
        $clipboard = new ClipBoard( $content );
        if($clipboard->isValid()){
            $view = $clipboard->__render();
            if( file_exists($view) ){
                include $view;
            }
            else {
                $clipboard->errorInvalid();
            }
        }
    }

    /**
     * @return {String}
     */
    public static function path( $id = '' ){
        return strlen($id) ?
            sprintf('%s/clipboard/content/%s',wp_upload_dir()['basedir'],$id) :
            sprintf('%s/clipboard/content',wp_upload_dir()['basedir']);
    }
    /**
     * @return {String}
     */
    public static function url( $url = ''){
        return strlen($url) ?
             sprintf('%s/clipboard/%s',wp_upload_dir()['baseurl'],$url) :
             sprintf('%s/clipboard',wp_upload_dir()['baseurl']);
    }
    /**
     * @param String $id
     * @return String
     */
    public static final function LINK( $id = ''){
        return get_site_url(null, 'clipboard/' . $id);
    }
    /**
     * @return {String}
     */
    public static final function table(){
        return $GLOBALS['wpdb']->prefix . 'clipboard_items';
    }
    /**
     * @param string $content
     * @return String
     */
    public static final function assetPath( $content = '' ){
        $path = plugin_dir_path(__FILE__ );
        
        return strlen($content) ? sprintf('%s/%s',$path,$content) : $path;
    }
    /**
     * @param string $content
     * @return String
     */
    public static final function assetUrl( $content = '' ){
        $url = plugin_dir_url(__FILE__);
        
        return strlen($content) ? $url.$content : $url;
    }
    /**
     * @return  boolean
     */
    public static final function isAdmin(){
        return current_user_can( 'administrator' );
    }
}
    
/**
 * 
 */
class ClipBoardContent{
    /**
     * @var array
     */
    private $_content = array(
        'id' => '',
        'name' => '',
        'type' => '',
        'title' => '',
        'description' => '',
        'acl' => '',
        'layout' => 'default',
        'created_at' => '',
        'parent_id' => '',        
    );
    /**
     * @type {String[]} Item cache
     */
    private $_collection = [];
    
    /**
     * @param array $input
     */
    protected function __construct( $input = array()) {
        
        $this->_content['created_at'] = self::timestamp();
        $this->populate( $input );
    }
    /**
     * @return String 
     */
    public static final function timestamp(){
        return date('Y-m-d H:i:s');
    }
    /**
     * @param array $input
     */
    protected function populate( $input = [] ){
        foreach( $input as $field => $value ){
            if( isset($this->_content[$field])){
                $this->_content[$field] = $value;
            }
        }
    }
    /**
     * @param string $name
     * @return string
     */
    public function __get( $name ){
        return $this->has($name) ? $this->_content[$name] : '';
    }
    /**
     * @param string $name
     * @param array $arguments
     * @return string
     */
    public function __call( $name  ,$arguments ){
        switch(true){
            case preg_match('/^is_/', $name):
                $is = 'is'.substr($name, 3);
                return method_exists($this, $is) ? $this->$is( $arguments ) : false;
            case preg_match('/^get_/', $name):
                $get = 'get'.substr($name, 4);
                return method_exists($this, $get) ? $this->$get( $arguments ) : '';
            case preg_match('/^list_/', $name):
                $list = 'list'.substr($name, 5);
                return method_exists($this, $list) ? $this->$list( $arguments ) : array();
            case preg_match('/^count_/', $name):
                $count = 'count'.substr($name, 6);
                return method_exists($this, $count) ? $this->$count( $arguments ) : 0;
            case preg_match('/^action_/', $name):
                return $this->__action( substr($name, 7) , $arguments);
        }
        return '';
    }
    /**
     * @param string $action
     * @param array $arguments
     * @return string
     */
    protected function __action( $action , $arguments = array()){
        $class = '';
        $url = '';
        $text = '';
        return sprintf('<a class="%s" href="%s">%s</a>',$class,$url,$text);
    }

    /**
     * @param string $attribute
     * @return boolean
     */
    public function has( $attribute = '' ){
        return strlen($attribute) && array_key_exists($attribute, $this->_content);
    }
    /**
     * @return array
     */
    public final function content(){
        return $this->_content;
    }
    /**
     * @param boolean $refresh
     * @return array
     */
    protected function collection( $refresh = false ){
        if( $refresh ){
            $this->_collection = self::list($this->id);
        }
        return $this->_collection;
    }    
    /**
     * @return  STring Description
     */
    public final function getUrl( ){
        return Clipboard::LINK( $this->id );
    }
    /**
     * @return Boolean
     */
    public final function isValid(){
        return strlen($this->id) > 0;
    }
    /**
     * @return Boolean
     */
    public function isDenied(){
        return $this->acl !== 'public' && !$this->isAdmin();
    }
    /**
     * @return Boolean
     */
    public function isImage( $args = array() ){
        return stripos(count($args) ? $args[0] : $this->type, 'image/') === 0;
    }
    /**
     * @return Boolean
     */
    public function isInline(){
        $inline_types = ['image/', 'text/', 'application/pdf'];
        foreach ($inline_types as $type_prefix) {
            if (stripos($this->type, $type_prefix) === 0) {
                return true;
            }
        }
        return false;
    }
    /**
     * @return String
     */
    public function getFilename(){
        $extension = explode('/', $this->type)[1] ?? 'bin';
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', basename($this->name)) . '.' . $extension;
    }
    /**
     * @return String
     */
    private function getPath(){
        return Clipboard::path($this->id);
    }
    /**
     * @return Int
     */
    public function getSize(){
        return $this->exists() ? filesize($this->getPath()) : 0;
    }
    /**
     * @return String
     */
    public function getDisposition(){
        return $this->isInline() ? 'inline' : 'attachment';
    }
    /**
     * @return Boolean
     */
    protected function isAvailable(){
        return file_exists($this->getPath());
    }
    /**
     * @return Int
     */
    public function countItems(){
        return count($this->collection());
    }    
    /**
     * @return array
     */
    public function listParents(){
        if( $this->isValid()){
            if(strlen($this->parent_id) ){
                //$parent = new Clipboard($this->parent_id);
                $parent = self::load($this->parent_id);
                $path = $parent->listParents();
                $path[ $this->id] = $this->title;
                return $path;
            }
            else{
                return array(
                    $this->id => $this->title );
            }
        }
        return array();
    }
    /**
     * @return array
     */
    public function listItems(){
        return $this->collection(true);
    }

    
    /**
     * @global wpdb $wpdb
     * @param type $id
     * @return \ClipBoardContent
     */
    public static function load( $id = '' ){
        global $wpdb;
        if(strlen($id)){
            $table = self::table();
            $content = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table` WHERE `id`='%s'",$id));
            return !is_null($content) ? new ClipBoardContent($content) : array();
        }
        return null;
    }
    /**
     * @global wpdb $wpdb
     * @param string $id
     * @return array
     */
    protected static function list( $id = '' ){
        global $wpdb;
        //$collection = array();
        $table = self::table();
        
        $list = $wpdb->get_results( strlen($id) ?
                    $wpdb->prepare("SELECT * FROM `$table` WHERE `parent_id`='%s'", $id):
                    $wpdb->prepare("SELECT * FROM `$table` WHERE `parent_id` IS NULL")
                , ARRAY_A );
        
        if(!is_null($list)){
            foreach($list as $content ){
                $collection[$content['id']] = new ClipBoardContent($content);
            }
        }
        return $collection;
    }    

    /**
     * @global wpdb $wpdb
     */
    public static final function install(){
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . self::table() .
            " ( id VARCHAR(64) NOT NULL PRIMARY KEY,
            parent_id VARCHAR(64) DEFAULT NULL,
            name VARCHAR(32) NOT NULL,
            type VARCHAR(24) DEFAULT 'application/octet-stream',
            title VARCHAR(48) NOT NULL,
            description TEXT,
            layout VARCHAR(24) DEFAULT 'default',
            acl VARCHAR(16) DEFAULT 'private',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset_collate;";

        dbDelta($sql);        
    }
    /**
     * @return string
     */
    public static final function table(){
        return $GLOBALS['wpdb']->prefix . 'clipboard_items';
    }
}





// Activation Hook
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    ClipBoardContent::install();

    //check the upload directory
    $uploads = Clipboard::path();
    if( !file_exists($uploads)){
        wp_mkdir_p($uploads);
    }
});

register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Rewrite Rules
add_action('init', function() {
    //flush_rewrite_rules();
    
    add_rewrite_tag('%clipboard_id%', '([a-zA-Z0-9_-]+)');
    add_rewrite_rule('^clipboard/([a-zA-Z0-9_-]+)/?$', 'index.php?clipboard_id=$matches[1]', 'top');
    add_rewrite_rule('^clipboards/([a-zA-Z0-9_-]+)/?$', 'index.php?clipboard_id=$matches[1]&mode=view', 'top');
    //add_rewrite_rule('^clipboards/?$', 'index.php?clipboards=1', 'top');
    
    add_shortcode('clipboard_view', function($atts){
        $atts = shortcode_atts(['id' => ''], $atts);
        ob_start();
        Clipboard::display($atts['id']);
        return ob_get_clean();
    });

    
    if(is_admin()){
        require_once sprintf('%s/admin.php',plugin_dir_path(__FILE__));
        /*add_action( 'admin_init',function(){
            require_once sprintf('%s/admin.php',plugin_dir_path(__FILE__));
        });*/
        
     
    }
});

add_filter('query_vars', function($vars) {
    $vars[] = 'clipboard_id';
    //$vars[] = 'clipboards';
    $vars[] = 'mode';
    return $vars;
});


// Redirect Handler
//add_action('template_redirect', ['Clipboard', 'handle_request']);
add_action('template_redirect', function(){
    //$id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : null;
    $id = get_query_var('clipboard_id');
    if( $id ){
        $mode = get_query_var('mode');
        if($mode === 'view' ){
            Clipboard::display( $id );
        }
        else{
            Clipboard::attach( $id );
        }
        exit;                    
    }
});


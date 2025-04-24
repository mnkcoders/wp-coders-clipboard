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

// Activation Hook
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE " . Clipboard::table() .
        " ( id VARCHAR(64) NOT NULL PRIMARY KEY,
        parent_id VARCHAR(64) DEFAULT NULL,
        name VARCHAR(32) NOT NULL,
        type VARCHAR(24) DEFAULT 'application/octet-stream',
        title VARCHAR(32) NOT NULL,
        description TEXT,
        layout VARCHAR(24) DEFAULT 'default',
        acl VARCHAR(16) DEFAULT 'private',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset_collate;";

    dbDelta($sql);
    //check the upload directory
    $uploads = Clipboard::dir();
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
        add_action( 'admin_init',function(){
            require_once sprintf('%s/admin.php',plugin_dir_path(__FILE__));
        });

        add_action('admin_menu', function () {
            add_menu_page(
                    __('Coders Clipboard', 'coders_clipboard'),
                    __('Clipboard', 'coders_clipboard'),
                    'upload_files', // or 'manage_options' if more restricted
                    'coders-clipboard',
                    function () {
                ClipboardAdmin::display();
                //require_once __DIR__ . '/admin.php'; // This will instantiate ClipboardAdmin
            },
                    'dashicons-format-gallery',
                    80
            );
        });        
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



// Clipboard Class
class Clipboard {
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
                return $this->is(substr($name, 3),$arguments);
            case preg_match('/^has_/', $name):
                return $this->has(substr($name, 4));
            case preg_match('/^count_/', $name):
                return $this->count(substr($name, 6));
            case preg_match('/^list_/', $name):
                return $this->list(substr($name, 5));
            case preg_match('/^get_/', $name):
                return $this->get(substr($name, 4),$arguments);
            case preg_match('/^view_/', $name):
                return $this->view(substr($name, 5)) ;
        }
        return sprintf('<!-- INVALID INPUT %s -->', $name);
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
    public function isImage( $args = array() ){
        return stripos(count($args) ? $args[0] : $this->type, 'image/') === 0;
    }
    /**
     * @return Boolean
     */
    public function denied(){
        return !$this->isValid() || $this->acl !== 'public';
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
                    '' => __('Clipboards','coders_clipboard'),
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
     * @param String $view
     * @param Boolean $path
     * @return String 
     */
    public function view( $view = 'default' , $path = 'public' ) {
        return sprintf('%shtml/%s/%s.php',plugin_dir_path(__FILE__), $path , $view);
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
        if ( $this->denied()) {
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
        readfile($this->path());
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
    private function path(){
        return self::dir($this->id);
        //return CLIPBOARD_UPLOAD_DIR . '/' . $this->id;
    }
    /**
     * @return Int
     */
    public function size(){
        return $this->exists() ? filesize($this->path()) : 0;
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
        return file_exists($this->path());
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
            $view = $clipboard->view();
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
    public static function dir( $path = '' ){
        return strlen($path) ?
            sprintf('%s/clipboard/%s',wp_upload_dir()['basedir'],$path) :
            sprintf('%s/clipboard',wp_upload_dir()['basedir']);
    }
    /**
     * @return {String}
     */
    public static function url( $url = ''){
        return strlen($path) ?
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
}
    


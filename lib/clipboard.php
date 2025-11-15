<?php namespace CODERS\Clipboard;

defined('ABSPATH') or die;

/**
 * 
 */
class Clipboard{
    /**
     * @var \CODERS\Clipboard\Clipboard
     */
    private static $_instance = null;
    /**
     * 
     */
    private function __construct() {
        //
    }
    /**
     * @return \CODERS\Clipboard\Clipboard
     */
    public static function instance(){
        if(is_null(self::$_instance)){
            self::$_instance = new Clipboard();
        }
        return self::$_instance;
    }
    
    
    
    /**
     * @param string $drive
     * @return \CODERS\Clipboard\Storage
     */
    public function storage( $drive = ''){
        return new Storage( strlen($drive) ? $drive : 'content' );
    }
    /**
     * @return \CODERS\Clipboard\ClipData
     */
    public function data() {
        return new ClipData();
    }
    /**
     * @param string $id
     * @param  bool $preload
     * @return \CODERS\Clipboard\Clip
     */
    public function load($id = '',$preload = false){
        return Clip::load($id , $preload );
    }
    /**
     * @param string $id
     * @return \CODERS\Clipboard\Clip[]
     */
    public function list( $id = '' ){
        $list = $this->data()->list($id);
        return array_map( function( $data ){
            return new Clip($data);
        },$list);
    }

    /**
     * @param array $data
     * @return \CODERS\Clipboard\Clip
     */
    public function create(array $data = array()) {
        return Clip::create($data);
    }
    
    /**
     * 
     */
    protected function error404(){
        status_header(404);
        wp_die(__('Clipboard item not found.', 'coders_clipboard'));
    }
    /**
     * 
     */
    protected function errorDenied(){
        status_header(403);
        wp_die(__('Access denied', 'coders_clipboard'));
    }
    
    
    /**
     * @param string $id
     */
    public static function attach( $id = ''){
        $clipboard = self::instance();
        $clip = $clipboard->load($id);
        switch(true){
            case is_null($clip):
                return $clipboard->error404();
            case !$clip->ready():
                return $clipboard->error404();
            case $clip->denied():
                return $clipboard->errorDenied();
        }
        foreach( $clip->headers() as $header ){
            header($header);
        }
        readfile($clip->path());
        exit;
    }
    /**
     * @param string $id
     * @return true
     */
    public static function board( $id = '' ) {
        if(strlen($id)){
                require_once sprintf('%s/lib/public.php', CODER_CLIPBOARD_DIR);
                do_action('coder_clipboard',$id);
                return true;
        }
        return false;
    }

    /**
     * @param string $role
     * @return boolean
     */
    public static function acl( $role = '' ){
        $admin = current_user_can( 'administrator' );
        $roles = array(
            'public',
            apply_filters('coder_acl','')
        );
        return $admin || in_array($role, $roles);
    }    
    
    /**
     * @param String $id
     * @return String
     */
    public static function clipboard( $id = ''){
        return get_site_url(null, sprintf('%s/%s', CODER_CLIPBOARD_UI,$id));
    }
    /**
     * @param String $id
     * @return String
     */
    public static function clipdata( $id = ''){
        return get_site_url(null, sprintf('%s/%s', CODER_CLIPBOARD_CONTENT,$id));
    }
    
    /**
     * @param string $id
     * @return {String}
     */
    public static function drive( $id = '' ){
        $path = sprintf('%s/clipboard/content/',wp_upload_dir()['basedir']);
        return strlen($id) ? $path . $id : $path;
    }    
    /**
     * @param bool $flush
     */
    public static function rewrite( $flush = false ){

        add_rewrite_tag('%clipboard_id%', '([a-zA-Z0-9_-]+)');
        add_rewrite_tag('%clip_id%', '([a-zA-Z0-9_-]+)');

        $content = sprintf('^%s/([a-zA-Z0-9_-]+)/?$', CODER_CLIPBOARD_CONTENT);
        $clipboard = sprintf('^%s/([a-zA-Z0-9_-]+)/?$', CODER_CLIPBOARD_UI);
       
        add_rewrite_rule( $content , 'index.php?clip_id=$matches[1]' , 'top');
        add_rewrite_rule( $clipboard, 'index.php?clipboard_id=$matches[1]', 'top');

        if( $flush ){
            flush_rewrite_rules();
        }
    }
    /**
     * Install plugin
     */
    public static function setup(){
        //flush_rewrite_rules();
        self::rewrite(true);
        $cb = self::instance();
        $cb->data()->install();
        $cb->storage()->create();
    }
}

/**
 * 
 */
class Clip{

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
        'parent_id' => '',        
        'slot' => 0,
        'tags' => '',
        //'drive' => '',
        'created_at' => '',
    );
    /**
     * @var bool
     */
    private $_updated = false;
    
    /**
     * @var \CODERS\Clipboard\Clip[]
     */
    private $_items = array(
        //clip contents
    );

    
    /**
     * @param array $input
     * @param bool $preload
     */
    public function __construct( $input = array() , $preload = false ) {
        $this->populate( $input );
        if( !array_key_exists('created_at', $input)){
            $this->_content['created_at'] = date('Y-m-d H:i:s');
        }
        if($preload){
            $this->_items = $this->loaditems();
        }
    }
    /**
     * @return String
     */
    public function __get( $name ){
        return $this->has($name) ? $this->_content[$name] : '';
    }
    /**
     * @param string $name
     * @param array $arguments
     * @return string
     */
    public function __call($name , $arguments){
        //$args = is_array($arguments) ? $arguments : array();
        switch(true){
            case preg_match('/^get_/', $name):
                $get = sprintf('get%s', ucfirst(substr($name, 4)));
                return method_exists($this, $get) ? $this->$get() : '';
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
        }
        return '';
    }
    /**
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value) {
        if( $this->has($name) && $name !== 'id' ){
            $this->_content[$name] = $value;
        }
    }
    /**
     * @param string $name
     * @return bool
     */
    public function has($name = ''){
        return strlen($name) && array_key_exists($name, $this->_content);
    }
    /**
     * @return array
     */
    protected function content(){
        return $this->_content;
    }
    
    /**
     * @param array $input
     */
    protected function populate( $input = [] ){
        foreach( $input as $field => $value ){
            if( isset($this->_content[$field]) && !is_null($value)){
                $this->_content[$field] = !is_null($value) ? $value : '';
            }
        }
    }
    /**
     * @return \CODERS\Clipboard\ClipData
     */
    protected static function db() {
        return new ClipData();
    }
    /**
     * @return \CODERS\Clipboard\Clip[]
     */
    protected function loaditems() {
        $this->db()->list($this->id);
        return Clipboard::instance()->list( $this->id );
    }
    /**
     * @return \CODERS\Clipboard\Clip[]
     */
    public function items(){
        return $this->_items;
    }
    /**
     * @return String[]
     */
    public function headers(){
        return array(
            'Content-Description: File Transfer',
            sprintf('Content-Type: %s',$this->type),
            sprintf('Content-Disposition: %s; filename=%s',$this->disposition(),$this->filename()),
            sprintf('Content-Length: %s',$this->size()),
        );
    }
    /**
     * @param bool $clipboard full clipboard view
     * @return string
     */
    public function url( $clipboard = false ) {
        return $clipboard ?
                Clipboard::clipboard($this->id) :
                Clipboard::clipdata($this->id);
    }
    /**
     * @return array
     */
    public function tags(){
        return explode(' ', $this->tags);
    }
    /**
     * @return Boolean
     */
    public function valid(){
        return strlen($this->id) > 0;
    }
    /**
     * @return Boolean
     */
    public function ready(){
        return $this->valid() && file_exists($this->path());
    }
    /**
     * @return Boolean
     */
    public function denied(){
        return !Clipboard::acl($this->acl);
    }
    /**
     * @return boolean
     */
    public function updated(){
        return $this->_updated;
    }    
    /**
     * @return Boolean
     */
    public function isImage( ){
        return stripos( $this->type, 'image/') === 0;
    }
    /**
     * @return Boolean
     */
    public function isMedia( ){
        return $this->isImage();
    }
    /**
     * @return Boolean
     */
    public function embed(){
        $inline_types = ['image/', 'text/', 'application/pdf'];
        foreach ($inline_types as $type) {
            if (stripos($this->type, $type) === 0) {
                return true;
            }
        }
        return false;
    }
    /**
     * @return String
     */
    public function filename(){
        $extension = explode('/', $this->type)[1] ?? 'txt';
        $filename = sprintf('%s.%s',basename($this->name),$extension);
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename );
    }
    /**
     * @return String
     */
    public function path(){
        return Clipboard::instance()->storage()->route($this->id);
    }
    /**
     * @return Int
     */
    public function size(){
        return $this->ready() ? filesize($this->path()) : 0;
    }
    /**
     * @return String
     */
    public function disposition(){
        return $this->embed() ? 'inline' : 'attachment';
    }
    /**
     * @return array
     */
    public function outline( $toplevel = '' ){
        if( $this->valid()){
            if(strlen($this->parent_id) && $this->parent_id !== $this->id && $this->id !== $toplevel){
                $parent = self::load($this->parent_id);
                $path = $parent->outline();
                $path[ $this->id ] = $this->title;
                return $path;
            }
            else{
                return array( $this->id => $this->title );
            }
        }
        return array();
    }
    /**
     * @return boolean
     */
    protected function tagmedia(){
        if($this->image()){
            $size = getimagesize($this->path());
            $aspect = count($size) > 1 ? $size[0] / $size[1] : 1;
            if( $aspect > 1.5 ){
                $this->tag('landscape');
            }
            elseif( $aspect < 0.75 ){
                $this->tag('portrait');
            }
            else{
                $this->tag('picture');
            }
            return true;
        }
        return false;
    }
    /**
     * @return array
     */
    public function meta(){
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'type' => $this->type,
            'parent_id' => $this->parent_id,
            'tags' => $this->listTags(),
            'link' => $this->getUrl(),
            'attach' => $this->disposition(),
        );        
    }
    
    /**
     * @return type
     */
    protected function getCss(){
        return implode(' ',array($this->type,$this->disposition()));
    }
    /**
     * @return string
     */
    protected function getClipboard() {
        return $this->url(true);
    }
    /**
     * @return string
     */
    protected function getUrl() {
        return $this->url();
    }
    /**
     * @return Int
     */
    public function countItems(){
        return count($this->items());
    }    

    
    
    /**
     * @global wpdb $wpdb
     * @param string $id
     * @return array
     */
    public static function list( $id = '' ){
        $db = new ClipData();
        $data = $db->list($id);
        $collection = array();
        foreach($data as $clip ){
            $collection[ $clip['id'] ] = new Clip($clip);
        }
        return $collection;
    }        
    /**
     * @param array $clipdata
     * @return \CODERS\Clipboard\Clip
     */
    public static function create( array $clipdata = array()) {
            $clip = new Clip($clipdata);
            $clip->tagmedia();
            return $clip->create() ? $clip : null;
    }
    /**
     * @param string $id
     * @param bool $preload
     * @return \CODERS\Clipboard\Clip
     */
    public static function load( $id = '' , $preload = false ){
        $db = new ClipData();
        $clipdata = $db->load($id);
        return count($clipdata) ? new Clip( $clipdata , $preload ) : null;
    }
}

/**
 * 
 */
class ClipData{
    /**
     * @var array
     */
    private $_log = array();
    /**
     * @return array
     */
    public function log(){
        return $this->_log;
    }
    /**
     * @param string $message
     * @param strint $type
     */
    protected function notify( $message = '' , $type = 'info'){
        if( $message && strlen($message)){
            $this->_log[] = array(
                'content' => $message,
                'type' => $type,
            );
        }
    }

    /**
     * @global \wpdb $wpdb
     * @return \wpdb
     */
    private static function wpdb(){
        global $wpdb;
        return $wpdb;
    }
    /**
     * @return string
     */
    protected static function table(){
        return self::wpdb()->prefix . 'clipboard_items';
    }   
    /**
     * 
     */
    public function recover(){
        $query = sprintf("UPDATE `%s` SET parent_id = NULL WHERE id = parent_id OR parent_id = ''", self::table());
        return $this->wpdb()->query($query) ?? 0;
    }
    /**
     * @return array
     */
    public function listids(){
        return $this->wpdb()->get_col(sprintf("SELECT `id` FROM `%s`", self::table()));
    }

    /**
     * @param string $id
     * @param int $index
     * @return int
     */
    public function sort( $id = '' , $index = 0 ){
        $wpdb = self::wpdb();
            $updated = $wpdb->update(
                    self::table(),
                    array('slot' => $index),    //update
                    array('id' => $id));        //where
            
            if( $updated !== false ){
                return $updated;
            }
            $this->notify($wpdb->last_error , 'error');
        return 0;
    }
    /**
     * @param String $id
     * @param int $slot
     * @param int $range
     * @return Int
     */
    public function arrange($id = '', $slot = -1 , $range = 0 ){
            $wpdb = self::wpdb();
            $table = self::table();
            // Set the variable
            $wpdb->query("SET @rownum = 0");
            $query = $wpdb->prepare(
                "UPDATE `$table`
                 SET `slot` = (@rownum := @rownum + 1) - 1
                 WHERE `parent_id` = %s",
                $id
            );
            if( $slot >= 0 ){
                $query .= sprintf(' AND `slot` >= %s',$slot);
            }
            if( $range ){
                $query .= sprintf(' AND `slot` <= %s',$range);
            }
            $query .= " ORDER BY `slot` ASC";
            $result = $wpdb->query($query);
            if(is_numeric($result)){
                return $result;
            }
            $this->notify( $wpdb->last_error , 'error');
            return 0;
    }    
    
    
    /**
     * @param string $id
     * @return int
     */
    public function count( $id = ''){
        $wpdb = self::wpdb();
        $table = self::table();
        $update = strlen($id) ?
                $wpdb->prepare("SELECT COUNT(*) AS `count` FROM `$table` WHERE `parent_id`='%s'", $id):
                "SELECT COUNT(*) AS `count` FROM `$table` WHERE `parent_id` IS NULL";
        $result = $wpdb->get_results(  $update , ARRAY_A );
        if( !is_null($result)){
            return count($result) ? intval( $result[0]['count'] ) : 0;
        }
        $this->notify($wpdb->error, 'error');
        return 0;
    }

    /**
     * @param array $id Parent Id
     * @return array
     */
    public function list( $id = '' ){
        $wpdb = $this->wpdb();
        $table = self::table();
        
        $list = $wpdb->get_results(strlen($id) ?
                    $wpdb->prepare("SELECT * FROM `$table` WHERE `parent_id`='%s' ORDER BY `slot`", $id):
                    "SELECT * FROM `$table` WHERE `parent_id` IS NULL ORDER BY `slot`"
                , ARRAY_A );
        
        if(!is_null($list)){
            return $list;
        }
        $this->notify($wpdb->error, 'error');
        return  array();
    }
    /**
     * @param string $id
     * @return array
     */
    public function load( $id = '' ){
        if(strlen($id)){
            $wpdb = $this->wpdb();
            $table = self::table();
            $content = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table` WHERE `id`='%s'",$id) , ARRAY_A);
            if( !is_null($content)){
                return $content;
            }
            $this->notify($wpdb->error, 'error');
        }
        return array();
    }
    /**
     * @param array $data clip data
     * @param array $where filters
     * @return bool
     */
    public function update( array $data = array() , array $where = array() ){
        $wpdb = self::wpdb();
        $result = $wpdb->update(self::table(), $data, $where );
        $error = $wpdb->error;
        if(strlen($error)){
            $this->notify($error,'error');
        }
        return $result !== false;
    }
    /**
     * @param array $data
     * @return bool
     */
    public function create( array $data = array()){
        $wpdb = $this->wpdb();
        $result = $wpdb->insert(self::table(), $data);

        if( $result !== false) {
            return true;
        }
        $this->notify($wpdb->error, 'error');
        return false;
    }
    
    /**
     * @param array $data
     * @return bool
     */
    public function delete( array $data = array()){
        $wpdb = $this->wpdb();
        $this->notify($wpdb->error, 'error');
        return false;
    }
    /**
     * @return bool
     */
    public function cleanup(){
        $wpdb = self::wpdb();
        // 1. Truncate DB table
        $result = $wpdb->query(sprintf('TRUNCATE TABLE `%s`', self::table()));
        return $result !== false;
    }


        /**
     * 
     */
    public function install(){
        
        $wpdb = self::wpdb();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS " . self::table() .
            " ( id VARCHAR(64) NOT NULL PRIMARY KEY,
            parent_id VARCHAR(64) DEFAULT NULL,
            name VARCHAR(32) NOT NULL,
            type VARCHAR(24) DEFAULT 'application/octet-stream',
            title VARCHAR(48) NOT NULL,
            description TEXT,
            layout VARCHAR(24) DEFAULT 'default',
            acl VARCHAR(16) DEFAULT 'private',
            slot INT DEFAULT '0',
            tags VARCHAR(24) DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset_collate;";
        dbDelta($sql);            
    }
}

/**
 * 
 */
class Storage{
    /**
     * @var string
     */
    private $_drive = 'content';
    
    /**
     * @param string $folder
     */
    public function __construct( $folder = 'content' ) {
        
        $this->_drive = $folder;
    }
    /**
     * @param string $id
     * @return string
     */
    public function route( $id = '' ){
        $route = array( $this->_drive );
        if(strlen($id)){
            $route[] =  $id;
        }
        return preg_replace('/\\\\/','/',self::root() . implode('/', $route));
    }
    /**
     * @param string $id
     * @return bool
     */
    public function exists( $id = '' ){
        return file_exists($this->route( $id ) );
    }
    /**
     * @return boolean
     */
    public function create( ){
        $route = $this->route();
        if( !file_exists($route)){
            return wp_mkdir_p($route);
        }
        return false;
    }

    /**
     * @return {String}
     */
    private static function root(){
        return sprintf('%s/clipboard/',wp_upload_dir()['basedir']);
    }    
}



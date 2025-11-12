<?php namespace CODERS\Clipboard;

defined('ABSPATH') or die;


class Clipboard{
    
    /**
     * 
     */
    protected function error404(){
            status_header(404);
            wp_die(__('Clipboard item not found.', 'coders_clipboard'));
            exit;        
    }
    /**
     * 
     */
    protected function errorDenied(){
        status_header(403);
        wp_die(__('Access denied', 'coders_clipboard'));
        exit;
    }
    
    
    /**
     * @param string $id
     */
    public static function attach( $id ){
        $clipboard = new Clipboard();
        $clip = Clip::load($id);

        switch(true){
            case is_null($clip):
                return $clipboard->error404();
            case !$clip->valid():
            case !$clip->ready():
                return $clipboard->error404();
            case !$clip->denied():
                return $clipboard->errorDenied();
        }
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $clip->type);
        header('Content-Disposition: ' . $clip->disposition() . '; filename=' . $clip->filename());
        header('Content-Length: ' . $clip->size());
        readfile($clip->path());
        exit;        
    }

    /**
     * @param string $role
     * @return boolean
     */
    public static function permission( $role = '' ){
        $roles = array(
            'public',
            apply_filters('coder_acl','')
        );
        return self::isAdmin() || in_array($role, $roles);
    }    
    
    
    /**
     * @return  boolean
     */
    protected static function isAdmin(){
        return current_user_can( 'administrator' );
    }

    /**
     * @param String $id
     * @return String
     */
    public static function clipboard( $id = ''){
        return get_site_url(null, sprintf('%s/%s', CODER_CLIPBOARD_APP,$id));
    }
    /**
     * @param String $id
     * @return String
     */
    public static function clip( $id = ''){
        return get_site_url(null, sprintf('%s/%s', CODER_CLIPBOARD_CLIP,$id));
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
        add_rewrite_rule(
                sprintf('^%s/([a-zA-Z0-9_-]+)/?$', 'index.php?clipboard_id=$matches[1]', CODER_CLIPBOARD_CLIP),
                'top');
        add_rewrite_rule(
                sprintf('^%s/([a-zA-Z0-9_-]+)/?$', 'index.php?clipboard_id=$matches[1]&mode=view', CODER_CLIPBOARD_APP),
                'top');

        if( $flush ){
            flush_rewrite_rules();
        }
    }
    /**
     * 
     */
    public static function setup(){
        ClipData::intstall();
        self::rewrite(true);
        //check the upload directory
        $uploads = Clipboard::drive();
        if( !file_exists($uploads)){
            wp_mkdir_p($uploads);
        }        
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
     * @var \CODERS\Clipboard\Clip
     */
    private $_items = array(
        //clip contents
    );

    
    /**
     * @param array $input
     */
    public function __construct( $input = array()) {
        $this->_content['created_at'] = date('Y-m-d H:i:s');
        $this->populate( $input );
    }
    /**
     * @return String
     */
    public function __get( $name ){
        return array_key_exists($name, $this->_content) ? $this->_content[$name] : '';
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
     * @return \CODERS\Clipboard\Clip
     */
    public function items(){
        return $this->_items;
    }
    /**
     * @param bool $clipboard full clipboard view
     * @return string
     */
    public function link( $clipboard = false ) {
        return $clipboard ? Clipboard::clipboard($this->id) : Clipboard::clip($this->id);
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
        return file_exists($this->path());
    }
    /**
     * @return Boolean
     */
    public function denied(){
        return !Clipboard::permission($this->acl);
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
    public function image( ){
        return stripos( $this->type, 'image/') === 0;
    }
    /**
     * @return Boolean
     */
    public function media( ){
        return $this->image();
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
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', sprintf('%s.%s',basename($this->name)),$extension);
    }
    /**
     * @return String
     */
    public function path(){
        return Clipboard::drive($this->id);
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
     * @return Int
     */
    public function count(){
        return count($this->items());
    }    
    /**
     * @return array
     */
    public function root( $toplevel = '' ){
        if( $this->valid()){
            if(strlen($this->parent_id) && $this->parent_id !== $this->id && $this->id !== $toplevel){
                $parent = self::load($this->parent_id);
                $path = $parent->root();
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
     * @return \CODERS\Clipboard\Clip
     */
    public static function load( $id = '' ){
        $db = new ClipData();
        $clipdata = $db->load($id);
        return count($clipdata) ? new Clip( $clipdata ) : null;
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
        if(strlen($message)){
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
    public static function count( $id = ''){
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
    public static function intstall(){
        
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

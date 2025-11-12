<?php namespace CODERS\Clipboard;

defined('ABSPATH') or die;


class Clipboard{
    
    
    
    /**
     * @param string $id
     * @return {String}
     */
    public static function path( $id = '' ){
        $path = sprintf('%s/clipboard/content/',wp_upload_dir()['basedir']);
        return strlen($id) ? $path . $id : $path;
    }    
    /**
     * @param bool $flush
     */
    public static function rewrite( $flush = false ){
        add_rewrite_tag('%clipboard_id%', '([a-zA-Z0-9_-]+)');
        add_rewrite_rule('^clipboard/([a-zA-Z0-9_-]+)/?$', 'index.php?clipboard_id=$matches[1]', 'top');
        add_rewrite_rule('^clipboards/([a-zA-Z0-9_-]+)/?$', 'index.php?clipboard_id=$matches[1]&mode=view', 'top');

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
        $uploads = Clipboard::path();
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
        'created_at' => '',
    );
    /**
     * @var bool
     */
    private $_updated = false;
    
    /**
     * @var \CODERS\Clipboard\Clip
     */
    private $_collection = array(
        //clip contents
    );

    
    /**
     * @param array $input
     */
    public function __construct( $input = array()) {
        $this->_content['created_at'] = self::timestamp();
        $this->populate( $input );
    }
    /**
     * @return String 
     */
    public static function timestamp(){
        return date('Y-m-d H:i:s');
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
    public function collection(){
        return $this->_collection;
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
     * @param array $data
     * @return bool
     */
    public function save( array $data = array()){
        $wpdb = $this->wpdb();
        $this->notify($wpdb->error, 'error');
        return false;
    }
    /**
     * @param array $data
     * @return bool
     */
    public function create( array $data = array()){
        $wpdb = $this->wpdb();
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

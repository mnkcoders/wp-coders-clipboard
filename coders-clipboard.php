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


/**
 * 
 */
class Clipboard {
    /**
     * @var array
     */
    static $_messages = array();    
    /**
     * @var \ClipboardContent
     */
    private $_content = null;

    /**
     * @type {String[]} Item cache
     */
    private $_collectionCache = [];
    
    /**
     * @param String $id 
     */
    protected function __construct( $id = '' ){
        
        if(strlen($id)){
            $this->_content = ClipboardContent::load($id);
        }
    }

    /**
     * @param String[] $items
     * @return Clipboard
     */
    protected function addItems( $items = array() ){
        foreach( $items as $id  => $atts ){
            $this->_collectionCache[$id] = $atts;
        }
        return $this;
    }
    /**
     * 
     * @return \ClipboardContent
     */
    protected function content(){
        return $this->_content;
    }
    /**
     * @return \Clipboard
     */
    protected function view(){
        
        $this->layout_default();

        return $this;        
    }
    /**
     * @return String
     */
    public function __get( $name ){
        return $this->hasContent() ? $this->content()->$name : '';
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
            case preg_match('/^has_/', $name):
                return $this->__run('has'.substr($name, 4), false, $arguments);
            case preg_match('/^count_/', $name):
                return $this->__run('count'.substr($name, 6), 0, $arguments);
            case preg_match('/^list_/', $name):
                return $this->__run('list'.substr($name, 5), array(), $arguments);
            case preg_match('/^get_/', $name):
                return $this->__run('get'.substr($name, 4), '', $arguments);
            case preg_match('/^view_/', $name):
                return $this->__view(substr($name, 5), is_admin() ? 'admin' : 'public') ;
            case preg_match('/^part_/', $name):
                return $this->__part(substr($name, 5), is_admin() ? 'admin' : 'public') ;
            case preg_match('/^layout_/', $name):
                return $this->__layout(substr($name, 7), is_admin() ? 'admin' : 'public') ;
            case preg_match('/^link_/', $name):
                return $this->__link(substr($name, 5), $arguments ) ;
            case preg_match('/^action_/', $name):
                return $this->__action(substr($name, 7), $arguments ) ;
        }
        return sprintf('<!-- INVALID INPUT %s -->', $name);
    }
    /**
     * @param string $action
     * @param array $request
     * @return string
     */
    protected function __action( $action , $request = array()){
        $call = 'action'.$action;
        if(method_exists($this,$call)){
            return $this->$call(...$request);
        }
        return $this->__link($action,$request);
    }

    /**
     * @param string $action
     * @param array $request
     * @return string
     */
    protected function __link( $action , $request = array()){
        $query = array();
        foreach ($request as $var => $val ){
            $query[] = sprintf('%s=%s',$var,$val);
        }
        $url = count($query) ? $action . '?' . implode('&', $query) : $action;
        return self::LINK($url);
    }    
    /**
     * @param string $call
     * @param mixed $default
     * @param array $arguments
     * @return mixed
     */
    protected function __run( $call , $default = false , $arguments = array()){
        return method_exists($this, $call) ? $this->$call( $arguments ) : $default;
    }
    /**
     * @return  STring Description
     */
    public function getLink(  ){
        return self::LINK( $this->id );
    }
    /**
     * @return string
     */
    public function getClipboard(){
        return self::CLIPBOARD( $this->id );
    }
    /**
     * @return  STring Description
     */
    public function getUrl( ){
        return self::LINK( $this->id );
    }
    /**
     * @return string
     */
    public function getId(){
        return $this->isValid() ? $this->content()->id : '';
    }
    /**
     * @return Boolean
     */
    public function isValid(){
        return $this->hasContent();
    }
    /**
     * @return Boolean
     */
    public function isMedia(){
        return $this->hasContent() && $this->content()->isMedia();
    }

    /**
     * @return boolean
     */
    public function isFullPage(){
        return true;
    }
    /**
     * @return Boolean
     */
    protected function hasContent(){
        return !is_null($this->_content);
    }
    /**
     * @return Boolean
     */
    protected function hasParent(){
        return $this->hasContent() && $this->content()->hasParent();
    }
    /**
     * @return Boolean
     */
    /*public function isImage( $args = array() ){
        return stripos(count($args) ? $args[0] : $this->type, 'image/') === 0;
    }*/
    /**
     * @return Boolean
     */
    private function isDenied(){
        return !self::isAdmin() && $this->acl !== 'public';
    }
    /**
     * @return array
     */
    public function listCollection(){
        return $this->hasContent() ? $this->content()->listItems() : $this->loadCollection();
    }
    /**
     * @return array
     */
    protected function loadCollection(){
        if( count($this->_collectionCache) === 0){
            $this->_collectionCache = ClipboardContent::list();
        }
        return $this->_collectionCache;
    }
    /**
     * @return array
     */
    public function listPath(){
        return $this->hasContent() ? $this->content()->listParents() : array();
    }
    /**
     * @return Int
     */
    public function countItems(){
        return count($this->listCollection());
    }



    /**
     * @param string $part
     * @param string $path
     * @return string
     */
    protected function __part( $part = '' , $path = 'public'){
        $view = $this->__view( 'parts/' . $part , $path );
        if(file_exists($view)){
            require $view;
            return true;
        }
        else{
            Clipboard::sendMessage(sprintf('Layout Part [%s] not found at %s',$part,$view),'error');
            require $this->__view('error',$path);
        }
        return false;
    }

    /**
     * @param String $layout
     * @param Boolean $path
     * @return Boolean
     */
    protected function __layout( $layout = 'default' , $path = 'public' ) {
        $view = $this->__view( 'layouts/' . $layout , $path );
        if(file_exists($view)){
            require $view;
            return true;
        }
        else{
            Clipboard::sendMessage(sprintf('Layout [%s] not found at %s',$layout,$view),'error');
            require $this->__view('error',$path);
        }
        return false;
    }
    /**
     * @param String $view
     * @param Boolean $path
     * @return String 
     */
    protected function __view( $view = 'default' , $path = 'public' ) {
        return self::assetPath(sprintf('html/%s/%s.php', $path , $view));
    }
    /**
     * 
     */
    public static function error404(){
            self::sendMessage(__('Clipboard not found.', 'coders_clipboard'),'error');
            status_header(404);
            var_dump(self::messages());
            //echo __('File not found.', 'coders_clipboard');
            //wp_die(__('Clipboard item not found.', 'coders_clipboard'));
            exit;        
    }
    /**
     * 
     */
    public static function errorDenied(){
        self::sendMessage(__('Access denied.', 'coders_clipboard'),'error');
        status_header(403);
        //echo __('Access denied.', 'coders_clipboard');
        exit;
    }
    /**
     * @param string $id
     */
    public static function attach( $id ){
        $clipboard = ClipboardContent::load($id);

        if (is_null($clipboard)) {
            return self::error404();
        }
        if ( !$clipboard->isValid()) {
            return self::error404();
        }
        if ( $clipboard->isDenied()) {
            return self::errorDenied();
        }
        if (!$clipboard->isAvailable()) {
            return self::error404();
        }
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $clipboard->type);
        header('Content-Disposition: ' . $clipboard->getDisposition() . '; filename=' . $clipboard->getFilename());
        header('Content-Length: ' . $clipboard->getSize());
        readfile($clipboard->getPath());
        exit;        
    }
    /**
     * @param string $id
     */
    public static function display( $id = '' ){
        $clipboard = new Clipboard($id);
        
        if( $clipboard->isFullPage() ){          
            wp_head();
        }
        //display the view layout
        $clipboard->__layout($clipboard->content()->layout ?? 'default' );
        
        if( $clipboard->isFullPage()){
            wp_footer();
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
     * @param String $url
     * @return String
     */
    public static function LINK( $url = ''){
        return get_site_url(null, 'clipboard/' . $url);
    }
    /**
     * @param String $url
     * @return String
     */
    public static function CLIPBOARD( $url = ''){
        return get_site_url(null, 'clipboards/' . $url);
    }
    /**
     * @return {String}
     */
    public static function table(){
        return $GLOBALS['wpdb']->prefix . 'clipboard_items';
    }
    /**
     * @param string $content
     * @return String
     */
    public static function assetPath( $content = '' ){
        $path = plugin_dir_path(__FILE__ );
        
        return strlen($content) ? $path.$content : $path;
        //return strlen($content) ? sprintf('%s/%s',$path,$content) : $path;
    }
    /**
     * @param string $content
     * @return String
     */
    public static function assetUrl( $content = '' ){
        $url = plugin_dir_url(__FILE__);
        
        return strlen($content) ? $url.$content : $url;
    }
    /**
     * @return  boolean
     */
    public static function isAdmin(){
        return current_user_can( 'administrator' );
    }
    
    
    /**
     * @param string $content
     * @param string $type
     */
    public static function sendMessage( $content , $type = 'info'){
        self::$_messages[] = array( 'content' => $content , 'type' => $type );
    }
    /**
     * @return array
     */
    public static function messages(){
        return self::$_messages;
    }    
    
    /**
     * @param string $role
     * @return boolean
     */
    public static function requestPermission( $role = '' ){

        $tier = apply_filters('coders_clipboard_tier','');
        
        return self::isAdmin() ||
                $role === 'public' ||
                $role === $tier;
    }    
}
    
/**
 * 
 */
class ClipboardContent{
    
    private $_updated = false;
    
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
     * @type ClipboardContent[] Item cache
     */
    private $_collection = [];
    
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
     * @param array $input
     * @return \ClipboardContent
     */
    public function override($input = array()) {
        foreach (['id', 'created_at'] as $key){
            unset($input[$key]);            
        }
        $this->populate($input);
        $this->_updated = count($input);
        return $this;
    }
    /**
     * @return String[]
     */
    public function listTags(){
        return explode(' ',$this->tags);
    }
    /**
     * @param string $tag
     * @return \ClipboardContent
     */
    public function tag( $tag = '' ){
        if( strlen($tag) && !in_array($tag, $this->listTags())){
            $tags = $this->tags . ' ' . $tag;
            if(strlen($tags) < 25 ){
                $this->tags = $tags;
                $this->_updated = true;
            }
        }
        return $this;
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
     * @param string $value
     */
    public function __set( $name , $value = ''){
        if( $this->isValid() && $this->has($name) && $name !== 'id'){
            if( $this->$name !== $value ){
                $this->_content[$name] = $value;
                $this->_updated = true;                
            }
        }
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
            case preg_match('/^has_/', $name):
                $has = 'has'.substr($name, 4);
                return method_exists($this, $has) ?
                        $this->$has( $arguments ) :
                        $this->has($has);
            case preg_match('/^get_/', $name):
                $get = 'get'.substr($name, 4);
                return method_exists($this, $get) ? $this->$get( $arguments ) : '';
            case preg_match('/^list_/', $name):
                $list = 'list'.substr($name, 5);
                return method_exists($this, $list) ? $this->$list( $arguments ) : array();
            case preg_match('/^count_/', $name):
                $count = 'count'.substr($name, 6);
                return method_exists($this, $count) ? $this->$count( $arguments ) : 0;
        }
        return '';
    }
    /**
     * @return array
     */
    public function content(){
        return  $this->_content;
    }
    /**
     * @return array
     */
    public function post(){
        $data = array(
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'type' => $this->type,
            'parent_id' => $this->parent_id,
            'tags' => $this->listTags(),
            'link' => $this->getUrl(),
        );
        
        if(is_admin()){
            $data['post'] = add_query_arg([
            'page' => 'coders_clipboard',
            'context_id' => $this->id,
                ], admin_url('admin.php'));
        }
        
        return $data;
    }
    /**
     * @param string $attribute
     * @return boolean
     */
    public function has( $attribute = '' ){
        return strlen($attribute) && array_key_exists($attribute, $this->content());
    }
    /**
     * @param boolean $refresh
     * @return ClipboardContent[]
     */
    protected function collection( $refresh = false ){
        if( $refresh ){
            $this->_collection = self::list($this->id);
        }
        return $this->_collection;
    }    
    
    /**
     * @return string
     */
    protected function defaultAcl() {
        return 'private';
    }

    /**
     * @global wpdb $wpdb
     * @return boolean
     */
    public function update() {
        global $wpdb;
        if ($this->isValid() && $this->isUpdated()) {
            $data = array(
                'name' => sanitize_file_name(trim($this->name)),
                'title' => sanitize_text_field(trim($this->title)),
                'description' => sanitize_textarea_field(trim($this->description)),
                'acl' => sanitize_text_field($this->acl),
                'layout' => sanitize_text_field($this->layout),
                'slot' => $this->slot,
            );

            $result = $wpdb->update(self::table(), $data, array('id' => $this->id));

            if ( $result !== false ) {
                $this->_updated = false;
                return true;
            }
            Clipboard::sendMessage('Clipboard update failed: ' . $wpdb->last_error , 'error');
        }
        return false;
    }
    /**
     * @global wpdb $wpdb
     * @return boolean
     */
    public function create() {
        if ($this->isValid()) {
            global $wpdb;

            $table = self::table();

            $name = mb_strimwidth($this->name,0,32);
            $content = array(
                'id' => $this->id,
                'name' => sanitize_file_name($name),
                'title' => $name,
                'description' => '',
                'type' => $this->type,
                'parent_id' => strlen($this->parent_id) ? $this->parent_id : null,
                'acl' => $this->defaultAcl(),
                'slot' => $this->slot,
                'tags' => $this->tags,
                'created_at' => current_time('mysql'),
            );
            $this->name = $name;
            $this->title = $name;
            
            // Insert into database
            $result = $wpdb->insert($table, $content);

            if ($result === false) {
                Clipboard::sendMessage($wpdb->last_error);
            }

            return $result !== false;
        }
        return false;
    }
    /**
     * @return boolean
     */
    public function moveup(){
        if( $this->hasParent()){
            $parent = self::load($this->parent_id);
            return $this->moveto($parent->parent_id);
        }
        return false;
    }
    /**
     * @global wpdb $wpdb
     * @return boolean
     */
    public function moveto( $parent_id = '') {
        global $wpdb;
        if ($this->isValid() && $this->parent_id !== $parent_id) {
            $count = self::count($parent_id);
            $data = array(
                'parent_id' => strlen($parent_id) ? $parent_id : null,
                'slot' => $count,
            );

            $result = $wpdb->update(self::table(), $data, array('id' => $this->id));
            if ( $result !== false ) {
                $this->_updated = false;
                $context = $this->parent_id;
                $this->parent_id = $parent_id;
                return self::arrange($context);
            }
            Clipboard::sendMessage('Clipboard update failed: ' . $wpdb->last_error , 'error');
        }
        return false;
    }
    /**
     * @global wpdb $wpdb
     * @return boolean
     */
    public function remove(){
        
        global $wpdb;
        
        $updated = $wpdb->update(self::table(),
                array('parent_id'=>$this->hasParent() ? $this->parent_id : null),
                array('parent_id' => $this->id));
        
        $deleted = $wpdb->delete( self::table(),array('id'=> $this->id) );
        
        if($deleted !== false){
            $path = $this->getPath();
            if(file_exists($path)){
                unlink($path);
            }
            return true;
        }
        Clipboard::sendMessage($wpdb->last_error);
        return false;
    }
    /**
     * @global wpdb $wpdb
     * @param int $index
     * @return int
     */
    public function sort( $index = 0 ){
        global $wpdb;
        if ($this->isValid()) {

            $result1 = $wpdb->update(
                    self::table(),
                    array('slot' => $index),
                    array('id' => $this->id));
            
            if ( $result1 !== false ) {
                $this->_updated = false;
                if( abs( $this->slot < $index) < 2 ){
                    return $result1;
                }
                //sort all surrounding items
                $from = $this->slot < $index ? $this->slot : $index;
                $to = $this->slot > $index ? $this->slot : $index;
                $arranged = self::arrange($this->parent_id , $from+1 , $to-1);
                return $result1 + $arranged;
            }
            Clipboard::sendMessage('Clipboard update failed: ' . $wpdb->last_error , 'error');
        }
        return 0;
    }
    /**
     * @global wpdb $wpdb
     * @param String $parent_id
     * @param int $slot
     * @param int $range
     * @return Int
     */
    public static function arrange($parent_id = '', $slot = -1 , $range = 0 ){
            global $wpdb;
            $table = self::table();
            // Set the variable
            $wpdb->query("SET @rownum = 0");
            $query = $wpdb->prepare(
                "UPDATE `$table`
                 SET `slot` = (@rownum := @rownum + 1) - 1
                 WHERE `parent_id` = %s",
                $parent_id
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
            Clipboard::sendMessage('Clipboard update failed: ' . $wpdb->last_error , 'error');
            return 0;
    }
    /**
     * @return boolean
     */
    public function tagImageSize(){
        if($this->isImage()){
            $size = getimagesize($this->getPath());
            $mode = count($size) > 1 && $size[0] > $size[1] ? 'landscape' : 'portrait';
            $this->tag( $mode );
            return true;
        }
        return false;
    }
    /**
     * @return string
     */
    public function getTags(){
        return $this->tags;
    }

    /**
     * @return  STring Description
     */
    public function getUrl( ){
        return Clipboard::LINK( $this->id );
    }
    /**
     * @return string
     */
    public function getClipboard(){
        return Clipboard::CLIPBOARD( $this->id );
    }    
    /**
     * @return int
     */
    public function getBefore(){
        return $this->slot > 0 ? $this->slot - 1 : 0;
    }
    /**
     * @return int
     */
    public function getAfter(){
        return $this->slot + 1;
    }
    /**
     * @return string
     */
    public function getCss(){
        $className = $this->listTags();
        $className[] = $this->isMedia() ? 'media' : 'attachment';
        return implode(' ' ,$className);
    }
    /**
     * @return Boolean
     */
    public function isValid(){
        return strlen($this->id) > 0;
    }
    /**
     * @return Boolean
     */
    public function isDenied(){
        return !Clipboard::requestPermission($this->acl);
    }
    /**
     * @return boolean
     */
    public function isUpdated(){
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
    public function getPath(){
        return Clipboard::path($this->id);
    }
    /**
     * @return Int
     */
    public function getSize(){
        return $this->isAvailable() ? filesize($this->getPath()) : 0;
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
    public function isAvailable(){
        return file_exists($this->getPath());
    }
    /**
     * @return Boolean
     */
    public function hasParent(){
        return strlen($this->parent_id) > 0;
    }
    /**
     * @return Boolean
     */
    public function hasItems(){
        return $this->countItems() > 0;
    }
    /**
     * @return Int
     */
    public function countItems(){
        return count($this->collection(true));
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
     * @param string $id
     * @return int
     */
    public static function copyLayouts( $id = ''){
        
        $clipboard = self::load($id);
        if( !is_null($clipboard)){
            $data = array(
                'parent_id' => $clipboard->id,
                'layout' => $clipboard->layout,
            );

            global $wpdb;
            $updated = $wpdb->update(self::table(), $data, array('id' => $id));
            if(!is_null($updated)){
                return $updated;
            }
            Clipboard::sendMessage($wpdb->last_error,'error');
        }
        return 0;
    }
    /**
     * @param string $id
     * @return int
     */
    public static function copyPermissions( $id = ''){
        $clipboard = self::load($id);
        if( !is_null($clipboard)){
            $data = array(
                'parent_id' => $clipboard->id,
                'acl' => $clipboard->acl,
            );

            global $wpdb;
            $updated = $wpdb->update(self::table(), $data, array('id' => $id));
            if(!is_null($updated)){
                return $updated;
            }
            Clipboard::sendMessage($wpdb->last_error,'error');
        }
        return 0;
    }
    /**
     * @param string $parent_id
     * @return int
     */
    public static function renameAll( $parent_id = ''){
        $count = 0;
        $content = self::load($parent_id);
        if( !is_null($content)){
            $name = $content->name;
            $title = $content->title;
            foreach( $content->listItems() as $item ){
                $item->name = sprintf('%s_%s',$name,$count+1);
                $item->title = $title;
                if($item->update()){
                    $count++;                    
                }
            }
        }
        return $count;
    }
    /**
     * @param string $parent_id
     * @return int
     */
    public static function fetchLost( $parent_id = ''){
        $count = 0;
        $folder = Clipboard::path();
        $files = scandir($folder);

        foreach ($files as $id) {
            if ($id === '.' || $id === '..') continue;

            $path = $folder . $id;
            $name = __('Found file','coders_clipboard');

            if (is_file($path)) {
                $mime_type = mime_content_type($path);

                $item = new ClipboardContent(array(
                    'id' => $id,
                    'parent_id' => $parent_id,
                    'type' => $mime_type,
                    'name' => $name,
                    'title' => $name,
                    'description' => '',
                    'created_at' => ClipboardContent::timestamp(),
                ));
                if( $item->create() ){
                    $count++;
                }
            }
        }        
        return $count;
    }

    /**
     * @global wpdb $wpdb
     * @param type $id
     * @return \ClipboardContent
     */
    public static function load( $id = '' ){
        global $wpdb;
        if(strlen($id)){
            $table = self::table();
            $content = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table` WHERE `id`='%s'",$id) , ARRAY_A);
            return !is_null($content) ? new ClipboardContent($content) : null;
        }
        return null;
    }
    /**
     * @global wpdb $wpdb
     * @param string $id
     * @return array
     */
    public static function list( $id = '' ){
        global $wpdb;
        $collection = array();
        $table = self::table();
        
        $list = $wpdb->get_results( strlen($id) ?
                    $wpdb->prepare("SELECT * FROM `$table` WHERE `parent_id`='%s' ORDER BY `slot`", $id):
                    "SELECT * FROM `$table` WHERE `parent_id` IS NULL ORDER BY `slot`"
                , ARRAY_A );
        
        if(!is_null($list)){
            foreach($list as $content ){
                $collection[$content['id']] = new ClipboardContent($content);
            }
        }
        return $collection;
    }    
    /**
     * @global wpdb $wpdb
     * @param string $id
     * @return int
     */
    public static function count( $id = ''){
        global $wpdb;
        $table = self::table();
        $update = strlen($id) ?
                $wpdb->prepare("SELECT COUNT(*) AS `count` FROM `$table` WHERE `parent_id`='%s'", $id):
                "SELECT COUNT(*) AS `count` FROM `$table` WHERE `parent_id` IS NULL";
        $result = $wpdb->get_results(  $update , ARRAY_A );
        return !is_null($result) && count($result) ? intval( $result[0]['count'] ) : 0;
    }

    /**
     * @global wpdb $wpdb
     */
    public static function install(){
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
            slot INT DEFAULT '0',
            tags VARCHAR(24) DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset_collate;";

        dbDelta($sql);        
    }
    /**
     * @global wpdb $wpdb
     * @return boolean
     */
    public static function resetContent() {
        global $wpdb;
        $done = 0;
        // 1. Truncate DB table
        $result = $wpdb->query(sprintf('TRUNCATE TABLE `%s`', self::table()));

        if ($result !== false) {
            $done++;
        }


        // 2. Delete files from uploads/clipboard
        $folder = Clipboard::path();

        if (file_exists($folder)) {
            $files = glob($folder . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $done++;
        }
        return $done > 1;
    }    
    /**
     * @return string
     */
    public static function table(){
        return $GLOBALS['wpdb']->prefix . 'clipboard_items';
    }
}





// Activation Hook
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    ClipboardContent::install();

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
        add_filter( 'body_class', function( $classes ) {
            return array_merge( $classes, array('coders-clipboard') );
        } );
        add_action( 'wp_enqueue_scripts' , function(){
            wp_enqueue_style(
                    'coders-clipboard-style',
                    Clipboard::assetUrl('html/public/style.css'));
        });
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



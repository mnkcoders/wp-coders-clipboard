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
     * @var array
     */
    private $_log = array();
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
     * @return array
     */
    public function log(){ return $this->_log; }
    /**
     * @return string
     */
    public function lastmessage() {
        $messages = $this->log();
        return count($messages) ? $messages[count($messages)-1]['content'] : '';
    }
    /**
     * @param string $message
     * @param strint $type
     */
    public function notify( $message = '' , $type = 'info'){
        if( $message && strlen($message)){
            $this->_log[] = array(
                'content' => $message,
                'type' => $type,
            );
        }
    }
    
    /**
     * @param string $drive
     * @return \CODERS\Clipboard\Storage
     */
    public function storage( $drive = ''){
        return new Storage( strlen($drive) ? $drive : 'content' );
    }
    /**
     * @return \CODERS\Clipboard\Data
     */
    public function db() {
        return new Data();
    }
    /**
     * @return \CODERS\Clipboard\CoderAcl
     */
    public function acl( ){
        return CoderAcl::role();
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
    public function list( $id = ''  ){
        $list = $this->db()->list($id);
        return array_map( function( $data ){
            return new Clip($data  );
        },$list);
    }

    /**
     * @param string $id
     */
    public static function request($id = '') {
        $provider = ContentProvider::create(Clip::load($id));
        if( !$provider->serve() ){
            wp_die(self::instance()->lastmessage());
        }
        exit;
    }
    
    /**
     * @param string $id
     */
    /*public static function attach( $id = ''){
        $clipboard = self::instance();
        $content = Clip::load($id);
        switch(true){
            case is_null($content):
                return $clipboard->error404();
            case !$content->isReady():
                return $clipboard->error404();
        }
        foreach ($content->headers() as $header) {
            header($header);
        }
        if( $content->isDenied()){
            if($content->isImage()){
                $content->buffer(true)->output();
            }
            else{
                print ';)';
            }
        }
        else{
            readfile($content->getPath());
        }
        exit;
    }*/
    /**
     * call to the public clipboard view namespace
     * @param string $clipboard
     * @return true
     */
    public static function board( $clipboard = array() ) {
        if(count($clipboard)){
                require_once sprintf('%s/lib/public.php', CODER_CLIPBOARD_DIR);
                do_action('coder_clipboard',$clipboard);
                return true;
        }
        return false;
    }
    
    /**
     * gets the clipboard display url, to render the full clip content gallery as defined
     * @param String $id
     * @return String
     */
    public static function clipboard( $id = '',$context = ''){
        return get_site_url(null, strlen($context) ?
                sprintf('%s/%s/%s', CODER_CLIPBOARD_VIEW,$id,$context) :
                sprintf('%s/%s', CODER_CLIPBOARD_VIEW,$id)
            );
    }
    /**
     * gets the clip content url to display (image/media/document ..
     * @param String $id
     * @return String
     */
    public static function clipdata( $id = ''){
        return get_site_url(null, sprintf('%s/%s', CODER_CLIPBOARD_DATA,$id));
    }
    /**
     * @param string $id
     * @return string
     */
    public  static function clipbuffer($id = '' ) {
        return get_site_url(null, sprintf('%s/%s', CODER_CLIPBOARD_BUFFER,$id));
    }
    /**
     * @param string $id
     * @param string $drive
     * @return string 
     */
    public static function route($id = '',$drive = 'content'){
        return self::instance()->storage($drive)->route($id);
    }
    
    /**
     * Create content redirection
     */
    private static function rewritecontent(){
        add_rewrite_tag('^clipboard/(.+)/?$', '(.+)');
        $content = sprintf('^%s/([a-zA-Z0-9_-]+)/?$', CODER_CLIPBOARD_DATA);
        add_rewrite_rule( $content , 'index.php?clip_id=$matches[1]' , 'top');
    }
    /**
     * create streaming buffer redirection
     */
    private static function rewritebuffer(){
        add_rewrite_tag('^clipbuffer/(.+)/?$', '(.+)');
        $buffer = sprintf('^%s/([a-zA-Z0-9_-]+)/?$', CODER_CLIPBOARD_BUFFER);
        add_rewrite_rule( $buffer , 'index.php?clip_id=$matches[1]' , 'top');
    }
    /**
     * create data redirection
     */
    private static function rewritedata(){
        //add_rewrite_tag('%clipboard_id%', '([a-zA-Z0-9_-]+)');
        add_rewrite_tag('%clip_id%', '([a-zA-Z0-9_-]+)');
        //$clipboard = sprintf('^%s/([a-zA-Z0-9_-]+)/?$', CODER_CLIPBOARD_VIEW);
        $clipboard = sprintf('^%s/(.+)/?$', CODER_CLIPBOARD_VIEW);
        add_rewrite_rule( $clipboard, 'index.php?clipboard_id=$matches[1]', 'top');
    }
    /**
     * @param bool $flush
     */
    public static function rewrite( $flush = false ){
        self::rewritecontent();
        self::rewritedata();
        //self::rewritebuffer();

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
        $cb->db()->install();
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
    private $_data = array(
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
        'drive' => 'content',
        'created_at' => '',
    );
    /**
     * @var array
     */
    private $_tree = array(
        //parent tree
    );
    
    /**
     * @var \CODERS\Clipboard\Clip[]
     */
    private $_items = array(
        //clip contents
    );
    /**
     * @var int
     */
    private $_count = 0;

    /**
     * @param array $input
     * @param bool $preload
     * @param string $toplevel
     */
    public function __construct( $input = array() , $preload = false , $toplevel = '' ) {
        $this->_data['created_at'] = date('Y-m-d H:i:s');
        $this->populate( $input );
        if($preload){
            $this->_items = $this->loaditems();
            $this->_tree = array_reverse( $this->loadtree($toplevel) );
            $this->_count = count($this->_items);
        }
        else{
            $this->_count = $this->db()->count($this->id);
        }
    }

    /**
     * @return string
     */
    public function __toString() {
        if(strlen($this->title)){
            return $this->title;
        }
        if(strlen($this->name)){
            return $this->name;
        }
        return $this->id;
    }
    /**
     * @param string $message
     * @param string $type
     * @return \CODERS\Clipboard\Clip
     */
    protected function notify($message = '' , $type = 'info') {
        Clipboard::instance()->notify($message, $type);
        return $this;
    }
    
    
    /**
     * @return array
     */
    protected function data( ){
        return $this->_data;
    }
    /**
     * @param string $name
     * @param string $value
     * @return boolean
     */
    protected function set( $name = '' , $value = ''){
        if( $this->has($name) && $name !== 'id' ){
            $this->_data[$name] = $value;
            return true;
        }
        return false;
    }
    /**
     * @return String
     */
    public function __get( $name ){
        $get = sprintf('get%s', ucfirst($name));
        if(method_exists($this, $get)){
            return $this->$get();
        }
        return $this->has($name) ? $this->data()[$name] : '';
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
     * @return bool
     */
    public function has($name = ''){
        return strlen($name) && array_key_exists($name, $this->data());
    }
    
    /**
     * @param array $input
     * @return \CODERS\Clipboard\Clip
     */
    protected function populate( $input = [] ){
        foreach( $input as $field => $value ){
            if( isset($this->_data[$field]) && !is_null($value)){
                $this->_data[$field] = !is_null($value) ? $value : '';
            }
        }
        return $this;
    }
    /**
     * @return \CODERS\Clipboard\Data
     */
    protected static function db() {
        return new Data();
    }
    /**
     * @return \CODERS\Clipboard\Clip[]
     */
    protected function loaditems() {
        $this->db()->list($this->id);
        return Clipboard::instance()->list( $this->id );
    }
    /**
     * @param String $toplevel
     * @return array
     */
    protected function loadtree( $toplevel = '' ) {
        $list = array( $this->id => $this->getTitleorName() );
        if($this->id === $toplevel){
            return $list;
        }
        $parent = self::load($this->parent_id);
        return $parent ? array_merge( $list, $parent->loadtree($toplevel)) : $list;
    }
    /**
     * @return String[]
     */
    /*public function headers(){
        return array(
            'Content-Description: File Transfer',
            sprintf('Content-Type: %s',$this->type),
            sprintf('Content-Disposition: %s; filename=%s',
                    $this->getDisposition(),
                    $this->getFilename(true)),
            sprintf('Content-Length: %s',$this->size()),
        );
    }*/
    
    /**
     * @return array
     */
    public function listTags(){
        return explode(' ', trim($this->tags));
    }
    /**
     * @param bool $filter
     * @return \CODERS\Clipboard\Clip[]
     */
    public function listItems($filter = false ){
        return $filter ? array_filter( $this->_items , function($item){
            !$item->isDenied();
        }) : $this->_items;
        //return $this->_items;
    }
    /**
     * @return array
     */
    public function listPath() {
        return $this->_tree;
    }
    /**
     * @return bool
     */
    public function hasItems(){
        return $this->countItems() > 0;
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
    public function isReady(){
        return $this->isValid() && file_exists($this->getPath());
    }
    /**
     * @return Boolean
     */
    public function isDenied(){
        return !CoderAcl::role()->can($this->acl);
        //return !Clipboard::acl($this->acl);
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
    public function canEmbed(){
        $inline_types = ['image/', 'text/', 'application/pdf'];
        foreach ($inline_types as $type) {
            if (stripos($this->type, $type) === 0) {
                return true;
            }
        }
        return false;
    }
    /**
     * @return string
     */
    public function getExtension(){
        return explode('/', $this->type)[1] ?? 'txt';
    }
    /**
     * @return String
     */
    public function getFilename($ext = false){
        $name = explode('.',basename($this->name))[0];
        return $ext ? sprintf('%s.%s',$name,$this->getExtension()) : $name;
        //$extension = explode('/', $this->type)[1] ?? 'txt';
        //$filename = sprintf('%s.%s',basename($this->name),$extension);
        //return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename );
    }
    /**
     * @return string
     */
    public function getTitleorName(){
        return strlen($this->title) ? $this->title : $this->name;
    }
    /**
     * @return String
     */
    public function getPath(){
        return Clipboard::instance()->storage()->route($this->id);
    }
    /**
     * @return Int
     */
    public function size(){
        return $this->isReady() ? filesize($this->getPath()) : 0;
    }
    /**
     * @return String
     */
    public function getDisposition(){
        return $this->canEmbed() ? 'inline' : 'attachment';
    }
    
    /**
     * @return string
     */
    public function getCss(){
        $meta = explode('/',$this->type);
        $meta[] = $this->getDisposition();
        $meta[] = $this->tags;
        return implode(' ',$meta);
    }
    /**
     * @return string
     */
    public function getContentType(): string {
        $type = strtolower((string) $this->type);

        switch (true) {
            case strpos($type, 'image/') === 0:
                return 'image';

            case strpos($type, 'video/') === 0:
                return 'video';

            case strpos($type, 'audio/') === 0:
                return 'audio';

            case strpos($type, 'text/') === 0:
                return 'text';

            case $type === 'application/pdf':
                return 'document';

            default:
                return 'binary';
        }
    }
    /**
     * @return string
     */
    public function getClipboard() { return self::clipboard($this->id); }
    /**
     * @return string
     */
    public function getUrl() { return self::clipdata($this->id); }
    /**
     * @return Int
     */
    public function countItems(){
        return $this->_count;
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
            //'link' => $this->getUrl(),
            'attach' => $this->getDisposition(),
            'slot' => intval($this->slot),
        );        
    }

    /**
     * @global wpdb $wpdb
     * @param string $id
     * @return array
     */
    public static function list( $id = '' ){
        $db = new Data();
        return array_map( function($data){
            return new Clip($data);
        },$db->list($id));
    }        
    /**
     * @param string $id
     * @param bool $preload
     * @param string $top
     * @return \CODERS\Clipboard\Clip
     */
    public static function load( $id = '' , $preload = false , $top = ''){
        $db = new Data();
        $clipdata = $db->load($id);
        return count($clipdata) ? new Clip( $clipdata , $preload ,$top) : null;
    }
    /**
     * @param string $id
     * @param string $context
     * @return string
     */
    public static function clipboard($id='' , $context = '') {
        return get_site_url(null, strlen($context) ?
                sprintf('%s/%s/%s', CODER_CLIPBOARD_VIEW,$id,$context) :
                sprintf('%s/%s', CODER_CLIPBOARD_VIEW,$id)
            );
    }
    /**
     * @param string $id
     * @return string
     */
    public static function clipdata($id = '') {
        return get_site_url(null, sprintf('%s/%s', CODER_CLIPBOARD_DATA,$id));
    }
}
/**
 * 
 */
class ContentProvider{
    /**
     * 
     * @var \CODERS\Clipboard\Clip
     */
    private $_content = null;
    /**
     * @param \CODERS\Clipboard\Clip $content
     */
    protected function __construct( Clip $content = null ) {
        $this->_content = $content;
    }
    /**
     * 
     * @return \CODERS\Clipboard\Clipboard
     */
    protected function clipboard() {
        return Clipboard::instance();
    }
    /**
     * @param string $message
     * @return \CODERS\Clipboard\Clipboard
     */
    protected function error($message = '' ) {
        $this->clipboard()->notify($message, 'error');
        return $this;
    }
    /**
     * 
     */
    protected function error404(){
        status_header(404);
        $this->error(__('Clipboard item not found.', 'coder_clipboard'));
    }
    /**
     * 
     */
    protected function errorDenied(){
        status_header(403);
        $this->error(__('Access denied', 'coder_clipboard'));
    }
    
    /**
     * @return \CODERS\Clipboard\Clip
     */
    protected function content() {
        return $this->_content;
    }
    /**
     * @return bool
     */
    protected function ready() {
        return !is_null($this->content()) && $this->content()->isReady(); 
    }
    /**
     * @return bool
     */
    protected function denied() {
        return $this->content() ? $this->content()->isDenied() : true;
    }
    /**
     * @return int
     */
    protected function size() {
        $content = $this->content();
        return  $content ? filesize($content->getPath()) : 0;
    }
    /**
     * @return string
     */
    protected function filename() {
        $content = $this->content();
        return $content ? $content->getFilename(true) : '';
    }
    /**
     * @return string
     */
    protected function disposition() {
        $content = $this->content();
        return $content ? $content->getDisposition() : '';
    }
    /**
     * @return string
     */
    protected function type() {
        $content = $this->content();
        return $content ? $content->type : '';
    }
    /**
     * @return string
     */
    protected function path() {
        $content = $this->content();
        return $content ? $content->getPath() : '';
    }
    /**
     * @return bool
     */
    protected function output() {
        $path = $this->path();
        return $path ? readfile($path) : false;
    }
    /**
     * @return array
     */
    protected function headers() {
        return array(
            'Content-Description: File Transfer',
            sprintf('Content-Type: %s',$this->type()),
            sprintf('Content-Disposition: %s; filename=%s',
                    $this->disposition(),
                    $this->filename()),
            sprintf('Content-Length: %s',$this->size()),
        );
    }
   /**
     * @return \CODERS\Clipboard\ContentProvider
     */
    protected function heading() {
        foreach ($this->headers() as $header) {
            header($header);
        }
        return $this;
    }
    /**
     * @return bool
     */
    public function serve() {
        if( !$this->ready()){
            $this->error404();
            return false;
        }
        return $this->heading()->output();
    }
   
    /**
     * @param \CODERS\Clipboard\Clip $content
     * @return \CODERS\Clipboard\ContentProvider
     */
    public static function create( Clip $content = null ){
        switch($content->getContentType()){
            case 'video': return new StreamProvider($content);
            case 'image': return new ImageProvider($content, 20);
            //define providers as required
            default: return new ContentProvider($content);
        }
    }
}
/**
 * 
 */
class ImageProvider extends ContentProvider{
    /**
     * @var \GdImage[]
     */
    private $_buffer = array();
    /**
     * @var int
     */
    private $_size = 10;

    /**
     * @param \CODERS\Clipboard\Clip $content
     * @param int $size
     */
    protected function __construct( Clip $content = null , $size = 10 ) {
        parent::__construct($content);
        $this->_size = $size;
    }

    /**
     * @param \GdImage $buffer
     * @return \CODERS\Clipboard\ImageProvider
     */
    private function setbuffer($buffer) {
        //if(get_class($buffer) === '\GdImage'){
             $this->_buffer[] = $buffer;
        //}
        return $this;
    }
    /**
     * @return \GdImage
     */
    private function buffer(){
        return count($this->_buffer) ? $this->_buffer[count($this->_buffer)-1] : null;
    }
    /**
     * @return \CODERS\Clipboard\ImageProvider
     */
    private function createbuffer( ) {
        $type = $this->type();
        $hidden = $this->denied();
        $path = $this->path();
        // Load image based on type
        switch ($type) {
            case 'image/jpeg':
                $this->setbuffer(  imagecreatefromjpeg($path) );
                break;
            case 'image/png':
                $this->setbuffer( imagecreatefrompng($path) );
                break;
            case 'image/gif':
                $this->setbuffer( imagecreatefromgif($path ) );
                break;
        }
        $buffer = $this->buffer();
        if( $buffer && $hidden ){
            //$this->reduce( $buffer );
            $this->setbuffer( $this->reduce( $buffer , $this->_size ) );
        }
        return $this;
    }
    /**
     * 
     * @param \GdImage $buffer
     * @param int $size
     * @return bool
     */
    private function reduce($buffer , $size = 10) {
             // Original dimensions
            $w = imagesx($buffer);
            $h = imagesy($buffer);
            // Reduce size
            //$small_w = max($size, intval($w * $size * 0.01));
            //$small_h = max($size, intval($h * $size * 0.01));
            $small_w = $size;
            $small_h = $size * $w / $h;

            // Downscale → Blur effect
            $small = imagecreatetruecolor($small_w, $small_h);
            imagecopyresampled($small, $buffer, 0, 0, 0, 0, $small_w, $small_h, $w, $h);

            $this->blur($small,$size);

            // Upscale back to original size
            $output = imagecreatetruecolor($w, $h);
            imagecopyresampled($output, $small, 0, 0, 0, 0, $w, $h, $small_w, $small_h);
            
            $this->setbuffer( $buffer );
            return $output;
    }
    /**
     * @param \GdImage $buffer
     * @param int $loops
     * @return \GdImage
     */
    private function blur($buffer , $loops = 1){
        for($i = 0 ; $i < $loops ; $i++ ){
            imagefilter($buffer, IMG_FILTER_GAUSSIAN_BLUR , 999 );
            //imagefilter($output, IMG_FILTER_SELECTIVE_BLUR, 999 );
        }
        return $buffer;
    }
    /**
     * @return bool
     */
    private function readbuffer() {
        $buffer = $this->buffer();
        if( $buffer ){
            switch($this->type()){
                case 'image/png':
                    imagepng($buffer);
                case 'image/gif':
                    imagegif($buffer);
                case 'image/jpeg':
                    imagejpeg($buffer, null,90);
            }
            // Cleanup
            $this->clear();
            return true;
        }
        return false;
    }
    /**
     * 
     */
    private function clear(){
            foreach($this->_buffer as $b ){
                imagedestroy($b);
            }
            $this->_buffer = array();
    }
    /**
     * @return String[]
     */
    protected function headers() {
        $headers = parent::headers();
        //append more headers here for image display
        return $headers;
    }
    /**
     * @return bool
     */
    protected function output(){
        return $this->denied() ? $this->createbuffer()->readbuffer() : parent::output();
    }
}
/**
 * 
 */
class StreamProvider extends ContentProvider{
    /**
     * @param \CODERS\Clipboard\Clip $content
     */
    protected function __construct( Clip $content = null) {
        parent::__construct($content);
    }
    
    private function buffer() {
        
    }
}


/**
 * ACL Role interaction with Coder Tiers Plugin
 */
class CoderAcl{
    /**
     * @var string
     */
    private $_role = '';
    /**
     * @param string $role
     */
    private function __construct( $role = '') {
        $this->_role = $role;
    }
    /**
     * @return string
     */
    public function __toString() {
        return $this->role();
    }
    /**
     * Match is admin user
     * @return bool
     */
    private function isadmin(){
        return current_user_can( 'administrator' );
    }
    /**
     * Call for coder_acl filter from Coder Tiers Plugin
     * @param string $tier
     * @return bool
     */
    private function acl($tier = ''){
        return apply_filters('coder_acl',$tier) ? true : false;
    }
    /**
     * Match loaded role or public access
     * @param string $tier
     * @return bool
     */
    private function istier($tier = ''){
        return in_array($tier, array( 'public', $this->_role, ));
    }
    /**
     * @param string $tier
     * @return boolean
     */
    public function can( $tier = '' ){
        return $this->isadmin() || $this->istier($tier) || $this->acl($tier);
    }
    /**
     * @return array
     */
    public function list(){
        return apply_filters('coder_tiers',array());
    }
    /**
     * @return \CODERS\Clipboard\CoderAcl
     */
    public static function role( )
    {
        $role = apply_filters('coder_role', '');
        return new CoderAcl($role);
    }
}

/**
 * 
 */
class Data{
    /**
     * @param string $message
     * @param string $type
     * @return \CODERS\Clipboard\Data
     */
    private function notify($message = '' , $type = 'info') {
        Clipboard::instance()->notify($message, $type);
        return $this;
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
        return self::wpdb()->prefix . 'coder_clipboard';
    }   
    /**
     * 
     */
    public function recover(){
        $query[] = sprintf("UPDATE `%s` SET parent_id = NULL", self::table());
        $query[] = "WHERE `id`=`parent_id` OR `parent_id`=''";
        return $this->wpdb()->query(implode(' ', $query)) ?? 0;
    }
    /**
     * @return array
     */
    public function allids(){
        return $this->wpdb()->get_col(sprintf("SELECT `id` FROM `%s`", self::table()));
    }

    /**
     * @param string $id
     * @param int $slot
     * @return int
     */
    public function sort( $id = '' , $slot = 0 ){
        $wpdb = self::wpdb();
            $updated = $wpdb->update(
                    self::table(),
                    array('slot' => $slot),    //update
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
    public function arrange($id = '', $slot = 0 , $range = 0 ){
            $wpdb = self::wpdb();
            $table = self::table();
            // Set the variable
            $wpdb->query("SET @rownum = 0");
            $query = array( "UPDATE `$table` SET `slot` = (@rownum := @rownum + 1)" );
            if( $id ){
                $query[] = sprintf("WHERE `parent_id` = '%s'",$id);
            }
            else{
                $query[] = "WHERE `parent_id` IS NULL OR `parent_id` = ''";
            }
            if( $slot > 0 ){
                $query[] = sprintf('AND `slot` >= %s',$slot);
            }
            if( $range ){
                $query[] = sprintf('AND `slot` <= %s',$range);
            }
            //$query[] = "ORDER BY `slot` ASC";
            $count = $wpdb->query(implode(' ', $query));
            if(is_numeric($count)){
                return $count;
            }
            $this->notify( $wpdb->last_error , 'error');
            return 0;
    }    
    
    
    /**
     * @param string $id
     * @return int
     */
    public function count( $id = ''){
        $db = self::wpdb();
        $query = array(sprintf("SELECT COUNT(*) AS `count` FROM `%s`",self::table()));
        if( $id === true ){
            //allow fetch all items in the table
        }
        elseif(is_string($id) && strlen($id)){
            $query[] = sprintf("WHERE `parent_id`='%s'",$id);
        }
        else{
            $query[] = "WHERE `parent_id` IS NULL OR `parent_id`=''";
        }
        $result = $db->get_results( implode(' ', $query), ARRAY_A );
        if( !is_null($result)){
            return count($result) ? intval( $result[0]['count'] ) : 0;
        }
        $this->notify($db->error, 'error');
        return 0;
    }
    /**
     * @param string $id
     * @param array $columns //leave empty to get them all
     * @param string $drive 
     * @return array
     */
    public function list( $id = '' , array $columns = array() ){
        $wpdb = $this->wpdb();
        $table = self::table();
        
        $scope = count($columns) ? sprintf('`%s`',implode('`,`', $columns)) : '*';
        
        $sql = array("SELECT $scope FROM `$table`");
        if(strlen($id)){
            $sql[] = "WHERE `parent_id`='$id'" ;
        }
        else{
            $sql[] = "WHERE (`parent_id` IS NULL OR `parent_id`='')";
        }
        $sql[] = "ORDER BY `slot`;";
        $list = $wpdb->get_results( implode(' ', $sql) , ARRAY_A );
        
        if(!is_null($list)){
            return $list;
        }
        $this->notify($wpdb->error, 'error');
        return  array();        
    }
    /**
     * @param array $id Parent Id
     * @return array
     */
    public function listold( $id = '' ){
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
     * @param array $ids
     * @param string $toid
     * @return int
     */
    public function movecollection( $fromid = '' , $toid = ''){
        if( $fromid && $fromid !== $toid ){
            $db = self::wpdb();
            $updated = $db->update(
                    self::table(),
                    array('parent_id'=> $toid),
                    array('parent_id' => $fromid) );
            $this->notify($db->error,'error');
            return $updated !== false ? $updated : 0;
        }
        return 0;
    }

    /**
     * @param array $data clip data
     * @param array $where filters
     * @return bool
     */
    public function update( array $data = array() , array $where = array() ){
        if( isset($data['id'])){
            unset($data['id']);
        }
        $db = self::wpdb();
        $result = $db->update(self::table(), $data, $where );
        $this->notify($db->error,'error');
        return $result !== false ? $result : 0;
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
        $db = self::wpdb();
        $deleted = $db->delete(self::table(), $data);
        $this->notify($db->error, 'error');
        return false !== $deleted;
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
     * @return string
     */
    public function drive(){
        return $this->_drive;
    }

    /**
     * @param string $name
     * @return string
     */
    public function makeid($name = '') {
        $seed = $this->_drive . $name . microtime(true) . rand();
        return substr(md5($seed), 0, 16); // Shorten if you want fixed-length IDs
    }
    /**
     * Remove physically a file from the uploads folder
     * @param string $id
     * @return bool
     */
    public function remove($id = '') {
        if( $id ){
            $path = $this->route($id);
            return unlink($path);
        }
        return false;
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
     * @return array
     */
    public function drives(){
        $items = scandir(self::root());
        return array_filter( $items , function($item){
            return $item !== '.' && $item !== '..';
        });
    }
    /**
     * @return array
     */
    public function list(){
        return array_filter( scandir($this->route()), function($file){
            return $file !== '.' && $file !== '..';
        });
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
     * Whipe out all files in the selected storage
     * @return int
     */
    public function clear() {
        $storage = $this->route();
        $count = 0;
        if (file_exists($storage)) {
            $files = glob($storage . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (unlink($file)) {
                        $count++;
                    }
                }
            }
            return true;
        }
        return $count;
    }

    /**
     * @return {String}
     */
    private static function root(){
        return sprintf('%s/clipboard/',wp_upload_dir()['basedir']);
    }    
}


class Strings{
    
}
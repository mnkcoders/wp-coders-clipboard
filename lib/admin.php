<?php namespace CODERS\Clipboard\Admin;

defined('ABSPATH') or die;

add_action('admin_post_coder_clipboard', function() {
    $server = \CODERS\Clipboard\Admin\Controller::redirect('post',INPUT_POST);

    $id = $server->response()['id'] ?? '';
    $redirect = \CODERS\Clipboard\Admin\View::adminurl(strlen($id) ? ['id'=>$id] : []);
    wp_redirect($redirect);
    exit;        
});

add_action('wp_ajax_coder_clipboard', function() {
    $server = \CODERS\Clipboard\Admin\Controller::redirect('ajax', INPUT_POST);
    wp_send_json_success($server->response());
    exit;
});
add_action('wp_ajax_nopriv_coder_clipboard', function() {
    $server = \CODERS\Clipboard\Admin\Controller::redirect('ajax', INPUT_POST);
    wp_send_json_success($server->response());
    exit;
});


add_action('admin_enqueue_scripts', function( ) {
    \CODERS\Clipboard\Admin\View::preload(filter_input(INPUT_GET, 'page') ?? '');
});

add_action('admin_menu', function () {

    add_menu_page(
            __('Clipboard', 'coder_clipboard'),
            __('Clipboard', 'coder_clipboard'),
            'upload_files', // or 'manage_options' if more restricted
            'coder_clipboard',
            function () {
                $context = filter_input(INPUT_GET, 'controller') ?? 'main';
                \CODERS\Clipboard\Admin\Controller::redirect($context);
            }, 'dashicons-art',40);
    add_submenu_page(
            'coder_clipboard',
            __('Settings', 'coder_clipboard'),
            __('Settings', 'coder_clipboard'),
            'manage_options',
            'coder_clipboard_settings',
            function () {
                \CODERS\Clipboard\Admin\Controller::redirect('settings'); }
            );
});

/**
 * 
 */
class Controller{

    const INPUT_REQUEST = 3;
    const INPUT_GET = INPUT_GET;
    const INPUT_POST = INPUT_POST;
    /**
     * @var bool
     */
    private $_completed = false;
    /**
     * @var array
     */
    private $_response = array(
        //fill in all required meta
    );
    /**
     * @var array
     */
    private $_input = array( );
    
    /**
     * @param array $input
     */
    protected function __construct( array  $input = array()) {
        $this->_input = $input;
    }
    /**
     * @param string $name
     * @return string
     */
    public function __get($name) {
        return $this->_input[$name] ?? '';
    }
    /**
     * @param string $name
     * @param string $value
     */
    public function __set($name,$value ){
        if(array_key_exists($name, $this->_input)){
            $this->_input[$name] = $value ?? '';
        }
    }
    /**
     * @param string $name
     * @return int
     */
    protected function getInt($name) {
        return array_key_exists($name, $this->data()) ? (int)$this->data()[$name] : 0;
    }
    /**
     * @param string $name
     * @return float
     */
    protected function getFloat($name) {
        return array_key_exists($name, $this->data()) ? (float)$this->data()[$name] : 0.0;
    }
    /**
     * @param string $name
     * @param string $split
     * @return array
     */
    protected function getList($name,$split = ' ') {
        return array_key_exists($name, $this->data()) ?
                explode($split,$this->data()[$name]) :
                array();
    }
    /**
     * @return bool
     */
    private function completed(){
        return $this->_completed;
    }
    /**
     * @return array
     */
    protected function data(){
        return $this->_input;
    }

    /**
     * @return bool
     */
    protected function canupload(){
        return  current_user_can('upload_files');
    }
    /**
     * @param string $key
     * @param mixed $value
     * @return \CODERS\Clipboard\Admin\Controller
     */
    protected function put($key = '' , $value = false ) {
        if(strlen($key)){
            $this->_response[$key] =  $value;
        }
        return $this;
    }
    /**
     * @param array $input
     * @return \CODERS\Clipboard\Admin\Controller
     */
    protected function fill( array $input = array()) {
        foreach($input as $var => $val ){
            $this->_response[$var] = $val;
        }
        return $this;
    }
    /**
     * @return array
     */
    static public function log(){
        return Content::manager()->log();
    }
    /**
     * @param string $message
     * @param string $type
     */
    static public function notify( $message = '' , $type = 'update'){
        Content::manager()->notify($message, $type);
    }
    /**
     * @return string
     */
    protected function context(){
        $class = explode('\\',get_called_class());
        return $class[count($class)-1];
    }
    /**
     * @return string
     */
    protected function action(){
        return $this->_input['action'] ?? 'main';
    }
    /**
     * @return \CODERS\Clipboard\Admin\Controller
     */
    public function run(){
        $action = $this->action();
        $call = sprintf('%sAction',$action);
        
        $this->_completed = method_exists($this, $call) ?
                $this->$call( ) :
                $this->error($action);

        return $this->put('_log',$this->log())
                ->put('_context', $this->context())
                ->put('_response', $this->completed())
                ->put('_input',$this->data())
                ->put('_action',$action);
    }
    /**
     * action[] , log[] , ...
     * @return array
     */
    public function response() {
        return $this->_response;
    }
    /**
     * @return \CODERS\Clipboard\Admin\View
     */
    protected function view( ){
        return View::create($this->action());
    }

    /**
     * @param string $action
     * @return bool
     */
    protected function error( $action = '' ){
        $this->notify(sprintf('Invalid action [%s]',$action),'error');
        $this->put('error', $this->log() );
        return false;
    }
    /**
     * @return bool
     */
    protected function mainAction( ) : bool{
        return true;
    }
    
    
    /**
     * @param int $type
     * @return array
     */
    protected static function input( $type = self::INPUT_REQUEST , $maskaction = false ) {
        switch($type){
            case self::INPUT_REQUEST:
                return array_merge(
                        self::input(self::INPUT_GET),
                        self::input(self::INPUT_POST)
                );
            case self::INPUT_GET:
            case self::INPUT_POST:
                $input = filter_input_array($type) ?? array();
                if($maskaction){
                    $input['action'] = $input['task'] ?? 'main';
                    unset($input['task']);
                }
                return $input;
            default:
                return array();
        }
    }
    /**
     * @param string $context
     * @param array $input
     * @return \CODERS\Clipboard\Admin\Controller
     */
    protected static final function create( $context = '' , array $input = array()){
        $class = sprintf('\CODERS\Clipboard\Admin\%sController', ucfirst($context));
        return class_exists($class) && is_subclass_of($class, self::class ,true ) ?
            new $class($input) :
            new Controller($input);
    } 
    /**
     * @param string $context
     * @return \CODERS\Clipboard\Admin\Controller
     */
    public static function redirect($context = 'main' , $type = INPUT_GET) {
        $controller = self::create($context,self::input($type, $type === INPUT_POST));
        return $controller ? $controller->run() : null;
    }
}
/**
 * 
 */
class MainController extends Controller{

    /**
     * @return \CODERS\Clipboard\Admin\View
     */
    protected function view( ){
        return new MainView($this->action());
    }
    
    
    /**
     * @return bool
     */
    protected function mainAction(): bool {
        $clip = Content::load( $this->id ,true);
        $this->view()
                ->setContent( $clip )
                ->show('clipboard');

        return true;
    }
    /**
     * @return bool
     */
    protected function removeAction() : bool{
        $clip = Content::load($this->id);
        if( $clip){
            $parent = $clip->parent_id;
            if( $clip->remove() ){
                $this->notify(sprintf('<b>%s</b> removed',$clip->name));                
                $this->id = strlen($parent) ? $parent : '';
                $this->action = '';
            }
        }
        return $this->mainAction();
    }

    /**
     * @return bool
     */
    protected function replaceAction() : bool{
        $clip = Content::load($this->id);
        if($clip && $clip->swap()){
            $this->notify(sprintf('<b>%s</b> moved to collection cover', $clip->name), 'update');
        }
        return $this->mainAction();
    }

    /**
     * @return bool
     */
    protected function driveAction(): bool{
        var_dump(Content::drive($this->drive));
        return true;
    }

    /**
     * @return bool
     */
    protected function uploadAction( ) : bool {
        if($this->canupload()){
            $clips = Uploader::create( 'upload' )->items( $this->id  );
            $this->notify(sprintf('%s items uploaded!','update'),count($clips));
        }
        else{
            $this->notify(__('Not allowed to upload','coder_clipboard'), 'error');
        }
        return $this->mainAction( $input );
    }
    /**
     * @return bool
     */
    protected function updateAction( ) : bool {
        $id = $this->id;
        $clip = Content::load($id);
        if ( !is_null($clip) && $clip->update($this->data())) {
            $this->notify(sprintf('%s updated!',$clip->name), 'update');
        }
        else{
            $this->notify("Can't update", 'error');
        }

        return $this->mainAction();
    }
    /**
     * @return bool
     */
    protected function deleteAction( ) : bool {
        $content = Content::load($this->id);
        if ( $content && $content->remove()) {
            $this->notify(sprintf('%s removed!',$content->name),'update');
        }
        else{
            $this->notify('Unable to remove item','error');
        }
        return $this->mainAction();
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function sortAction( ) : bool {

        $item = Content::load($this->id);
        $slot = $this->getInt('slot');
        if (!is_null($item)) {
            $count = $item->sort($slot);
            $this->notify(sprintf('%s items udpated',$count), 'update');
        }
        return $this->mainAction();
    }
    /**
     * @return bool
     */
    protected function arrangeAction() : bool {
        $count = Content::manager()->db()->arrange($this->id);
        $this->notify(sprintf('%s items udpated',$count), 'update');
        return $this->mainAction();
    }
    /**
     * @return bool
     */
    protected function moveAction( ) : bool {
        $clip = Content::load($this->id);
        if( $clip && $clip->moveto($this->parent_id) ){
            $this->put('id',$this->id);
        }
        return $this->mainAction();
    }
    /**
     * @return bool
     */
    protected function moveupAction( ): bool {
        $clip = Content::load( $this->id );
        if (!is_null($clip) && $clip->moveup()) {
            $this->put('id',$this->id);
        }
        return $this->mainAction();
    }

    /**
     * @return bool
     */
    protected function movetoAction( ) : bool {
        return $this->moveAction();
    }
    /**
     * @return bool
     */
    protected function recoverAction() : bool {
        $lost = Content::findLost();
        $orphen = Content::restoreLost();
        if( count($lost) + $orphen ){
            $this->notify(sprintf('Recovered %s lost items and %s unparented items',
                count($lost),
                $orphen));
        }
        else{
            $this->notify('All clear, nothing found :)');
        }
        return $this->mainAction();
    }
    /**
     * @return bool
     */
    protected function copynameAction( ) : bool {
        $clip = Content::load( $this->id );
        if($clip){
            $count = $clip->copynames();
            $this->notify(sprintf('%s items updated',$count));
        }
        else{
            $this->notify('Invalid item','error');
        }
        return $this->mainAction();
    }
    /**
     * @return bool
     */
    protected function copyroleAction( ) : bool {
        $clip = Content::load( $this->id );
        if($clip){
            $count = $clip->copyroles();
            $this->notify(sprintf('%s items updated!',$count),'update');
        }
        else{
            $this->notify('Invalid clip','error');
        }
        return $this->mainAction();
    }
    /**
     * @return bool
     */
    protected function copylayoutAction( )  : bool{
        $clip = Content::load( $this->id );
        if($clip){
            $count = $clip->copylayouts();
            $this->notify(sprintf('%s items updated!',$count),'update');
        }
        else{
            $this->notify('Invalid clip','error');
        }
        return $this->mainAction();
    }
}
/**
 * 
 */
class SettingsController extends Controller{
    /**
     * @return bool
     */
    protected function mainAction( ): bool {
        $this->view()->show('settings');
        return true;
    }
    /**
     * @return bool
     */
    protected function nukeAction( ) : bool {
        $settings = new Settings();
        $data = $settings->cleardata();
        $files = $settings->cleardrive();
        if( $data){
            $this->notify(__('Clipboard data clear','coder_clipboard'));
        }
        else{
            $this->notify(__('Unable to clear Clipboard data','coder_clipboard'),'warning');            
        }
        if( $files){
            $this->notify(sprintf('%s %s',$files,__('files removed','coder_clipboard')),'update');
        }
        else{
            $this->notify(__('Unable to clear Clipboard drive','coder_clipboard'),'warning');            
        }
        return $this->mainAction();
    }
}

/**
 * 
 */
class PostController extends Controller{
    /**
     * @param array $input
     */
    function __construct($input = array()) {
        parent::__construct($input);
        //$this->put('page', 'coder_clipboard');
        if( strlen($this->id)){
            $this->put('id',$this->id);
        }
    }
    /**
     * @return bool
     */
    protected function mainAction(): bool {
        return true; 
    }
    /**
     * @param array $input
     * @return boolean
     */
    protected function updateAction( ) : bool{
        $clip = Content::load($this->id);
        return $clip ? $clip->update($this->data()) : false;
    }
}
/**
 * 
 */
class AjaxController extends Controller{
    /**
     * @param array $input
     */
    function __construct( $input = array()) {
        parent::__construct( $input );
    }
    /**
     * @return bool
     */
    protected function testAction( ) {
        var_dump($this->data());
        $action = sprintf('%sAction',$this->action());
        if(method_exists($this, $action)){
            var_dump( $this->$action());
        }
        var_dump($this->response());
        return true;
    }


    /**
     * @return bool
     */
    protected function mainAction(): bool {
        return $this->listAction();
    }
    protected function countAction() : bool {
        $this->put('count',Content::manager()->db()->count(true));
        return true;
    }
    /**
     * @return bool
     */
    protected function listAction(): bool{
        $items = Content::collection($this->id);
        $this->put('items',$items);
        return true;
    }
    /**
     * @return bool
     */
    protected function driveAction(): bool{
        $this->put('items',Content::drive($this->drive));
        return true;
    }

    /**
     * @return bool
     */
    protected function attributesAction(): bool{
        $clip = new Content();
        $this->put('attributes',$clip->attributes());
        return true;
    }
    /**
     * @return bool
     */
    protected function loadAction() :bool{
        $clip = Content::load($this->id);
        $this->put('item',$clip ? $clip->meta() : null);
        return true;
    }

    /**
     * @return boolean
     */
    protected function uploadAction( ) : bool{
        if($this->canupload()){
            $clips = Uploader::create( 'upload' )->items($this->id);
            $response = array('count' => count($clips),'items' => $clips);
            $this->fill($response);
            return true;
        }
        return false;
    }
    /**
     * @return boolean
     */
    protected function removeAction( ) : bool{
        $clip = Content::load($this->id);
        $this->put('id',$this->id);
        if( is_null( $clip ) ){
            $this->notify(sprintf('Invalid clip <b>%s</b>',$this->id),'warning');
            return false;
        }
        $items = $clip->listIds();
        if( !$clip->remove()){
            $this->notify(sprintf('Unable to remove <b>%s</b>',$this->id));
            return false;
        }
        $this->put('items',$items);
        $this->notify(sprintf('Clip <b>%s</b> removed',$this->name),'update');
        return true;
    }
    /**
     * @return boolean
     */
    protected function movetoAction( ) : bool{
        $clip = Content::load($this->id);
        $target = $this->target;
        if( !$clip ) {
            $this->notify('Invalid clip','warning');
            return false;
        }
        if(strlen($target) === 0){
            $this->notify('No target selected', 'warning');
            return false;
        }
        if(!$clip->moveto($target)){
            $this->notify(sprintf('Unable to move <b>%s</b>',$this->id),'warning');
            return false;
        }
        $this->fill(array('id' => $this->id,'target'=>$target));
        return true;
    }
    /**
     * @return boolean
     */
    protected function moveupAction( ) : bool{
        $clip = Content::load($this->id);
        if($clip && $clip->moveup()){
            $this->notify('Moved!','update');
            return true;
        }
        else{
            $this->notify('Invalid item','error');
        }
        return false;
    }
    /**
     * @return bool
     */
    protected function sortAction() : bool{
        $clip = Content::load($this->id);
        $slot = $this->getInt('slot');
        if( !$clip){
            $this->notify('Invalid clip','warning');
            return false;
        }
        $count = $clip->sort($slot);
        if ($count ){
            $this->put('list',Content::slots($clip->parent_id));
            $this->notify(sprintf('%s items updated',$count));
            return true;
        }
        return false;
    }
    /**
     * @return bool
     */
    protected function replaceAction() : bool{
        $clip = Content::load($this->id);
        $this->put('id',$this->id);
        if($clip){
            return $clip->swap();
        }
        return false;
    }
    /**
     * @return boolean
     */
    protected function setaclAction( ) : bool {
        $clip = Content::load($this->id);
        if( $clip){
            $count = $clip->copyroles();
            $this->notify(sprintf('%s items updated',$count));
            return true;
        }
        else{
            $this->notify('Invalid item','error');
        }
        return false;
    }
    /**
     * @return boolean
     */
    protected function setlayoutAction( ) :bool{
        $clip = Content::load($this->id);
        if( $clip){
            $count = $clip->copylayouts();
            $this->notify(sprintf('%s items updated',$count));
            return true;
        }
        else{
            $this->notify('Invalid item','error');
        }
        return false;
    }
}


/**
 * 
 */
class Content extends \CODERS\Clipboard\Clip{
    
    /**
     * @var bool
     */
    private $_updated = false;
    /*public function __construct($input = array(), $preload = false, $toplevel = '') {
        parent::__construct($input, $preload, $toplevel);
        //override with new attributes?
    }*/
    /**
     * @param string $name
     * @param string $value
     * @return boolean
     */
    protected function set($name = '', $value = ''){
        if( parent::set($name, $value)){
            $this->_updated = true;
            return true;
        }
        return false;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value) {
        $this->set($name,$value);
    }

    /**
     * @return array
     */
    public function attributes(){
        return array_keys($this->data());
    }
    /**
     * @return array
     */
    public function listIds() {
        return $this->db()->list($this->id, array('id'));
    }
    /**
     * @return \CODERS\Clipboard\Admin\Content
     */
    public function parent(){
        return Content::load($this->parent_id);
    }
    
    /**
     * @param string $parent_id
     * @param int $slot
     * @return int
     */
    public function sort($slot = 0  ){
        if( $slot ){
            $this->slot = $slot;
            $count = $this->db()->arrange($this->parend_id,$slot);
            $sort = $this->db()->sort($this->id, $slot);
            //DEBHUG
            $this->notify(sprintf('%s slot is now %s (%s)',
                    $this->name,
                    $this->slot,
                    $this->isUpdated() ? 'updated' : 'not updated'),'debug');
            return $count + $sort;
        }
        return 0;
    }    

    /**
     * @return int
     */
    public function copynames() {
        $name = $this->name;
        $title = $this->title;
        $db = $this->db();
        $count = $db->update(array(
            'name' => $name,
            'title' => $title,
        ), array('parent_id' => $this->id));
        //fetch all error messsages from $db?
        return $count ?? 0;
    }

    /**
     * @return int
     */
    public function copylayouts(){
        
        $count = $this->db()->update(array(
            'layout' => $this->layout
        ), array( 'parent_id'=>$this->id));
        
        return $count ?? 0;
    }
    /**
     * @return int
     */
    public function copyroles( ){
        
        $count = $this->db()->update(array(
            'acl' => $this->acl
        ), array( 'parent_id'=>$this->id));
        
        return $count ?? 0;
    }
    /**
     * @return boolean
     */
    public function moveto( $toid = '') {
        if( !$this->isValid()){
            $this->notify('Invalid id', 'warning');
            return false;
        }
        if( $this->parent_id === $toid ){
            $this->notify( sprintf('Same id as parent <b>%s</b>',$toid), 'warning');
            return false;
        }
        //load upper level, or set to root
        $content = self::load($toid);
        $upper_id = $content ? $content->parent_id : '';
        if( $upper_id && $upper_id === $this->id ){
            //avoid recursive dependencies id = parent and parent's parent = id...
            $this->notify( sprintf('Cant create a recursive dependency<b>%s</b>',$toid), 'warning');
            return false;
        }
        $data = array(
            'parent_id' => strlen($toid) ? $toid: '',
            'slot' => self::count($toid) + 1,
        );
        if ( !$this->db()->update($data, array('id' => $this->id)) ) {
            $this->notify(sprintf('Failed to move to parent <b>%s</b>', $toid),'warning');
            return false;
        }
        return true;
    }

    /**
     * @return boolean
     */
    public function moveup(){
        if( $this->parent_id ){
            $parent = self::load($this->parent_id);
            return $this->moveto($parent->parent_id);
        }
        return false;
    }    
    
    /**
     * Remove and update depending childs to parent's id
     * @return boolean
     */
    public function remove( ){
        $db = $this->db();
        $storage = self::manager()->storage();
        $id = $this->id;
        $parent_id = $this->parent_id;

        if( strlen($id) === 0 ){
            $this->notify('Invalid id', 'warning');
            return false;
        }
        if ( !$storage->remove($id)) {
            $this->notify(sprintf('Unable to remove <b>File %s</b>', $id));
            return false;
        }
        if (!$db->delete(array('id' => $id))) {
            $this->notify(sprintf('Unable to remove <b>Clip %s</b>', $id));
            return false;
        }
        //update all items belonging to this removed clip
        $count = $db->update(
                array('parent_id' => $parent_id),
                array('parent_id' => $id));

        if($count){
            $this->notify(sprintf('<b>%s</b> items updated',$count));
        }

        return true;
    }
    
    /**
     * Override with more metadata for the admin view
     * @return array
     */
    public function meta(): array {
        $data = parent::meta();
        $data['items'] =$this->countItems();
        $data['post'] = View::adminurl(array('id'=>$clip->id));
        return $data;
    }
    /**
     * @param string $tag
     * @return \CODERS\Clipboard\Admin\Content
     */
    private function tag($tag = ''){
        if(strlen($tag)){
            $this->tags = $this->tags . ' ' . $tag; 
        }
        return $this;
    }
    /**
     * Generate image format css tags
     * @return \CODERS\Clipboard\Admin\Content
     */
    private function tagmedia(){
        if($this->isImage()){
            $size = getimagesize($this->getPath());
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
        }
        return $this;
    }

    /**
     * @return boolean
     */
    public function isUpdated(){
        return $this->_updated;
    }        
    /**
     * @param array $data
     * @return boolean
     */
    public function update( array $data = array()){
        foreach($data as $key => $val ){
            $this->$key = $val;
        }
        return $this->save();
    }
    /**
     * @return bool
     */
    public function save() {
        if($this->isUpdated()){
            $this->_updated = false;
            $backup = $this->clone();
            if( $this->db()->update($this->data(),['id'=>$this->id]) ){
                return true;
            }
            $this->rollback($backup);
        }
        return false;
    }    
    /**
     * Replace collection cover by current clip item
     * @return bool
     */
    public function swap() {
        $parent = $this->parent();
        if( $parent && $parent->parent_id !== $this->id){
            $this->parent_id = $parent->parent_id;
            $parent->parent_id = $this->id;
            if( !$this->save()){
                $this->notify( sprintf('Cannot save <b>%s</b>',$this->name),'warning');
                return false;
            }
            if( !$parent->save()){
                $this->notify( sprintf('Cannot save <b>%s</b>',$parent->name),'warning');
                return false;
            }
            //move from parent id's collection to current item id
            $count = $this->db()->movecollection($parent->id,$this->id);
            $this->notify(sprintf('<b>%s</b> items updated',$count),'update');
            return true;
        }
        return false;
    }
    /**
     * @return \CODERS\Clipboard\Admin\Content
     */
    private function clone(){
        return new Content($this->data());
    }
    /**
     * 
     * @param \CODERS\Clipboard\Admin\Content $copy
     * @return bool
     */
    private function rollback( $copy = null ) {
        if(get_class($copy) === self::class){
            //
            return true;
        }
        return false;
    }
    /**
     * @param string $collection_id
     * @return array
     */
    public static function slots( $collection_id = ''){
        return self::db()->slots($collection_id);
    }
    /**
     * @param string $id
     * @return int
     */
    public static function count($id = '') {
        $list = self::db()->list($id);
        return count($list);
    }
    /**
     * @param array $data
     * @return \CODERS\Clipboard\Clip
     */
    public static function create( array $data = array()) {
            $clip = new Content($data);
            $clip->tagmedia();
            return $clip->db()->create($clip->data()) ? $clip : null;
    }
    /**
     * @param string $id
     * @param boolean $preload
     * @param string $toplevel
     * @return \CODERS\Clipboard\Admin\Content
     */
    public static function load($id = '',$preload = false , $toplevel = '') {
        $clip = parent::load($id,$preload,$toplevel);
        return $clip ? new Content( $clip->data(),$preload) : null;
    }
    /**
     * @return \CODERS\Clipboard\Clipboard
     */
    public static function manager(){
        return \CODERS\Clipboard\Clipboard::instance();
    }
    /**
     * @param string $id
     * @return array
     */
    public static function collection( $id = '' ){
        $clips = self::manager()->list($id,true);
        return array_map( function ($clip){
            $data = $clip->meta();
            $data['slot'] = intval( $clip->slot );
            $data['items'] = $clip->countItems();
            return $data;
        },$clips);
    }
    /**
     * 
     * @param type $drive
     * @return array
     */
    public static function drive( $drive = ''){
        $clips = self::manager()->db()->list('', array(),
                strlen($drive) ? $drive : 'content' );
        return array_map( function ($data){
            $content = new Content($data);
            return $content->meta();
        },$clips);
    }
    
    /**
     * @return int
     */
    public static function restoreLost(){
        return self::db()->recover();
    }
    /**
     * @global wpdb $wpdb
     * @return array
     */
    public static function findLost() {
        $db_ids = array_map('strtolower', self::db()->allids());
        $drive = self::manager()->storage();
        $lost = [];
        $skip = ['..','.'];
        $files = $drive->list();
        foreach ($files as $file) {
            if ( !in_array(strtolower($file), $db_ids) && !in_array($file, $skip)) {
                $path = $drive->route($file);
                $lost[$file] = mime_content_type($path);
            }
        }
        return self::restore($lost);
    }    
    /**
     * @param array $files
     * @return \CODERS\Clipboard\Admin\Content[]
     */
    private static function restore( $files = []) {
        $restored = array();
        foreach ($files as $id => $type ){
            $restored[] = self::create(array(
                'id'=>$id,
                'type'=>$type
            ));
        }
        return $restored;
    }
}
/**
 * 
 */
class Settings{
    /**
     * @var array
     */
    private $_settings = array(
        //add settings here
    );
    /**
     * @param string $name
     * @return string
     */
    public function __get($name): string {
        return $this->has($name) ? $this->_settings[$name] : '';
    }
    /**
     * 
     * @param string $name
     * @param mixed $value
     */
    public function __set($name,$value) {
        if($this->has($name)){
            $this->_settings[$name] = $value;
        }
    }
    /**
     * @param string $name
     * @return bool
     */
    public function has($name) {
        return array_key_exists($name, $this->_settings);
    }
    /**
     * @return array
     */
    public function listSettings(){
        return $this->_settings;
    }


    /**
     * @return bool
     */
    public function cleardata(){
        return Content::manager()->db()->cleanup();
    }
    
    
    /**
     * @return int
     */
    public function cleardrive(){
        return Content::manager()->storage()->clear();
    }
}




/**
 * 
 */
class View{
    /**
     * @var object
     */
    private $_content = null;
    /**
     * @var string
     */
    private $_context = '';
    /**
     * @var array
     */
    private $_settings = array();
    /**
     * @var \CODERS\Clipboard\Admin\Text
     */
    private $_text = null;
    
    /**
     * @param string $context
     */
    function __construct( $context = '' ) {
        $this->_context = $context;
        $this->_text = new Text();
    }
    /**
     * @param string $context
     * @return \CODERS\Clipboard\Admin\View
     */
    static public function create($context = 'main') {
        $view = sprintf('\CODERS\Clipboard\Admin\%sView', ucfirst($context));
        return is_subclass_of($view, self::class) ? new $view($context) : new View($context);
    }

    /**
     * @param object $content
     * @return \View Description
     */
    public function setContent( $content = null ){
        if(is_object($content)){
            $this->_content = $content;
        }
        return $this;
    }
    /**
     * @return object
     */
    protected function content(){
        return $this->_content;
    }
    /**
     * @param string $key
     * @return string
     */
    public function get( $key = ''){
        return strlen($key) ? $this->_settings[$key] ?? '' : '';
    }
    /**
     * @param string $key
     * @param string $value
     * @return \CODERS\Clipboard\Admin\View
     */
    public function set( $key = '' , $value = ''){
        if(strlen($key)){
            $this->_settings[$key] = $value;
        }
        return $this;
    }

        /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments) {
        $args = $arguments ?? array();
        switch(true){
            case preg_match('/^get_/', $name):
                $get = sprintf('get%s', ucfirst(substr($name, 4)));
                return method_exists($this, $get) ? $this->$get(...$args) : '';
            case preg_match('/^check_/', $name):
                $check = sprintf('check%s', ucfirst(substr($name, 6)));
                return method_exists($this, $check) ? $this->$check(...$args) : false;
            case preg_match('/^count_/', $name):
                $count = sprintf('count%s', ucfirst(substr($name, 6)));
                return method_exists($this, $count) ? $this->$count(...$args) : 0;
            case preg_match('/^list_/', $name):
                return $this->__list(substr($name, 5));
            case preg_match('/^is_/', $name):
                return $this->__is(substr($name, 3));
            case preg_match('/^has_/', $name):
                return $this->__has(substr($name, 4));
            case preg_match('/^action_/', $name):
                $action = substr($name, 7);
                $call = sprintf('action%s', ucfirst($action));
                return method_exists($this, $call) ?
                        $this->$call(...$args) :
                        self::adminurl(array('action'=>$action));
            case preg_match('/^show_/', $name):
                return $this->template(substr($name, 5));
            case preg_match('/^editor_/', $name):
                return $this->__editor( substr($name, 7), ...$args );
            case preg_match('/^link_/', $name):
                return $this->link( substr($name, 5), ... $args );
            case preg_match('/^url_/', $name):
                return $this->url( explode( '_', substr($name, 4)), ...$args );
        }
        
        return '';
        //return !is_null($this->content()) ? $this->content()->$name : '';
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        $get = sprintf('get%s', ucfirst($name));
        switch(true){
            case preg_match('/^text_/', $name):
                return $this->text()->get(substr($name, 5));
            case preg_match('/^print_/', $name):
                print $this->text()->get(substr($name, 6));
                break;
            case $this->hasContent() && method_exists($this->content(), $get):
                return $this->content()->$get();
            case method_exists($this, $get):
                return $this->$get();
            case $this->hasContent():
                return $this->content()->$name;
            default:
                return $this->get($name);                
        }
        return '';
    }
    /**
     * @param string $name
     */
    protected function __editor($name) {
        $content = $this->$name;
        $id = 'id_' . $name;
        $settings = [
            'textarea_name' => $name, // The name attribute
            'editor_height' => 250,
            'media_buttons' => true,
            'tinymce' => true,
            'quicktags' => true
        ];

        wp_editor($content, $id, $settings);
    }    
    /**
     * @return \CODERS\Clipboard\Admin\Text
     */
    protected function text( ){
        return $this->_text;
    }
    /**
     * @param string $list
     * @return array
     */
    protected function __list($list = ''){
        $call = sprintf('list%s', ucfirst($list));
        if( $this->hasContent() && method_exists($this->content(), $call) ){
            return $this->content()->$call();
        }
        return method_exists($this, $call) ? $this->$call() : array();
    }
    /**
     * @param string $has
     * @return bool
     */
    protected function __has($has = '') {
        $call = sprintf('has%s', ucfirst($has));
        if( $this->hasContent()  && method_exists($this->content(), $call) ){
            return $this->content()->$call();
        }
        return method_exists($this, $call) ? $this->$call() : false;
    }
    /*
     * @param string $has
     * @return bool
     */
    protected function __is( $is = '' ){
        $call = sprintf('is%s', ucfirst($is));
        if( $this->hasContent()  && method_exists($this->content(), $call) ){
            return $this->content()->$call();
        }
        return method_exists($this, $call) ? $this->$call() : false;
    }
    
    /**
     * 
     * @param string $link
     * @param array $query
     * @return string|url
     */
    protected function link($link = '', array $query = array()) {
        $call = sprintf('link%s', ucfirst($link));
        return method_exists($this, $call) ? $this->$call($query) : $this->url($link,$query);
    }
    /**
     * @param array $path
     * @param array $args
     * @return string
     */
    protected function url( $path = '' , array $args = array() ){
        if(is_array($path)){
            $path = implode('/', $path);
        }
        $base_url = site_url( $path );

        $get = array();

        foreach( $args as $var => $val ){
            $get[] = sprintf('%s=%s',$var,$val);
        }

        if( count( $get )){
            $base_url .=  '?' . implode('&', $get);
        }

        return $base_url;
    }
    /**
     * @param type $form
     * @param array $args
     * @return type
     */
    protected function form( $form = '' , array $args = array() ){
        if(strlen($form)){
            $args['task'] = $form;
        }
        return View::adminurl($args);
    }

    /**
     * @param string $action
     * @param array $args
     * @return string
     */
    protected function action( $action = '' , array $args = array()){
        $call = sprintf('action%s', ucfirst($action));
        if(method_exists($this, $call)){
            return $this->$call(...$args);
        }
        if(strlen($action)){
            $args['action'] = $action;
        }
        return View::adminurl($args);
    }
    /**
     * @param string $name
     * @return string
     */
    protected function path($name = ''){
        return sprintf('%s/html/admin/%s',CODER_CLIPBOARD_DIR,$name);
    }
    /**
     * @param string $view
     * @return bool
     */
    public function show($view = ''){
        $path = $this->path(sprintf('%s.php', strlen($view) ? $view : $this->_context));
        if(file_exists($path)){
            require $path;
        }
        else{
            require $this->path('error.php');
        }
        return $this;
    }
    /**
     * @param string $template
     * @return bool
     */
    protected function template( $template = '' ){
        printf('<!-- TEMPLATE [%s] -->',$template);
        return strlen($template) && $this->show(sprintf('templates/%s',$template));
    }    
    
    /**
     * @return array
     */
    protected function listMessages( ){
        return Controller::log();
    }    
    /**
     * @return bool
     */
    protected function hasContent(){
        return !is_null( $this->content());
    }
        /**
     * @return string
     */
    protected function getNonce(){
        return '';
        return wp_nonce_field(-1,'coder_nonce',false,false);
    }

    /**
     * @param array $args
     * @return url|string
     */
    public static function adminurl( array $args = array() ) {
        $query = array( 'page' => 'coder_clipboard');
        foreach($args as $key => $val ){
            $query[$key] = $val;
        }
        return add_query_arg($query, admin_url('admin.php'));
    }
    /**
     * @return string
     */
    public function getForm(){
        return esc_url(admin_url('admin-post.php'));
    }
    /**
     * @return int
     */
    public function getMaxfilesize(){
        $max = wp_max_upload_size() / 1000000 ;
        return sprintf('%s MB', number_format($max,2));
    }

    /**
     * @param string $page
     */
    public static function preload( $page = ''){
        if ($page === 'coder_clipboard') {
            $public = get_site_url();
            $admin = self::adminurl();
            $style = sprintf('%shtml/admin/content/style.css', CODER_CLIPBOARD_URL);
            $style_path = sprintf('%shtml/admin/content/style.css', CODER_CLIPBOARD_DIR);
            $script = sprintf('%shtml/admin/content/script.js', CODER_CLIPBOARD_URL);
            $script_path = sprintf('%shtml/admin/content/script.js', CODER_CLIPBOARD_DIR);
            wp_enqueue_style('clipboard-style', $style, [], filemtime($style_path));
            wp_enqueue_script('clipboard-script', $script, [], filemtime($script_path), true);
            wp_localize_script('clipboard-script', 'CodersAPI', [
                'public' => $public,
                'admin' => $admin,
                'ajax' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('coder_nonce'),
                'maxfilesize' => wp_max_upload_size(),
            ]);
        }
    }
}

/**
 * 
 */
class MainView extends View{
    /**
     * @var string
     */
    //private $_mode = 'manual';
    
    /**
     * @return \CODERS\Clipboard\Clip[]
     */
    public function listItems(){
        return Content::list();
    }
    /**
     * @return array
     */
    public function listDrives() {
        $storage = array();
        $drives = Content::manager()->storage()->drives();
        foreach($drives as $drive ){
            $storage[$drive] = $drive === $this->getDrive();
        }
        return $storage;
    }
    /**
     * @return array
     */
    protected function listTiers(){
        //$tiers = apply_filters('coder_tiers', array());
        $tiers = Content::manager()->acl()->list();
        return is_array($tiers) ? $tiers : array();
    }
    /**
     * @return array
     */
    protected function listSlots(){
        return Content::slots($this->id);
    }

    /**
     * @return array
     */
    protected function listRoles(){
        $roles = array(
            'private' => __('Private (admin only)', 'coder_clipboard'),
            'public' => __('Public (everyone)', 'coder_clipboard'),
        );
        
        foreach( $this->listTiers() as $tier => $title ){
            $roles[$tier] = sprintf('%s Tier',$title);
        }
        return $roles;
    }
    /**
     * @return array
     */
    protected function listLayouts(){
        return array(
            'default' => __('Default', 'coder_clipboard'),
            'ecomic' => __('e-Comic', 'coder_clipboard'),
            'collection' => __('Collection', 'coder_clipboard'),
            'gallery' => __('Gallery', 'coder_clipboard'),
            'slideshow' => __('Slideshow', 'coder_clipboard'),
            'portfolio' => __('Portfolio', 'coder_clipboard'),
            'mosaic' => __('Mosaic', 'coder_clipboard'),
            'showcase' => __('Showcase', 'coder_clipboard'),
        );
    }
    /**
     * @return bool
     */
    public function isEmpty(){
        return !$this->hasContent() && count(Content::list()) === 0;
    }
    /**
     * @param string $acl
     * @return string
     */
    protected function checkRole($acl = '') {
        return strlen($acl) && $acl === $this->acl;
    }
    /**
     * @param string $layout
     * @return string
     */
    protected function checkLayout($layout = ''){
        return strlen($layout) && $layout === $this->layout ;
    }
    /**
     * @return bool
     */
    public function isAjaxmode(){
        return $this->getMode() === 'ajax';
    }
    /**
     * @return bool
     */
    public function hasItems(){
        return $this->countItems() > 0;
    }
    /**
     * @return int
     */
    public function countItems(){
        //return $this->hasContent() ? count(Content::list($this->id)) : 0;
        return count(Content::list($this->id));
    }
    /**
     * @return string
     */
    public function getMode(){
        return 'ajax';
        //return $this->_mode;
    }

    /**
     * @return boolean
     */
    public function isMain(){
        return !$this->hasContent();
    }
    /**
     * @return string
     */
    public function getMain(){
        return $this->isMain() ? 'main' : 'item';
    }
    /**
     * @return string
     */
    public function getDrive() {
        $drive = $this->get('drive');
        return strlen($drive) ? $drive : 'content';
    }
    /**
     * @param string $id
     * @return string
     */
    public function getBase(){
        return View::adminurl();
    }
    /**
     * @param string $id
     * @return string
     */
    public function getPost( $id = '' ){
        return View::adminurl(array('id'=>$id ? $id : $this->id));
    }
    /**
     * @param string $id
     * @return string
     */
    public function getUrl( $id = '' ){
        if(strlen($id) === 0){
            $id = $this->id;
        }
        return Content::manager()->clipdata($id);
    }
    /**
     * @param string $id
     * @return string
     */
    public function actionArrange( $id = '' ){
        $action = array('action' => 'arrange');
        if($id){
            $action['id'] = $id;
        }
        elseif($this->id){
            $action['id'] = $this->id;
        }
        return self::adminurl ( $action );
    }

    /**
     * @param string $id
     * @return string
     */
    public function actionRemove( $id = '' ){
        $action = ['action'=>'remove'];
        if($id){
            $action['id'] = $id;
        }
        elseif($this->id){
            $action['id'] = $this->id;
        }
        return $this->adminurl($action);
    }
    /**
     * @param string $id
     * @return string
     */
    public function actionRename($id = '') {
        $action = ['action' => 'copyname'];
        if($id){
            $action['id'] = $id;
        }
        elseif($this->id){
            $action['id'] = $this->id;
        }
        return $this->adminurl($action);
    }
    /**
     * @param string $id
     * @return string
     */
    public function actionRole($id = '') {
        $action = ['action' => 'copyrole'];
        if($id){
            $action['id'] = $id;
        }
        elseif($this->id){
            $action['id'] = $this->id;
        }
        return $this->adminurl($action);
    }
    /**
     * @param string $id
     * @return string
     */
    public function actionLayout($id = '') {
        $action = ['action' => 'copylayout'];
        if($id){
            $action['id'] = $id;
        }
        elseif($this->id){
            $action['id'] = $this->id;
        }
        return $this->adminurl($action);
    }

    /**
     * @param string $id
     * @return string
     */
    public function actionMoveup( $id = ''){
        $action = ['action'=>'moveup'];
        if(strlen($id)){
            $action['id'] = $id;
        }
        elseif($this->id){
            $action['id'] = $this->id;
        }
        return View::adminurl($action);
    }
    /**
     * @param string $id
     * @return string
     */
    public function actionMove( $id = '' , $parent_id = ''){
        if(strlen($id) === 0){
            $id = $this->id;
        }
        return View::adminurl(array('action'=>'move','id'=>$id,'parent_id'=>$parent_id));
    }
}



/**
 * Upload Manager for new contents
 */
class Uploader {
    /**
     * @var array
     */
    private $_files = array();

    private function __construct($files = array()) {
        $this->_files = $files;
    }
    /**
     * @param string $drive
     * @return \CODERS\Clipboard\Storage
     */
    public static function storage($drive = 'content') {
        return Content::manager()->storage($drive);
    }
    /**
     * @return array
     */
    public final function files() {
        return $this->_files;
    }
    /**
     * @param string $id
     * @return \CODERS\Clipboard\Clip[]
     */
    public function items( $id = '' ) {
        $container = Content::load($id);
        $slot = !is_null($container) ? $container->count() : 0;
        $layout = !is_null($container) ? $container->layout : '';
        $acl = !is_null($container) ? $container->acl : '';

        $list = array();
        foreach($this->files() as $file ){
            //set the first parent id to the parsed ID
            $file['parent_id'] = $id;
            $file['slot'] = ++$slot;
            $file['acl'] = $acl;
            $file['layout'] = $layout;
            //then set the next to the first file ID
            if (strlen($id) === 0) {
                $id = $file['id'];
            }
            $clip = Content::create($file);
            $list[] = $clip->meta();
        }
        return $list;
    }

    /**
     * @param string $upload
     * @return array
     */
    private static function import($upload = 'upload') {

        $files = array_key_exists($upload, $_FILES) ? $_FILES[$upload] : array();
        $output = array();
        if (count($files)) {
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    $name = explode('.',$files['name'][$i])[0] ?? 'clip-'.$i;
                    $output[] = array(
                        'name' => $name,
                        'tmp_name' => $files['tmp_name'][$i],
                        'type' => $files['type'][$i],
                        'error' => $files['error'][$i],
                    );
                }
            } else {
                $output[] = $files;
            }
        }
        return $output;
    }

    /**
     * @param string $error
     * @return boolean
     * @throws \Exception
     */
    private static function validate($error = '') {
        try {
            switch ($error) {
                case UPLOAD_ERR_CANT_WRITE:
                    throw new \Exception('UPLOAD_ERROR_READ_ONLY');
                case UPLOAD_ERR_EXTENSION:
                    throw new \Exception('UPLOAD_ERROR_INVALID_EXTENSION');
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \Exception('UPLOAD_ERROR_SIZE_OVERFLOW');
                case UPLOAD_ERR_INI_SIZE:
                    throw new \Exception('UPLOAD_ERROR_CFG_OVERFLOW');
                case UPLOAD_ERR_NO_FILE:
                    throw new \Exception('UPLOAD_ERROR_NO_FILE');
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new \Exception('UPLOAD_ERROR_INVALID_TMP_DIR');
                case UPLOAD_ERR_PARTIAL:
                    throw new \Exception('UPLOAD_ERROR_INCOMPLETE');
                case UPLOAD_ERR_OK:
                    return true;
            }
        } catch (Exception $ex) {
            ClipboardAdmin::sendMessage($ex->getMessage(), 'error');
        }
        return false;
    }
    /**
     * @param string $from
     * @param string $to
     * @return bool
     */
    private static function save( $from ='' , $to = ''){
        return move_uploaded_file($from, $to);
    }
    /**
     * @param string $from upload input
     * @param string $into drive/storage content
     * @return \Uploader
     */
    public static final function create($from = 'upload',$into = 'content' ) {

        $drive = self::storage( $into );
        $input = self::import($from);
        $files = array();

        foreach ($input as $upload) {
            if (self::validate($upload['error'])) {
                $upload['id'] = $drive->makeid($upload['name']);
                $upload['path'] = $drive->route($upload['id']);
                // Move uploaded file
                if (self::save($upload['tmp_name'], $upload['path'])) {
                    $upload['size'] = filesize($upload['path']);
                    unset($upload['tmp_name']);
                    $files[] = $upload;
                }
                else {
                    Controller::notify(
                            __('Failed to move uploaded file', 'coder_clipboard') . ' ' . $upload['name'],
                            'error');
                }
            }
        }
        return new Uploader($files);
    }
    /**
     * @param string $from input name
     * @param string $id parent clipboard id
     * @return \CODERS\Clipboard\Clip[]
     */
    public static function upload($from = 'upload', $id = '') {
        $uploaded = array();
        $clipboard = Content::manager();
        $container = $clipboard->load($id);
        $slot = !is_null($container) ? $container->count() : 0;
        $layout = !is_null($container) ? $container->layout : '';
        $acl = !is_null($container) ? $container->acl : '';
        //attach to parent id, ir leave blank to set it to first clip
        foreach (self::create($from)->files() as $file) {
            //set the first parent id to the parsed ID
            $file['parent_id'] = $id;
            $file['slot'] = ++$slot;
            $file['acl'] = $acl;
            $file['layout'] = $layout;

            $clip = $clipboard->create($file);
            if(!is_null($clip)){
                $uploaded[] = $clip->meta();
                $uploaded['post'] = View::adminurl($clip->id);
            }
            //then set the next to the first file ID
            if (strlen($id) === 0) {
                $id = $file['id'];
            }
        }

        return $uploaded;
    }
}
/**
 * 
 */
class Text{
    /**
     * @var array
     */
    private $_strings = array();
    /**
     * @param string $path
     */
    public function __construct( $path = '') {
        $this->_strings = $this->load();
    }
    /**
     * @return array
     */
    protected function load(){
        return array(
            'test' => __('This is a test TEXT','coder_clipboard'),
            'workspace' => __('Workspace','coder_clipboard'),
            'removeitem' => __('All items will move up after removal','coder_clipboard'),
            'title' => __('Title', 'coder_clipboard'),
            'created' => __('Created', 'coder_clipboard'),
            'update' => __('Update', 'coder_clipboard'),
            'copylayout' => __('Copy current layout to all items', 'coder_clipboard'),
            'copyrole' => __('Copy current access role to all items', 'coder_clipboard'),
            'copyname' => __('Copy current name and title to all items', 'coder_clipboard'),
            'preview' => __('Preview', 'coder_clipboard'),
            'previewdesc' => __('Open a preview', 'coder_clipboard'),
            'nuke' => __('Reset Content Data','coder_clipboard'),
        );
    }
    /**
     * @param string $text
     * @return string
     */
    public function get( $text = ''){
        return strlen($text) ? $this->_strings[$text] ?? $text : 'empty';
    }
    /**
     * @param string $name
     * @return string
     */
    public function __get($name){
        return $this->get($name);
    }
}





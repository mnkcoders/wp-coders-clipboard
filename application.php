<?php defined('ABSPATH') or die;

/**
 * 
 */
final class ClipBoard extends CodersApp{
    /**
     * @param string $root
     */
    protected final function __construct($root) {
        //dependencies?
        parent::__construct($root);
    }
    /**
     * @param string $action
     * @return bool
     */
    protected final function run($action = '') {
        
        if( strlen($action) < 1 ){
            $action = 'main';
        }
        
        return is_admin() || $action === 'main' ? parent::run($action) : $this->source( $action );
    }
    /**
     * @param array $input
     * @return bool
     */
    protected final function runAdminMain(array $input = []) {
       
        $this->display('main');
        
        return parent::runAdminMain($input);
    }
    /**
     * @param array $input
     * @return bool
     */
    protected final function runMain(array $input = []) {
        
        $this->display('main');
        
        return parent::runMain($input);
    }
    /**
     * @param string $id
     * @return bool
     */
    public final function source( $id ){
        if(strlen($id)){
            $data = $this->data($id);
            $buffer = $this->load($id);
            if(strlen($buffer) && count($data) ){
                $this->header($data['name'],$data['type'],$data['attachment']);
                print $buffer;
                return TRUE;
            }            
        }
        $this->header('error', 'text/plain');
        print '{"error":"invalid id :D"}';
        return FALSE;
    }
    /**
     * @param string $name
     * @param string $type
     * @param bool $attachment
     * @return ClipBoard
     */
    private final function header( $name = 'image', $type = 'image/png', $attachment = FALSE ){
        
        header( sprintf("Content-type: %s; Content-Disposition: %s; filename=%s.png",
                $type,
                $attachment ? 'attachment' : 'inline',
                $name) );
        
        return $this;
    }
    /**
     * @param string $id
     * @return array
     */
    private final function data($id){
        return array(
            'id' => 'abcdefghijk',
            'type' => 'image/png',
            'attachment' => false,
        );
        global $wpdb;
        $sql = sprintf( "SELECT * FROM `%s_clipboard` WHERE `id`='%s'", $wpdb->prefix,$id );
        $result = $wpdb->get_results($sql, ARRAY_A);
        return count($result) ? $result[0] : array();
    }
    /**
     * @param String $id
     * @return string
     */
    private final function load( $id ){
        $path = $this->contentPath($id);
        if(file_exists($path)){
            $buffer = file_get_contents($path);
            if( $buffer !== false ){
                return $buffer;
            }
        }
        return '';
    }
    /**
     * @param String $id
     * @return String
     */
    private final function contentPath( $id = '' ) {
        $path = preg_replace('/\\\\/', '/', sprintf('%s/coders/clipboard/', wp_upload_dir()['basedir']));
        return strlen($id) ? $path . '/' . $id : $path;
    }
    /**
     * @param String $id
     * @return String
     */
    public final function getUrl($id = '') {
        $url = sprintf('%s/coders/clipboard/', wp_upload_dir()['baseurl']);
        return strlen($id) ? $url . '/' . $id : $url;
    }
}




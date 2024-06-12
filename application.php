<?php defined('ABSPATH') or die;

/**
 * ClipBoard Class
 */
final class ClipBoard extends CodersApp {

    /**
     * @param string $root
     */
    protected final function __construct($root) {
        //dependencies?
        parent::__construct($root);
    }

    /**
     * Admin Menu Setup
     */
    protected function adminMenu() {
        return array(
            'name' => __('ClipBoard', 'coders_clipboard'),
            'title' => __('ClipBoard', 'coders_clipboard'),
            'capability' => 'administrator',
            'slug' => 'clipboard',
            'icon' => 'dashicons-art',
            'position' => 200,
            'children' => array(
                array(
                    'name' => __('Settings', 'coders_clipboard'),
                    'title' => __('Settings', 'coders_clipboard'),
                    'capability' => 'administrator',
                    'slug' => 'settings',
                )
            ),
        );
    }

    /**
     * @param string $action
     * @return bool
     */
    public final function run($action = '') {

        return is_admin() || $action === 'main' ? parent::run($action) : $this->source($action);
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
    public final function source($id) {

        $resource = Resource::load($id);

        if (!is_null($resource)) {
            $resource->source();
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 
     * @param string $plugin
     */
    public static final function install($plugin) {

        global $wpdb;

        // Get the table prefix
        $table_name = $wpdb->prefix . 'clipboard';

        // SQL to create the table
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `ID` CHAR(32) NOT NULL,
            `type` VARCHAR(24) NOT NULL,
            `size` INT NOT NULL,
            `name` VARCHAR(32) NOT NULL,
            `description` LONGTEXT NOT NULL,
            `parent` CHAR(32) DEFAULT NULL,
            `order` INT NOT NULL,
            `role` VARCHAR(24) NOT NULL,
            `created` DATETIME NOT NULL,
            PRIMARY KEY (ID)
        ) {$wpdb->get_charset_collate()};";

        // Include the WordPress upgrade script
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create the table
        dbDelta($sql);

        parent::install($plugin);
    }

    /**
     * 
     * @param string $plugin
     */
    public static final function uninstall($plugin, $removeData = false) {

        if ($removeData) {
            //REMOVE TABLE
            global $wpdb;
            // Get the table name
            $table_name = $wpdb->prefix . 'clipboard';
            // SQL to drop the table
            $sql = "DROP TABLE IF EXISTS $table_name;";
            // Execute the query
            $wpdb->query($sql);
        }

        parent::uninstall($plugin);
    }
}

/**
 * Content class
 */
final class Resource {

    private $_data = array(
        'ID' => '',
        'parent' => '',
        'name' => 'upload',
        'description' => '',
        'type' => '',
        //'attachment' => false,
        'role' => '',
        'size' => 0,
        'order' => 0,
        'created' => '',
    );

    /**
     * @param array $data
     */
    private final function __construct(array $data) {

        $this->import($data);
    }

    /**
     * @param string $name
     * @return string
     */
    public function __get($name) {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __call($name, $args ) {
        return $this->get($name , count($args) ? $args[0] : '' );
    }

    /**
     * @param string $name
     * @param mixed $vaule
     */
    public function __set($name, $vaule) {
        $this->set($name, $vaule);
    }

    /**
     * @param string $name
     * @return bool
     */
    public final function has($name) {
        return array_key_exists($name, $this->_data);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    private final function set($name, $value) {
        switch ($name) {
            case 'order':
            case 'size':
                $this->_data[$name] = (int) $value;
                break;
            case 'ID':
            case 'created':
                if (strlen($this->$name) === 0 && strlen($value) > 0) {
                    $this->$name = $value;
                }
                break;
            default:
                $this->_data[$name] = $value;
                break;
        }
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public final function get($name, $default = '') {
        return array_key_exists($name, $this->_data) ? $this->_data[$name] : $default;
    }

    /**
     * @return bool
     */
    public final function ready() {
        return strlen($this->ID) > 0;
    }

    /**
     * @param array $data
     * @return \Resource
     */
    private final function import(array $data) {
        foreach ($data as $var => $val) {
            $this->$var = $val;
        }
        return $this;
    }

    /**
     * @param string $id
     * @param bool $forceAttachment
     * @return bool
     */
    public final function source($forceAttachment = false) {
        if ($this->ready()) {
            $buffer = $this->storage($this->ID);
            if (strlen($buffer) && $this->ready()) {
                $this->header($this->name, $this->type, $this->isAttachment() || $forceAttachment);
                print $buffer;
                return TRUE;
            }
        }
        $this->header('error', 'text/plain');
        print sprintf('{"error":"invalid id [%s]"}', $this->ID);
        return FALSE;
    }

    /**
     * @param string $name
     * @param string $type
     * @param bool $attachment
     * @return ClipBoard
     */
    private final function header($name = 'image', $type = 'image/png', $attachment = FALSE) {

        header(sprintf("Content-type: %s; Content-Disposition: %s; filename=%s.png",
                        $type,
                        $attachment ? 'attachment' : 'inline',
                        $name));

        return $this;
    }

    /**
     * @param String $id
     * @return string
     */
    private final function storage($id) {
        $path = $this->path($id);
        if (file_exists($path)) {
            $buffer = file_get_contents($path);
            if ($buffer !== false) {
                return $buffer;
            }
        }
        return '';
    }

    /**
     * @param String $id
     * @return String
     */
    public static final function path($id = '') {
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

    /**
     * 
     * @param array $filter
     * @return array
     */
    private static final function query(array $filter = array()) {

        if (count($filter) === 0) {
            $filter['parent'] = '';
        }

        $where = array();
        if (count($filter)) {
            foreach ($filter as $var => $val) {
                $where[] = sprintf("`%s`='%s'", $var, $val);
            }
        } else {
            //get root elements
            $where[] = "`parent`=''";
        }

        global $wpdb;
        $sql = sprintf("SELECT * FROM `%sclipboard` WHERE %s ORDER BY `order`;",
                $wpdb->prefix,
                implode(' AND ', $where));
        $result = $wpdb->get_results($sql, ARRAY_A);
        return count($result) ? $result[0] : array();
    }

    /**
     * @return \Resource
     */
    public function save() {

        global $wpdb;

        // Get the table name
        $clipboard = $wpdb->prefix . 'clipboard';

        // Unserialize the data
        //$data = unserialize($serialized_data);
        $data = $this->_data;

        $ts = date('Y-m-d H:i:s');

        // Prepare the data for insertion
        $input = array(
            'ID' => strlen($data['ID']) ? $data['ID'] : self::generateId(),
            'type' => strlen($data['type']) ? $data['type'] : 'text/plain',
            'name' => strlen($data['name']) ? $data['name'] : 'uploaded ' . $ts,
            'size' => isset($data['size']) ? (int) $data['size'] : 0,
            'description' => strlen($data['description']) ? $data['description'] : '',
            'parent' => strlen($data['parent']) ? $data['parent'] : '',
            'order' => isset($data['order']) ? (int) $data['order'] : 0,
            'role' => strlen($data['role_name']) ? $data['role_name'] : '',
            'created' => strlen($data['created']) ? $data['created'] : $ts
                //'created' => strlen($data['created']) ? $data['created'] : current_time('mysql')
        );

        try {
            // Insert the data into the database
            $wpdb->insert($clipboard, $input);

            // Check for errors
            if ($wpdb->last_error) {
                throw 'Could not insert data into the clipboard table';
            }

            if ($wpdb->insert_id > 0) {
                //
            }
        } catch (Exception $ex) {
            return new WP_Error('db_insert_error', $ex->getMessage(), $wpdb->last_error);
        }
        return $this;
    }

    /**
     * @param string $name
     * @return string
     */
    private static final function generateId($name = '', $variation = '') {

        return md5(strlen($name) ? $name . strval($variation) : uniqid('YmdHis', true));
    }

    /**
     * @return bool
     */
    public final function isAttachment() {
        switch ($this->type) {
            case 'image/png':
            case 'image/gif':
            case 'image/jpg':
            case 'image/jpeg':
                return false;
        }
        return true;
    }

    /**
     * @param string $upload
     * @return array
     */
    private static final function importUploads($upload) {
        $files = array_key_exists($upload, $_FILES) ? $_FILES[$upload] : array();
        $output = array();
        if (count($files)) {
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    $output[] = array(
                        'name' => $files['name'][$i],
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
     * Upload handler
     * @param string $name
     * @return array
     * @throws \Exception
     */
    public static final function upload($name) {

        $ts = uniqid(date('YmdHis'), true);
        $ts_counter = 0;
        $output = array();

        foreach (self::importUploads($input) as $upload) {
            try {
                switch ($upload['error']) {
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
                        break;
                }

                $buffer = file_get_contents($upload['tmp_name']);
                unlink($upload['tmp_name']);
                unset($upload['tmp_name']);
                if ($buffer !== FALSE) {
                    $upload['id'] = self::generateId($ts, ++$ts_counter);
                    $upload['path'] = $this->path($upload['id']);
                    if (file_put_contents($upload['path'], $buffer)) {
                        $upload['size'] = filesize($upload['path']);
                        $output[$upload['id']] = self::create($upload, true);
                    }
                } else {
                    throw new \Exception(sprintf('Failed to read upload buffer %s', $upload['name']));
                }
            } catch (\Exception $ex) {
                //send notification
                die($ex->getMessage());
            }
        }
        return $output;
    }

    /**
     * @param string $id
     * @return Resource
     */
    public static final function load($id) {

        $resource = self::query(array('id' => $id));

        return count($resource) ? new Resource($resource) : null;
    }

    /**
     * @param string $parentId
     * @return \Resource[]
     */
    public static final function list($parentId = '') {

        $list = self::query(array('parent' => $parentId));
        $output = array();
        foreach ($list as $resource) {
            $output[] = new Resource($resource);
        }
        return $output;
    }

    /**
     * 
     * @param array $data
     * @param bool $save
     * @return \Resource
     */
    public static final function create(array $data, $save = false) {

        $resource = new Resource($data);

        return $save ? $resource->save() : $resource;
    }
}




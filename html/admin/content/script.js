/****
 * {task: list, id:string|empty} retrieve the list of items in the current view to fill the collection
 * {task: load, id:string|empty} retrieve the current clip content view to fill the form
 * {task: moveup, id:string|string[]} move up to the upper level one or more items (accepts array of ids)
 * {task: moveto, id:string|string[],parentid} move up to the new parent id (sibling in the same colleciton)
 * {task: remove, id:string|string[]} can remove several selected items
 * {task: sort, id: string , position: (1,...) } requests a sorting update to arrange the collection
 * 
 * {filetask: file, id: string } uploads a file with a special task which includes the File object
 */
//Loader
document.addEventListener('DOMContentLoaded', () => App.client());


/**
 * 
 * @type App
 */
class App {
    /**
     * @returns {App}
     */
    constructor() {
        if (App.__instance) {
            return App.__instance;
        }
        App.__instance = this;

        this._input = new CoderInput();
        //this._id = this.input().get('id') || '';
        this._components = {};
        this._attributes = [];
        this._timeout = false;
        this.setup().initialize();
        console.log(this);
    }
    /**
     * @returns {Boolean}
     */
    timeout(){ return this._timeout; }
    /**
     * @returns {CoderInput}
     */
    input() { return this._input; }
    /***
     * @returns {App}
     */
    setup() {
        //console.log('Setup Components:' , this.components());
        return this.register(new CoderServer(CodersAPI || {}))
            .register(new CoderStrings())
            .register(new ClipFormView('div.content'))
            .register(new Notifier('div.notifier'))
            .register(new Collection('ul.collection'))
            .register(new Uploader('div.uploader'))
            .register(new Toolbar('ul.toolbar'))
            .register(new NavigatorView('ul.path'))
            .register(new ToggleAjax('button.toggle-mode'));
    }
    /***
     * @returns {App}
     */
    initialize() {
        this.components()
            .map(c => this._components[c])
            .filter(c => c instanceof ViewComponent)
            .forEach(c => c.initialize());
        
        return this.load( );
    }
    /**
     * @param {String} id 
     * @returns {App}
     */
    load( id = ''){
        App.clipview().load(id || this.id() );
        App.collection().clear().populate(id || this.id());
        return this;
    }
    /**
     * @returns {App}
     */
    loadattributes() {
        App.server().attributes(r => r.attributes && r.attributes.forEach(a => this._attributes.push(a)));
        return this;
    }
    /**
     * @returns {String[]}
     */
    components() { return Object.keys(this._components); }
    /**
     * @param {Object} component 
     * @returns {App}
     */
    register(component = null) {
        if (component instanceof Object) {
            this._components[component.constructor.name] = component;
        }
        return this;
    }
    /**
     * @param {String} name 
     * @returns {Component}
     */
    component(name = '') { return this._components[name] || null; }

    /**
     * @returns {String}
     */
    id() { return this.input().get('id') || ''; }

    /**
     * @returns {App}
     */
    static client() { return App.__instance || new App(); }
    /**
     * @returns {Notifier}
     */
    static log() {
        return this.client().component(Notifier.name);
    }
    /**
     * @param {String} message 
     * @param {String} type 
     * @returns {ClipboardView}
     */
    static notify(message, type = 'info') {
        const log = this.log();
        log && log.show(message, type);
        return this;
    }
    /**
     * @param {String} text 
     * @returns {String}
     */
    static text(text = '') {
        const strings = this.client().component(CoderStrings.name);
        return strings && strings.get(text) || text;
    }


    /**
     * @returns {CoderServer}
     */
    static server() { return this.client().component(CoderServer.name); }
    /**
     * @returns {Object}
     */
    static api() { return this.server().api(); }
    /**
     * @returns {ClipFormView}
     */
    static clipview() { return this.client().component(ClipFormView.name); }
    /**
     * @returns {Collection}
     */
    static collection() { return this.client().component(Collection.name); }
    /**
     * @returns {NavigatorView}
     */
    static nav() { return this.client().component(NavigatorView.name); }
    /**
     * @returns {Toolbar}
     */
    static tools() { return this.client().component(Toolbar.name); }
    /**
     * @returns {Uploader}
     */
    static uploader() { return this.client().component(Uploader.name); }
}

/**
 * 
 */
class CoderInput {
    /**
     * 
     */
    constructor() {
        const url = location.href.split('?');
        this._url = url[0];
        this._input = this.parse(url[1] || '');
    }
    /**
     * @returns {CoderInput}
     */
    static create() { return new CoderInput(); }
    /**
     * @param {String} key 
     * @param {String} value 
     * @returns {CoderInput}
     */
    set( key = '' , value = '' ){
        this._input[key] = value;
        return this;
    }
    /**
     * @param {String} key 
     * @returns {String}
     */
    get(key = '') { return key && this._input[key] || ''; }
    /**
     * @param {String} key 
     * @returns {Number}
     */
    getInt(key = '') { return parseInt(this.get(key)) || 0; }
    /**
     * @param {String} input 
     * @returns {Object}
     */
    parse(input = '') {
        const content = {};
        input && input.split('&')
            .map(param => param.split('='))
            .forEach(pair => content[pair[0]] = pair[1] || '');
        return content;
    }
}

/**
 * Events: update , done
 */
class CoderServer {
    /**
     * @param {Object} api 
     */
    constructor(api = {}) {
        this._tasks = [];
        this._api = { public: '', admin: '', ajax: ajaxurl, nonce: '' , maxfilesize: 1000000 };
        Object.keys(this._api).forEach(key => this._api[key] = api[key] || '');
    }
    /**
     * @returns {Object}
     */
    api() { return this._api; }
    /**
     * @returns {Number}
     */
    maxfilesize(){ return this.api().maxfilesize; }
    /**
     * @returns {String}
     */
    publicurl() { return this.api().public; }
    /**
     * @param {String} id 
     * @returns {String}
     */
    clipurl(id = '') { return `${this.publicurl()}/clipdata/${id}`; }
    /**
     * @param {String} id 
     * @returns {String} 
     */
    clipboardurl(id = '') {
        return `${this.publicurl()}/clipboard/${id}`;
    }
    /**
     * @returns {String} 
     */
    adminurl() { return this.api().admin; }
    /**
     * @returns {String}
     */
    ajaxurl() { return this.api().ajax; }
    /**
     * @returns {String}
     */
    nonce() { return this.api().nonce || ''; }
    /**
     * @param {Object[]} messages 
     * @returns {CoderServer}
     */
    dumplog(messages = []) {
        messages.forEach(m => this.notify(m.content || '', m.type || 'info'));
        return this;
    }
    /**
     * @param {String} message 
     * @param {String} type 
     * @returns {CoderServer}
     */
    notify( message , type = 'info'){
        App.notify(message,type);
        return this;
    }
    /**
     * 
     * @param {ClipTask} task 
     * @returns {CoderServer}
     */
    add(task = null) {
        if (task instanceof ClipTask) {
            //notify?
            task._('notify', messages => this.dumplog(messages));
            task.request();
        }
        return this;
    }


    /// define all task creators
    /**
     * list and populate all items in the clipboard
     * @param {String} id 
     * @param {Function} callback 
     * @returns {CoderServer}
     */
    list(id = '', callback = nul) {
        return this.add(new ClipTask('list', id && { 'id': id } || {}, callback));
    }
    /**
     * @param {Function} callback 
     * @returns {CoderServer}
     */
    count(callback = null){
        return this.add(new ClipTask('count',{},callback));
    }
    /**
     * @param {Function} callback 
     * @returns {CoderServer}
     */
    attributes(callback = null) {
        return this.add(new ClipTask('attributes', {}, callback));
    }
    /**
     * @param {String} id 
     * @param {Function} callback 
     * @returns {CoderServer}
     */
    load(id = '', callback = null) {
        return this.add(new ClipTask('load', { 'id': id }, callback));
    }
    /**
     * @param {File} file 
     * @param {String} id 
     * @param {Function} callback 
     * @returns {CoderServer}
     */
    upload(file = null, id = '', callback = null) {
        if (file instanceof File) {
            if( file.size < this.maxfilesize() ){
                const data = {};
                if (id) {
                    data['id'] = id;
                }
                this.add(new UploadTask(file, data, callback));
            }
            else{
                this.notify(`${file.name} cannot be uploaded because exceeds the size limit`,'warning');
            }
        }
        return this;
    };
    /**
     * @param {File[]} files 
     * @returns {ClipQueue}
     */
    queue(files = []) { return new ClipQueue(files, App.client().id()); }
    /**
     * save clipdata
     * @param {ClipData} data 
     * @param {Function} callback 
     * @returns {CoderServer}
     */
    save(data = null, callback = null) {
        if (data instanceof ClipData) {
            this.add(new ClipTask('save', data.content(), callback));
        }
        return this;
    }
    /**
     * @param {String} id 
     * @param {Function} callback 
     * @returns {CoderServer}
     */
    moveup(id = '', callback = null) {
        return this.add(new ClipTask('moveup', {
            'id': id,
        }, callback));
    }
    /**
     * @param {String} id 
     * @param {String} target 
     * @param {Function} callback 
     * @returns {CoderServer}
     */
    moveto(id = '', target = '', callback = null) {
        return this.add(new ClipTask('moveto', {
            'id': id,
            'target': target,
        }, callback));
    }
    /**
     * @param {String} id 
     * @param {String} target 
     * @param {Function} callback 
     * @returns {CoderServer}
     */
    replace(id = '' , callback = null ){
        return this.add(new ClipTask('replace',{'id':id,},callback ) );
    }
    /**
     * @param {ClipData} id 
     * @param {Function} callback 
     * @returns {CoderServer}
     */
    remove(id = '', callback = null) {
        return this.add(new ClipTask('remove', {
            'id': id,
        }, callback));
    }
    /**
     * @param {ClipData} id 
     * @param {Function} callback 
     * @returns {CoderServer}
     */
    sort(id = '', slot = 0, callback = null) {
        return this.add(new ClipTask('sort', {
            'id': id,
            'slot': slot || 0,
        }, callback));
    }
}


/**
 * @type {Component}
 */
class Component {
    constructor() {
        this.__e = {};
    }
    /**
     * Register an event
     * @param {String} e 
     * @param {Function} call 
     * @returns {Component}
     */
    _(e = '', call = null) {
        if (e && typeof call === 'function') {
            if (!this.__e[e]) this.__e[e] = [];
            this.__e[e].push(typeof call === 'function' && call || (data => console.log(data)));
        }
        return this;
    }
    /**
     * Run an event. Now allowing many params
     * @param {String} e 
     * @param {*} args 
     * @returns 
     */
    $( ) {
        const args = Array.from(arguments).slice();
        const e = args.shift() || '';
        //console.log( `Event ${e}: `, args );
        e && this.__e[e] && this.__e[e].forEach(call => call( ... args ) );
        //old version with one single param
        //e && this.__e[e] && this.__e[e].forEach(call => call(args));
        return this;
    }
}


/**
 * Use to handle multiple uploads
 * events: update (progress) , upload( ClipData ) , done()
 */
class ClipQueue extends Component {
    /**
     * @param {String} id 
     */
    constructor(id = '') {
        super();
        this._id = id || '';
        this.reset(true);
    }
    /**
     * @returns {File[]}
     */
    items() { return this._items; }
    /**
     * @param {File[]} files 
     * @returns {ClipQueue}
     */
    send(files = []) {
        if (files && files.length && this.empty()) {
            const maxsize = App.server().maxfilesize();
            this._items = files.filter( file => file.size < maxsize);
            const missing = files.length - this.count();
            missing && App.notify( `${missing} files won't be uploaded`,'warning');
            this.reset().update();
        }
        return this;
    }
    /**
     * @param {Boolean} clear
     * @returns {ClipQueue}
     */
    reset(clear = false) {
        if (clear) {
            this._items = [];
        }
        this._header = '';
        this._total = this.count();
        return this;
    }
    /**
     * @returns {File}
     */
    next() { return this.items().shift() || null; }
    /**
     * @returns {Number}
     */
    count() { return this.items().length; }
    /**
     * @returns {Number}
     */
    total() { return this._total; }
    /**
     * @returns {Boolean}
     */
    empty() { return !this.count(); }
    /**
     * @returns {Number}
     */
    progress() {
        const t = this._total;
        return t && (t - this.count()) / parseFloat(t) * 100 || 0;
    }
    /**
     * @returns {CoderServer}
     */
    server() { return App.server(); }
    /**
     * @returns {Collection}
     */
    collection() { return App.collection() }
    /**
     * 
     * @returns {Uploader}
     */
    uploader() { return App.uploader() }
    /**
     * 
     */
    update() {
        if (this.count()) {
            const id = this._header || this._id;
            const file = this.next();
            file && this.server().upload(file, id, r => {
                this.deliver(r.items || []);
                this.$('update', this.progress());
                this.update();
            });
        }
        else {
            this.$('complete', this.total());
            this.reset(true);
        }
        return this;
    }
    /**
     * @param {Object[]} content
     * @returns {ClipQueue}
     */
    deliver(content = []) {
        console.log(content);
        const clips = ClipData.fromlist(content || []);
        if (clips.length) {
            if (!this._header) {
                //set the header
                this._header = clips[0].id();
            }
            this.$('upload', clips);
        }
        return this;
    }
}


/**
 * Subscribe to response and  done event calls 
 * all actions for hook {action: coder_clipboard}
 * {task: remove, id:string|string[]} can remove several selected items
 * {task: moveup, id:string|string[]} move up to the upper level one or more items (accepts array of ids)
 * {task: moveto, id:string|string[],parentid} move up to the new parent id (sibling in the same colleciton)
 * {task: sort, id: string , position: (1,...) } requests a sorting update to arrange the collection
 * @type {ClipTask}
 */
class ClipTask extends Component {
    /**
     * @param {String} task 
     * @param {Object} data
     * @param {Function} callback
     */
    constructor(task = '', data = {}, callback = null) {
        super();
        this._status = ClipTask.State.Ready;
        this._action = task || 'main';
        this._data = data || {};
        //this._callback = callback || null;
        this._response = {};
        this._('response', callback);
    }
    /**
     * @param {String} task 
     * @param {Object} data 
     * @param {Function} callback 
     * @returns {ClipTask}
     */
    static create(task = '', data = {}, callback = null) {
        return new ClipTask(task, data, callback);
    }
    /**
     * @returns {Uploader}
     */
    uploader() { return App.client().display().uploader(); }
    /**
     * @returns {Object}
     */
    response() { return this._response || {}; }
    /**
     * @returns {Boolean}
     */
    success() { return !!this.response()._response; }
    /**
     * Messsages
     * @returns {Object[]}
     */
    log() { return this.response()._log || []; }
    /**
     * @returns {CoderServer}
     */
    client() { return App.server(); }
    /**
     * @returns {String}
     */
    url() { return this.client().ajaxurl(); }
    /**
     * @returns {String}
     */
    nonce() { return this.client().nonce(); }
    /**
     * @returns {String}
     */
    state() { return this._status; }
    /**
     * @returns {Boolean}
     */
    ready() { return this.state() === ClipTask.State.Ready && this.valid(); }
    /**
     * @returns {Boolean}
     */
    running() { return this.state() === ClipTask.State.Running; }
    /**
     * @returns {Boolean}
     */
    finished() { return [ClipTask.State.Complete, ClipTask.State.Failed].includes(this.state()); }
    /**
     * @returns {Boolean}
     */
    completed() { return this.state() === ClipTask.State.Complete; }

    /**
     * @returns {String}
     */
    task() { return this._action; }
    /**
     * @returns {Object|File}
     */
    data() { return this._data; }
    /**
     * @returns {Boolean}
     */
    hasData() {
        return Object.keys(this.data()).length > 0;
    }

    /**
     * @returns {Boolean}
     */
    valid() { return !!this.task(); }
    /**
     * @param {Object} input 
     * @returns {FormData}
     */
    createForm(input = {}) {
        const form = new FormData();
        form.append('action', 'coder_clipboard');
        form.append('task', this.task());
        Object.keys(input).forEach(key => form.append(key, input[key]));
        return form;
    }
    /**
     * @returns {ClipboardContent}
     */
    request() {
        if (this.valid()) {
            this._status = ClipTask.State.Running;
            const content = this.createForm(this.data());
            try {
                fetch(this.url(), { method: 'POST', body: content })
                    .then(r => r.json())
                    .then(r => this.success(r))
                    .catch(error => this.failure(error));
            }
            catch (err) {
                this.failure(err);
            }
        }
        return this;
    }
    /**
     * @param {Object} response 
     */
    success(response = null) {
        if (response && response.success) {
            const action = response.data && response.data._action || '';
            console.log( `RESPONSE [${action}]`, response.data);
            this._response = response.data || {};
            this._status = ClipTask.State.Complete;
            this.$('response', this.response()).$('notify', this.log()).$('done', this);
        }
        else {
            this._status = ClipTask.State.Failed;
        }
        return this;
    }
    /**
     * @param {Object} error 
     */
    failure(error) {
        console.log('ERROR', error, this);
        this._status = ClipTask.State.Failed;
        this.$('notify', [{ content: error.toString(), type: 'error' }]).$('done', this);
        return this;
    }
}
/**
 * @type {ClipTask.Status}
 */
ClipTask.State = {
    Ready: 'ready',
    Running: 'running',
    Complete: 'complete',
    Failed: 'failed',
};
/**
 * Use to upload new contents 
 */
class UploadTask extends ClipTask {

    constructor(file = null, data = {}, callback) {
        super('upload', data, callback);
        this._attachment = file instanceof File && file || null;
    }
    /**
     * @returns {File}
     */
    attachment() { return this._attachment || null; }
    /**
     * @returns {Boolean}
     */
    hasAttachment() { return !!this.attachment(); }
    /**
     * @param {Object} input 
     * @returns {FormData}
     */
    createForm(input = {}) {
        const form = super.createForm(input);
        if (this.hasAttachment()) {
            form.append('upload', this.attachment());
        }
        return form;
    }
}
/**
 * Use to save the current gallery item's meta data
 */
class FormTask extends ClipTask {
    /**
     * @param {Object} content 
     * @param {Function} callback 
     */
    constructor(content = null, callback = null) {
        super('save',
            content instanceof ClipData ? content.content() : {},
            callback);
    }
}


/**
 * Use to portdata between the list and the server tasks
 * @type {ClipData}
 */
class ClipData extends Component {
    /**
     * @param {Object} data
     */
    constructor(data) {
        super();
        this._data = {
            'id': '',
            'parent_id': '',
            'name': 'new-clip',
            'title': '',
            'type': '',
            'description': '',
            'created': '',
            'tags': '',
            'path': {},
            'slot': 0,
            'items': 0,
        };
        this.populate(data)
        //this._id = id || '';
        //this._slot = 0;
        //this._parent_id = '';
        //this._name = 'new-clip';
        //this._type = '';
        //this._title = '';
        //this._description = '';
        //this._tags = [];
        //this._path = {};
        //this._items = 0;
    }
    /**
     * @returns {Object}
     */
    data() { return this._data; }
    /**
     * @returns {String[]}
     */
    attributes() { return Object.keys(this.data()) }
    /**
     * @param {String} name 
     * @param {String|Number|Boolean} value 
     * @returns {ClipData}
     */
    set(name = '', value = '') {
        if (this.data().hasOwnProperty(name)) {
            this.data()[name] = value;
        }
        return this;
    }
    /**
     * @param {String} name 
     * @returns {String}
     */
    get(name = '') { return name && this.data()[name] || ''; }
    /**
     * @param {String} name 
     * @returns {Number}
     */
    getint(name = '') { return parseInt(this.get(name)) || 0; }
    /**
     * @param {String} name 
     * @param {String} sep 
     * @returns {String[]}
     */
    getlist(name = '', sep = ',') {
        const value = this.get(name);
        return Array.isArray(value) ? value : value.split(sep);
    }
    /**
     * @param {String} name 
     * @returns {Object}
     */
    getobj(name = '') {
        const value = this.get(name);
        return value && typeof value === 'object' ? value : {};
    }
    /**
     * @param {Object} data 
     * @returns {ClipData}
     */
    populate(data = {}) {
        Object.keys(data || {}).forEach(key => {
            this.set(key, data[key]);
            /*const t = '_' + key;
            if (this.hasOwnProperty(t) && typeof this[t] !== 'function') {
                this[t] = data[key];
            }*/
        });
        return this;
    }
    /**
     * @param {Object} data 
     * @returns {ClipData}
     */
    static fromdata(data = null) {
        return new ClipData(data || {});
    }
    /**
     * @param {Object[]} list 
     * @returns {ClipData[]}
     */
    static fromlist(list = []) {
        return list && list.map(data => this.fromdata(data)) || [];
    }
    /**
     * @returns {Number}
     */
    count() { return this.getint('items'); }
    /**
     * @param {Object} content 
     * @returns {ClipData}
     */
    copy(content = {}) {
        typeof content === 'object' && Object.keys(content).forEach(key => {
            const value = content[key];
            this.set(key, Array.isArray(value) ? value.slice() : value);
        });
        return this;
    }
    clone() { return new ClipData().copy(this.data()); }
    /**
     * @returns {String}
     */
    link() { return App.server().clipurl(this.id()); }
    /**
     * @returns {String}
     */
    adminlink() { return `${App.server().adminurl()}&id=${this.id()}`; }

    /**
     * @returns {Boolean}
     */
    empty() { return !this.id(); }
    /**
     * @returns {Object}
     */
    content() {
        return {
            id: this.id(),
            name: this.name(),
            title: this.title() || this.name(),
            parent: this.parent(),
            slot: this.slot(),
        };
    }
    /**
     * @returns {ClipData}
     */
    update() {
        if (this.id()) {
            App.server().load(this.id(), r => this.copy(r.item || {}));
        }
        return this;
    }
    /**
     * @returns {FormTask}
     */
    save() {
        return new FormTask(this, task => {
            //handle in the Clipboard's form view
        });
    }
    /**
     * @returns {String}
     */
    type() { return this.get('type'); }
    /**
     * @returns {String}
     */
    name() { return this.get('name'); }
    /**
     * @returns {String}
     */
    title() { return this.get('title'); }
    /**
     * @returns {String}
     */
    desc() { return this.get('description'); }
    /**
     * @returns {String}
     */
    id() { return this.get('id'); }
    /**
     * @returns {String}
     */
    parent() { return this.get('parent_id'); }
    /**
     * @returns {String[]}
     */
    tags() { return this.getlist('tags', ' '); }
    /**
     * @returns {Number}
     */
    slot() { return this.getint('slot'); }
    /**
     * @returns {Object}
     */
    path() { return this.getobj('path'); }
    /**
     * @returns {String[]}
     */
    nodes() { return Object.keys(this.path() || {}); }
    /**
     * @returns {Object}
     */
    tree() {
        const path = {};
        this.nodes().forEach(id => path[id] = this.path()[id]);
        path[this.id()] = this.title() || this.name();
        return path;
    }



    /**
     * @param {String} data
     * @returns {String}
     */
    getMime = function (data) {
        var matches = data.match(/^data:(.*?);base64,/);
        return matches !== null ? matches[1] : 'text/plain';
    };
    /**
     * @param {String} base64String
     * @returns {Blob}
     */
    base64ToBlob = function (base64String) {
        const contentType = this.getMime(base64String);
        const data = base64String.replace(/^data:([A-Za-z-+\/]+);base64,/, '');
        var byteCharacters = atob(data);
        var byteArrays = [];

        for (var offset = 0; offset < byteCharacters.length; offset += 512) {
            const slice = byteCharacters.slice(offset, offset + 512);
            let byteNumbers = new Array(slice.length);
            for (let i = 0; i < slice.length; i++) {
                byteNumbers[i] = slice.charCodeAt(i);
            }
            byteArrays.push(new Uint8Array(byteNumbers));
        }
        return new Blob(byteArrays, { type: contentType });
    }
}


/**
 * Base View class
 */
class ViewComponent extends Component {
    /**
     * @param {String} name
     */
    constructor(name = '') {
        super();
        this._state = ViewComponent.State.New;
        this._node = this.selector(name);
        this.create();
    }
    /**
     * @returns {String[]}
     */
    rootnode() { return ['#wpbody-content', '.coders-clipboard'].slice(); }
    /**
     * @param {String} selector 
     * @returns {Element}
     */
    getnode( selector ){
        if( selector) {
            const path = this.rootnode();
            path.push(selector);
            return document.querySelector( path.join(' ') ) || null;
        }
        return null;
    }
    /**
     * @param {String} selector 
     * @returns {Element}
     */
    selector(selector = '') {
        if (selector) {
            const query = this.rootnode();
            query.push(selector);
            return document.querySelector(query.join(' ')) || null;
        }
        return null;
    }
    /**
     * Setup internal component data, before all components have been created
     */
    create() {
        //override
        this._components = [];
        this._state = ViewComponent.State.Created;
    }
    /**
     * Initialize component data, fetching and events once all components have been created
     */
    initialize() {
        //initialize view data and events
        if (!this._node) {
            this._node = this.render();
        }
        this._state = ViewComponent.State.Initialized;
        //console.log(`Initializing ${ this.constructor.name }`)
    }
    /**
     * @param {String} message 
     * @param {String} type 
     * @returns {ViewComponent}
     */
    notify(message = '', type = 'info') {
        App.notify(message, type);
        return this;
    }
    /**
     * @returns {Number}
     */
    state() { return this._state; }

    /**
     * @param {ViewComponent} c 
     * @returns {ViewComponent}
     */
    append(c = null) {
        if (c && c instanceof ViewComponent) {
            //console.log(this,c);
            !c.node() && c.initialize();
            this.components().push(c);
            if (this.node() && c.node()) {
                this.node().appendChild(c.node());
            }
            //append extra events such as remove
            c._('remove', c => this.drop(c));
        }
        return this;
    }
    /**
     * @returns {ViewComponent}
     */
    remove() {
        this.node() && this.node().remove();
        this._state = ViewComponent.State.Removed;
        this.$('remove', this);
        return this;
    }
    /**
     * @param {ViewComponent} component 
     * @returns {ViewComponent}
     */
    drop(component = null) {
        if (component instanceof ViewComponent) {
            this._components = this.components().filter(c => component !== c);
        }
        return this;
    }
    /**
     * @returns {ViewComponent}
     */
    update() {
        this._components = this.components().filter(c => c.state() !== ViewComponent.State.Removed);
        return this;
    }
    /**
     * @returns {ViewComponent[]}
     */
    components() { return this._components; }
    /**
     * @returns {Element}
     */
    node() { return this._node; }
    /**
     * @param {String} text 
     * @returns {String}
     */
    changetext(text = '') { return App.text(text); }
    /**
     * @returns {CoderServer}
     */
    api() { return App.server(); }
    /**
     * @param {String[]} path
     * @param {Object} params
     * @returns {String}
     */
    url(path = [], params = null) {
        const pairs = [];
        const url = this.api().publicurl() + path.join('/');
        Object.keys(params || {}).forEach(key => pairs.push(`${key}=${params[key]}`));
        return pairs.length ? `${url}?${pairs.join('&')}` : url;
    }
    /**
     * @returns {String}
     */
    adminurl() { return this.api().adminurl(); }
    /**
     * @returns {String}
     */
    nonce() { return this.api().nonce(); }


    /**
     * @param {String} type
     * @param {Object} attributes 
     * @param {*} content 
     * @returns {Element}
     */
    html(type = '', attributes = null, content = null) {
        const element = document.createElement(type);
        attributes instanceof Object && Object.keys(attributes).forEach(att => element.setAttribute(att, attributes[att]));
        if (content instanceof Element) {
            element.appendChild(content);
        }
        else {
            element.innerHTML = content || '';
        }
        return element;
    }
    /**
     * @returns {Element}
     */
    render() { return this.html('div', { 'class': 'empty' }, ''); }
    /**
     * @param {Object} request
     * @param {String|Element} content 
     * @param {String} className 
     * @param {String} target _self|_blank
     * @returns {Element}
     */
    link(request = {}, content, className = '', target = '_self') {
        const base = this.adminurl();
        const data = Object.keys(request)
            .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(request[key])}`);
        const url = data.length ? `${base}&${data.join('&')}` : base;
        return this.html('a', { 'class': className, 'href': url, 'target': target }, content || '');
    }
    /**
     * @param {String} action 
     * @param {Element|String} content 
     * @param {String} className 
     * @returns {Element}
     */
    action(action = '', content, className = '') {
        return this.link({ action: action }, content, className, '_self');
    }
}
/**
 * @type {ViewComponent.State|Number}
 */
ViewComponent.State = {
    New: 0,
    Created: 1,
    Initialized: 2,
    Removed: 3
};

/**
 * 
 */
class Collection extends ViewComponent {
    /**
     * @param {String} name 
     */
    constructor(name = '') {
        super(name);
    }
    /**
     * 
     */
    create() {
        super.create();
    }
    /**
     * 
     */
    initialize() {
        super.initialize();
        //prepare all events
        //this.onDragStart();
        this.onDragEnd();
        this.onDragOver();
        this.onDrop();
        //this.populate( );

        this._('sort', ( id , slot ) => {
            App.server().sort( id || '',  parseInt(slot) || 0, r => r.list && this.sortitems(r.list) );
            console.log('SORT',id,slot);
        });

        this._('move', ( content  ) => {
            App.server().moveto(content.id || '', content.target || '', r => this.removeitem(r.id || '') );
        });
    }
    /**
     * @param {String} id
     * @returns {Collection}
     */
    populate( id = '') {
        App.server().list( id || App.client().id(), (r) => {
            this.fill(ClipData.fromlist((r.items || [])));
        });
        return this;
    }
    /**
     * @returns {ClipView[]}
     */
    items() { return this.components(); }
    /**
     * @param {String} id 
     * @returns {Collection}
     */
    removeitem( id = '' ){
        const item = id && this.items().find( i => i.id() === id ) || null;
        //console.log('removing item ', item);
        item && item.remove();
        return this;
    }
    /**
     * @param {String[]} list 
     * @returns {Collection}
     */
    sortitems( list = []){
        console.log('SORT ITEMS',list);
        if( list && list.length ){
            const collection = this.node();
            list.map( id => this.getitem( id ) )
                .filter( (item, slot ) => !!item && item.setslot( slot + 1 ) )
                .sort( (a , b)  =>  a.slot() - b.slot() );
            //append back to collection node, this will move the real item's position  in the view actually
            this.items().forEach( item => collection.appendChild(item.node()));
            this.listslots();
        }
        return this;
    }
    /**
     * @param {Object} data
     * @returns {Collection}
     */
    sortitem( data = null){
        if( data ){
            const id = data.id || '';
            const slot = data.slot || 0;
            const item = id && this.items().find( i => i.id() === id ) || null;
            if(item){
                //move to slot
                console.log(`Moving ${item.name()} to slot ${slot}`);
            }
        }
        return  this;
    }
    /**
     * @returns {Number}
     */
    count() { return this.items().length }
    /**
     * @param {ClipData} clip 
     * @returns {Collection}
     */
    add(clip = null) {
        if (clip instanceof ClipData) {
            const item = new ClipView(clip);
            item._('remove', c => this.populate());
            this.append(item);

        }
        return this;
    }
    /**
     * @returns {ClipData}
     */
    clip() { return App.clipview(); }
    /**
     * @returns {Collection}
     */
    clear() {
        this.items().forEach(item => item.remove());
        return this;
    }
    /**
     * @param {ClipData[]} items 
     * @returns {Collection}
     */
    fill(items = []) {
        const current = App.client().id();
        const listed = this.listids();
        //add only items matching current parent_id and not present in tje list
        (items || [])
            .filter(item => !listed.includes(item.id()))
            .filter(item => item.parent() === current)
            .forEach(data => this.add(data));

        this.$('refresh', this.count());
        return this;
    }
    /**
     * @returns {String[]}
     */
    listids() {
        return this.components()
            .filter(c => !!c.id)
            .map(c => c.id());
    }
    /**
     * 
     * @param {String} id 
     * @returns {ClipView}
     */
    getitem(id = '') { return this.components().find(c => c.id() === id) || null; }
    /**
     * @param {String} id 
     * @param {String} target 
     * @returns {Collection}
     */
    move(id = '', target = '') {
        App.server().moveto(id, target, r => {
            const id = r.id || '';
            const item = this.items().find(item => item.id() === id) || null;
            console.log('removing item ', item);
            item && item.remove();
        });
        return this;
    }
    /**
     * @returns {Collection}
     */
    onDragEnd() {
        document.addEventListener('dragend', e => {
            e.preventDefault();
            const node = this.node();
            if( node ){
                node.classList.remove('move');
                const source = node.querySelector('li.item.moving');
                source.classList.remove('moving');
            }
        });
        return this;
    }
    /**
     * @returns {Collection}
     */
    onDragStart() {
        const node = this.node();
        node && node.addEventListener('dragstart', e => {
            /*e.preventDefault();*/
            const item = e.target.closest('li.item');
            if (!item || !node.contains(item)) return;
            console.log(node,e.target,e.target.closest('li.item'));

            node.classList.add('move');
            item.classList.add('moving');

            const id = item.dataset.id;
            const slot = item.dataset.slot;
            //console.log(item,id,slot);

            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData(
                'application/json',
                JSON.stringify({ 'id': id, 'slot': slot }));
            // Create a custom drag image
            const ghost = this.createGhost(item.querySelector('img.media'));
            // Wait for the browser to render the ghost before setting it as the drag image
            if( ghost ){
                e.dataTransfer.setDragImage(ghost, 0, 0);
                // Optional cleanup
                setTimeout(() => ghost.remove(), 1000);
            }

        });
        return this;
    }
    /**
     * @param {Element} image 
     * @returns {Collection}
     */
    createGhost(image = null) {
        if (image instanceof Element) {
            const ghost = image.cloneNode(true);
            ghost.style.borderRadius = '50%';
            ghost.style.position = 'absolute';
            ghost.style.top = '-1000px';
            ghost.style.left = '-1000px';
            ghost.style.zIndex = '-1'; // avoid blocking other elements
            ghost.style.pointerEvents = 'none';
            document.body.appendChild(ghost);
            return ghost;
        }
        return null;
    }
    /**
     * @returns {Collection}
     */
    onDragOver() {
        this.node() && this.node().addEventListener('dragover', (e) => e.preventDefault());
        return this;
    }
    /**
     * @returns {Collection}
     */
    onDrop() {
        const content = this.node();
        console.log(content);
        content && content.addEventListener('drop', e => {
            e.preventDefault();
            const target = e.target;
            const item = target.closest('li.item');
            const data = JSON.parse(e.dataTransfer.getData('application/json') || '{}');
            const fromid = data.id || '';
            const fromslot = parseInt(data.slot) || 0;
            const action = target.classList.contains('placeholder') && 'sort'
                || target.classList.contains('cover') && 'move' || '';
            //console.log(data,action);

            switch (action) {
                case 'move':
                    const toid = item && item.dataset.id || '';
                    fromid !== toid && this.$('move', {'id':fromid,'target':toid});
                    break;
                case 'sort':
                    const toslot = item && parseInt(item.dataset.slot) || 0;
                    console.log(item,toslot,fromslot);
                    toslot && toslot !== fromslot && this.$('sort', fromid, toslot );
                    break;
                default:
                    //console.log(`No target selected`);
                    break;
            }
        });
        return this;
    }
    listslots(){
        this.items().forEach( item => console.log (item.id(),item.slot()) );
    }
}
/**
 * 
 */
class Notifier extends ViewComponent {
    /**
     * @param {String} name 
     */
    constructor(name = '') {
        super(name);
    }
    /**
     * 
     */
    initialize() {
        super.initialize();
    }
    /**
     * @param {String} content 
     * @param {String} type 
     * @returns {Notifier}
     */
    show(content = '', type = 'info') {
        console.log(`[${type}] ${content}`);
        if (content && this.node()) {
            this.node().appendChild(this.newmessage(content, type, App.client().timeout()));
        }
        return this;
    }
    /**
     * @param {String} content 
     * @param {String} type 
     * @param {Boolean} timeout 
     * @returns {Element}
     */
    newmessage(content = '', type = 'info', timeout = false) {
        const message = this.html('div', { 'class': `is-dismissible notice type-${type}` }, content);
        message.addEventListener('click', function (e) {
            e.preventDefault();
            this.remove();
            return true;
        });
        timeout && window.setTimeout(() => message.remove(), 4000);
        return message;
    }
}

/**
 * Use this new model instead ClipData
 */
class ClipView extends ViewComponent {
    /**
     * @param {ClipData} data
     */
    constructor(data = null) {
        super();
        this._data = data && data instanceof ClipData ? data : null;
    }
    create() {
        super.create();
    }
    initialize() {
        super.initialize();
        this.onDragStart();
    }
    /**
     * @returns {Collection}
     */
    onDragStart() {
        const node = this.node();
        node && node.addEventListener('dragstart', e => {
            /*e.preventDefault();*/
            const item = e.target.closest('li.item');
            if (!item || !node.contains(item)) return;
            console.log(node,e.target,e.target.closest('li.item'));

            node.classList.add('move');
            item.classList.add('moving');

            const id = item.dataset.id;
            const slot = item.dataset.slot;
            //console.log(item,id,slot);

            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData(
                'application/json',
                JSON.stringify({ 'id': id, 'slot': slot }));
            // Create a custom drag image
            const ghost = this.createGhost(item.querySelector('img.media'));
            // Wait for the browser to render the ghost before setting it as the drag image
            if( ghost ){
                e.dataTransfer.setDragImage(ghost, 0, 0);
                // Optional cleanup
                setTimeout(() => ghost.remove(), 1000);
            }

        });
    }
    /**
     * @param {Element} image 
     * @returns {Collection}
     */
    createGhost(image = null) {
        if (image instanceof Element) {
            const ghost = image.cloneNode(true);
            ghost.style.borderRadius = '50%';
            ghost.style.position = 'absolute';
            ghost.style.top = '-1000px';
            ghost.style.left = '-1000px';
            ghost.style.zIndex = '-1'; // avoid blocking other elements
            ghost.style.pointerEvents = 'none';
            document.body.appendChild(ghost);
            return ghost;
        }
        return null;
    }
    /**
     * @returns {ClipData}
     */
    data() { return this._data; }
    /**
     * @returns {String}
     */
    id() { return this.data() && this.data().id() || ''; }
    /**
     * @returns {String}
     */
    slot() { return this.data() && this.data().slot() || 0; }
    /**
     * @param {Number} slot
     * @returns {ClipView} 
     */
    setslot( slot = 0 ){
        if( slot && this.data() ){
            this.data().set('slot',slot);
            this.$('slot',slot);
        }
        return this;
    }
    /**
     * @returns {Boolean}
     */
    isimage() { return this.type().indexOf('image/') >= 0; }
    /**
     * @returns {Boolean}
     */
    isattachment() { return this.type().indexOf('text/') >= 0; }
    /**
     * @returns {Boolean}
     */
    isvideo() { return this.type().indexOf('video/') >= 0; }
    /**
     * @returns {Boolean}
     */
    ismedia() { return this.isimage() || this.isvideo(); }
    /**
     * @returns {String}
     */
    type() { return this.data() && this.data().type() || ''; }
    /**
     * @returns {Boolean}
     */
    parent() { return this.data() && this.data().parent() || ''; }
    /**
     * @returns {Number}
     */
    counter() { return this.data() && this.data().count() || 0; }

    /**
     * @returns {Boolean}
     */
    empty() { return !this.data(); }
    /**
     * @param {String} id 
     * @returns {ClipView}
     */
    clipurl(id = '') { return this.api().clipurl(id); }
    /**
     * @returns {String}
     */
    clipboardurl() { return `${this.public()}/clipboard/${this.id()}`; }
    /**
     * @returns {String}
     */
    adminurl() { return `${this.url()}?page=coder_clipboard&id=${this.id()}`; }

    /**
     * @returns {Element}
     */
    render() {
        //console.log(this.parent());
        const item = this.html('li', {
             'class': 'item',
             'data-id': this.id(),
             'data-slot': this.slot(),
             'draggable': 'true',
        });
        
        const placeholder = this.makeplaceholder();
        this._('slot', slot => {
            slot && item.setAttribute('data-slot',slot );
            slot && placeholder.setAttribute('data-slot',slot );
        });
        
        item.appendChild(placeholder);
        item.appendChild(this.makecontent());
        //item.appendChild(this.makecheck());
        item.appendChild(this.makereplace());
        this.parent() && item.appendChild(this.makemoveup()); //only in lower levels
        this.counter() && item.appendChild(this.makecount());
        item.appendChild(this.makeremove());
        return item;
    }
    /**
     * @returns {Element}
     */
    makeplaceholder() { return this.html('span', { 'class': 'placeholder', 'data-slot': this.slot() }); }
    /**
     * @returns {Element}
     */
    makecheck() {
        const selector = `select_${this.id()}`;
        const element = this.html('label', {
            'class': 'top-left task select',
            'for': selector
        });
        element.appendChild(this.html('input', {
            'type': 'checkbox',
            'id': selector,
            'value': this.id(),
        }));
        //add dashicons contents
        return element;
    }
    /**
     * @returns {Element}
     */
    makecount() {
        const element = this.html('span', { 'class': 'task counter visible bottom-left' }, this.counter());
        return element;
    }
    /**
     * @returns {Element}
     */
    makereplace() {
        const element = this.html('span', { 'class': 'task top-right dashicons dashicons-arrow-right-alt' });
        element.addEventListener('click', e => {
            e.preventDefault();
            this.id() && App.server().replace(this.id(), r => {
                const url = r.id && `${App.server().adminurl()}&id=${r.id}` || App.server().adminurl();
                location.href = url;
                //App.client().load(r.id || '');
            });
            return true;
        });

        return element;
    }
    /**
     * @returns {Element}
     */
    makemoveup() {
        const element = this.html('span', { 'class': 'task top-left dashicons dashicons-arrow-up-alt' });
        element.addEventListener('click', e => {
            e.preventDefault();
            this.id() && App.server().moveup(this.id(), r => {
                //remove from active view
                this.remove();
                //r.id && this.id() === r.id && this.remove();
            });
            return true;
        });

        return element;
    }
    /**
     * @returns {Element}
     */
    makeremove() {
        const element = this.html('span', {
            'class': 'task bottom-right dashicons dashicons-remove',
        });
        element.addEventListener('click', e => {
            e.preventDefault();
            this.id() && App.server().remove(this.id(), r => {
                r.id && this.id() === r.id && this.remove();
            });
            return true;
        });
        //add actions
        return element;
    }
    /**
     * @returns {Element}
     */
    makecontent() {
        const data = this.data();
        const content = this.html('div', {
            'class': 'content',
            //'draggable': 'true'
        });
        if (data) {
            switch (true) {
                case this.isimage():
                    content.appendChild(this.makeimage());
                    break;
                default:
                    content.appendChild(this.makeattachment());
                    break;
            }
            content.appendChild(this.html('a', {
                'class': 'cover',
                'data-id': data.id(),
                'href': data.adminlink()
            }, data.title()));
        }
        return content;
    }
    /**
     * @returns {Element}
     */
    makeattachment() {
        const d = this.data();
        return d && this.html('span', {
            'class': 'dashicons attachment dashicons-media-document' + d.tags().join(' ')
        }) || super.render();
    }
    /**
     * @returns {Element}
     */
    makeimage() {
        const d = this.data();
        return d && this.html('img', {
            'src': d.link(),
            'alt': d.name(),
            'title': d.title(),
            'class': 'media ' + d.tags().join(' ')
        }) || super.render();
    }
}


/**
 * 
 */
class ClipFormView extends ViewComponent {
    /**
     * @param {String} name 
     */
    constructor(name = '') {
        super(name);
        this.clear();
    }
    create() {
        super.create();
        this.setupTabs();
    }
    /**
     * 
     */
    initialize() {
        //this.load(App.client().id());
        this.onChangeName();
        this.setplaceholder( this.nameinput() && this.nameinput().value || 'Your clip' );
    }
    /**
     * @returns {Element}
     */
    nameinput(){ return this.node() && this.node().querySelector('input.name') || null; }
    /**
     * 
     */
    onChangeName(){
        const name = this.nameinput();
        name && name.addEventListener('input', e => {
                e.preventDefault();
                this.setplaceholder(name.value);
                return true;
            });
    }
    /**
     * @param {String} text 
     * @returns {ClipFormView}
     */
    setplaceholder( text = ''){
        const title = this.node() && this.node().querySelector('input.title') || null;
        title && title.setAttribute('placeholder',text || 'your clip' );
        return this;
    }
    /**
     * @returns {ClipFormView}
     */
    setupTabs() {
        const element = this.node();
        if (element) {
            const toggle = element.querySelector('.tab > .toggle');
            toggle && toggle.addEventListener('click', function (e) {
                e.preventDefault();
                (this).parentNode.classList.toggle('collapsed');
                return true;
            });
        }
        return this;
    }

    /**
     * @param {String} id
     * @returns {ClipFormView}
     */
    load(id = '') {
        this._clip = new ClipData(id && { 'id': id } || {});
        if (this.clip()) {
            this.clip().update();
            //refresh all data changes when the clip geets updated
            this.clip()._('update', clip => { this.update(clip, false); });
            //fire clip data event
            this.$('load', this.clip());
        }
        return this;
    }
    /**
     * @param {ClipData} clip 
     * @returns {ClipFormView}
     */
    update(clip = null, runevent = false) {
        if (clip instanceof ClipData) {
            this._clip = this.clip() ? this.clip().copy(clip.data()) : clip;
            runevent && this.$('refresh', this.clip());
            //fill fporm data here
            //console.log('Form Data ', clip);
        }
        return this;
    }

    /**
     * @returns {ClipData}
     */
    clip() { return this._clip; }
    /**
     * @returns {ClipFormView}
     */
    clear() {
        this._clip = null;
        return this;
    }
}
/**
 * Manage tasks and uploads, update the progress with a progress bar
 */
class Uploader extends ViewComponent {
    /**
     * @param {String} name 
     */
    constructor(name = '') {
        super(name);
        this._dragcounter = 0;
    }
    /**
     * 
     */
    create() {
        super.create();
        this._queue = new ClipQueue(App.client().id());
    }
    /**
     * 
     */
    initialize() {
        this.setupFileInput();
        this.onPaste();
        this.onDragDrop();
        this.queue()._('update', progress => this.progress(progress));
        this.queue()._('upload', clips => this.collection().fill(clips));
        this.queue()._('complete', completed => this.clear());
    }
    /**
     * @returns {ClipQueue}
     */
    queue() { return this._queue; }
    /**
     * @returns {Element}
     */
    inputfile() { return this.node() && this.node().querySelector('input[type=file]') || null; }
    /**
     * @returns {Element}
     */
    uploadbutton() { return this.node() && this.node().querySelector('button.upload') || null; }
    /**
     * @returns {Element}
     */
    clipboard() {
        const clipboard = this.rootnode().join(' ') + '> .clipboard';
        return document.querySelector(clipboard);
    }
    /**
     * @param {String} text
     * @returns {Element}
     */
    changetext(text = '') {
        const element = this.node() && this.node().querySelector('.files > span.status') || null;
        if (text && element) {
            element.innerHTML = text;
        }
        return this;
    }
    /**
     * @returns {Boolean}
     */
    useajax() { return this.node() && this.node().classList.contains('ajax') || false; }
    /**
     * @returns {Boolean}
     */
    toggle() {
        this.node() && this.node().classList.toggle('ajax');
        const active = this.useajax();
        this.changetext(App.text(active && 'Drop your files here' || 'Select your files'));
        return active;
    }
    /**
     * @returns {Uploader}
     */
    setupFileInput() {
        const input = this.inputfile();
        const id = App.client().id();
        input && input.addEventListener('change', e => {
            //e.preventDefault();
            this.useajax() && this.enqueue(e.target.files, id);
            return true;
        });
        return this;
    }
    /**
     * @returns {Element}
     */
    gaugebar() { return this.node().querySelector('.gauge'); }
    /**
     * @returns {Element}
     */
    progressbar() { return this.gaugebar().querySelector('.progress'); }
    /**
     * @returns {Element}
     */
    textbar() { return this.gaugebar().querySelector('.status'); }
    /**
     * @param {Number} progress 
     * @returns {Uploader}
     */
    progress(progress = 0) {
        const gauge = this.gaugebar();
        const bar = this.progressbar();
        const text = this.textbar();
        console.log(gauge, progress);
        if (gauge) {
            !this.isactive() && gauge.classList.add('active');
            const count = Math.floor(Math.min(progress, 100));
            bar.style.width = `${count}%`;
            text.innerHTML = `${count} %`;
        }
        return this;
    }
    /**
     * @returns {Boolean}
     */
    isactive() { return this.gaugebar() && this.gaugebar().classList.contains('active') || false; }
    /**
     * @param {Number} count
     * @returns {Uploader}
     */
    clear(count = 0) {
        if (this.isactive()) {
            const text = this.textbar();
            if (text && count) {
                text.innerHTML = `${count} new clips uploaded!`, 'update';
            }
            window.setTimeout(() => {
                this.gaugebar().classList.remove('active');
            }, 4000);
        }
        return this;
    }
    /**
     * @returns {Collection}
     */
    collection() { return App.collection(); }
    /**
     * @param {File[]} files remove
     * @returns {Uploader}
     */
    enqueue(files = []) {
        //console.log('Uploading: ', files);
        this.queue().send(Array.from(files));
        return this;
    }
    /**
     * @returns {Uploader}
     */
    over() {
        if (!this._dragcounter) {
            const cb = this.clipboard();
            cb && cb.classList.add('drag-content');
        }
        this._dragcounter++;
        return this;
    }
    /**
     * @param {Boolean} reset
     * @returns {Uploader}
     */
    leave(reset = false) {
        if (this._dragcounter) {
            if (reset) {
                this._dragcounter = 0;
            }
            else {
                this._dragcounter--;
            }
            if (!this._dragcounter) {
                const cb = this.clipboard();
                cb && cb.classList.remove('drag-content');
            }
        }
        return this;
    }
    /**
     * @returns {Uploader}
     */
    onDragDrop() {
        // Drag-drop
        document.addEventListener('dragover', e => e.preventDefault());
        document.addEventListener('dragenter', e => {
            if (![...e.dataTransfer.types].includes('Files')) return;
            this.over();
        });
        document.addEventListener('dragleave', e => {
            this.leave();
        });
        document.addEventListener('drop', e => {
            e.preventDefault();
            if (e.dataTransfer.files.length) {
                this.enqueue(e.dataTransfer.files);
            }
            this.leave(true);
        });
        return this;
    }
    /**
     * @returns {Uploader}
     */
    onPaste() {
        // Paste
        document.addEventListener('paste', e => {
            const items = e.clipboardData.files.length ?
                e.clipboardData.files :
                (e.clipboardData.items || [])
                    .filter(item => item.kind === 'file')
                    .map(item => item.getAsFile() || null)
                    .filter(item => item !== null)
            this.enqueue(items);
        });
        return this;
    }
}
/**
 * Component to build the gallery navigation hierarchy at the top
 */
class NavigatorView extends ViewComponent {
    /**
     * @param {String} name 
     */
    constructor(name = '') {
        super(name);
        this._path = {};
    }
    /**
     * 
     */
    create() {
        super.create();
    }
    /**
     * 
     */
    initialize() {
        super.initialize();
        this.onCopy();
        const clipview = this.clipview();
        clipview._('load', clip => clip && this.pathto(clip.path()));
    }

    /**
     * @returns {ClipFormView}
     */
    clipview() { return App.clipview(); }
    /**
     * @param {Object} path 
     * @returns {NavigatorView}
     */
    pathto(path = {}) {
        if (path instanceof Object) {
            this._path = path;
        }
        return this;
    }
    /**
     * @returns {Object}
     */
    path() { return this._path; }
    /**
     * @returns {String[]}
     */
    list() { return Object.keys(this.path()); }
    /**
     * @returns {Element}
     */
    nav() { return this.node(); }
    /**
     * @returns {NavigatorView}
     */
    clear() {
        this.nav().innerHTML = '';
        return this;
    }

    /**
     * @returns {Element}
     */
    copylink() { return this.node() && this.node().querySelector('.copy-link') || null; }
    /**
     * @returns {Uploader}
     */
    onCopy() {
        const copylink = this.copylink();
        if (copylink) {
            const _self = this;
            copylink.addEventListener('click', function (e) {
                e.preventDefault();
                const link = this.dataset.link || '';
                console.log(copylink, link);
                if (link) {
                    navigator.clipboard.writeText(link)
                        .then(e => {
                            _self.notify('URL copied to clipboard!', 'updated');
                        })
                        .catch(err => {
                            _self.notify('Failed to copy: ', err);
                        });
                }
                return true;
            });
        }
        return this;
    }
}
/**
 * Selective actions for the collection view
 */
class Toolbar extends ViewComponent {
    /**
     * @param {String} name 
     */
    constructor(name = '') {
        super(name);
        this._total = 0;
        this._count = 0;
    }
    /**
     * 
     */
    initialize() {
        super.initialize();
        App.collection()._('refresh', count =>  this.count(count) );
        //App.server().count( r => App.tools().total(r.count || 0));
    }
    /**
     * @param {Number} total 
     * @returns {Toolbar}
     */
    total( total = 0){
        this._total = total;
        return this.refresh();
    } 
    /**
     * @param {Number} count 
     * @returns {Toolbar}
     */
    count( count = 0 ){
        this._count = count;
        return this.refresh();
    }
    /**
     * @returns {Element}
     */
    totalbar(){ return this.node() && this.node().querySelector('.total') || null; }
    /**
     * @returns {Element}
     */
    countbar() { return this.node() && this.node().querySelector('.counter') || null; }
    /**
     * @returns {Toolbar}
     */
    refresh() {
        const counter = this.countbar();
        //const total = this.totalbar();
        if (counter) {
            counter.innerHTML = this._count;
        }
        //if(total && this._total){
        //    total.classList.remove('hide');
        //    total.innerHTML = this._total;
        //}
        return this;
    }
}
/**
 * 
 */
class ToggleAjax extends ViewComponent {
    /**
     * @param {String} selector 
     */
    constructor(selector = '') {
        super(selector);
    }
    /**
     * @returns {Element}
     */
    total(){ return this.getnode('.options .info .total'); }
    /**
     * @param {Number} count 
     * @returns {ToggleAjax}
     */
    updatetotal( count = 0){
        const total = this.total();
        if( total && count ){
            total.innerHTML = count;
        }
        return this;
    }
    create() {
        super.create();
    }
    initialize() {
        super.initialize();
        this.node() && this.node().addEventListener('click', e => {
            e.preventDefault();
            App.uploader().toggle();
            return true;
        });
        App.server().count( r => this.updatetotal(r.count || 0) );
    }
}


/**
 * Text parser
 */
class CoderStrings {
    /**
     * 
     */
    constructor() {
        this._strings = {};
    }
    /**
     * @param {*} content 
     */
    fill(content = null) {
        Object.keys(content instanceof Object && content || {})
            //.filter()
            .forEach(key => this._strings[key] = content[key]);
    }
    /**
     * @param {String} text 
     * @returns {String}
     */
    get(text) { return text && this._strings[text] || text; }
}

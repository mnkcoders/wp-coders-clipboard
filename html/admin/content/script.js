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
document.addEventListener('DOMContentLoaded', function () {
    App.instance();
});


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
        this._id = this.input().get('id') || '';
        //this._clip = id && new ClipData(id) || null;
        this._components = {};

        this.setup().initialize();
        //console.log(this);
    }
    /**
     * @returns {CoderInput}
     */
    input() { return this._input; }
    /***
     * @returns {App}
     */
    setup() {
        //console.log('Setup Components:' , this.components());
        return this.register(new CoderClient())
            .register(new CoderDrive())
            .register(new CoderStrings())
            .register(new ClipFormView('div.content'))
            .register(new Notifier('div.notifier'))
            .register(new Collection('ul.collection'))
            .register(new Uploader('div.upload'))
            .register(new Toolbar('ul.tools'))
            .register(new NavigatorView('ul.path'));
    }
    /***
     * @returns {App}
     */
    initialize() {
        //initialize views
        console.log('Initialize Components:', this.components());
        this.components()
            .map(c => this._components[c])
            .filter(c => c instanceof ViewComponent)
            .forEach(c => c.initialize());
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
     * @returns {ClipData}
     */
    clip() { return this.id() && new ClipData(this.id()) || null; }
    /**
     * @returns {String}
     */
    id() { return this._id; }


    /**
     * @returns {App}
     */
    static instance() { return App.__instance || new App(); }

    /**
     * @param {String} message 
     * @param {String} type 
     * @returns {ClipboardView}
     */
    static notify(message, type = 'info') {
        const log = this.instance().component(Notifier.name);
        log && log.show(message, type);
        return this;
    }
    /**
     * @param {String} text 
     * @returns {String}
     */
    static text(text = '') {
        const strings = this.instance().component(CoderStrings.name);
        return strings && strings.get(text) || text;
    }


    /**
     * @returns {CoderClient}
     */
    static server() { return this.instance().component(CoderClient.name); }
    /**
     * @returns {Object}
     */
    static api() { return this.server().api(); }
    /**
     * @returns {ClipFormView}
     */
    static clipview() { return this.instance().component(ClipFormView.name); }
    /**
     * @returns {Collection}
     */
    static collection() { return this.instance().component(Collection.name); }
    /**
     * @returns {NavigatorView}
     */
    static nav() { return this.instance().component(NavigatorView.name); }
    /**
     * @returns {Toolbar}
     */
    static tools() { return this.instance().component(Toolbar.name); }
    /**
     * @returns {Uploader}
     */
    static uploader() { return this.instance().component(Uploader.name); }
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
 * @type {Component}
 */
class Component {
    constructor() {
        this._e = {};
    }
    /**
     * Register an event
     * @param {String} e 
     * @param {Function} call 
     * @returns {Component}
     */
    _(e = '', call = null) {
        if (e && typeof call === 'function') {
            if (!this._e[e]) this._e[e] = [];
            this._e[e].push(typeof call === 'function' && call || (data => console.log(data)));
        }
        return this;
    }
    /**
     * Run an event
     * @param {String} e 
     * @param {*} data 
     * @returns 
     */
    $(e = '', data = false) {
        e && this._e[e] && this._e[e].forEach(call => call(data));
        return this;
    }
}


/**
 * Events: update , done
 */
class CoderClient extends Component {

    constructor() {
        super();
        this._api = this.apidata();
        this._tasks = [];
    }
    /**
     * @returns {Object}
     */
    apidata() {
        const data = { public: '', admin: '', ajax: ajaxurl, nonce: '' };
        const api = CodersAPI || {};
        Object.keys(data).forEach(key => data[key] = api[key] || '');
        return data;
    }
    /**
     * @returns {Object}
     */
    api() { return this._api; }
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
     * @returns {ClipTask[]}
     */
    tasks(active = false) { return active ? this.tasks().filter(t => !t.finished()) : this._tasks; }
    /**
     * @param {ClipTask} task 
     * @returns {Uploader}
     */
    add(task = null) {
        if (task instanceof ClipTask) {
            const start = !this.count();
            this.tasks().push(task);
            //handle the loop here
            task._('done', task => this.update());
            //then start the loop if first in the list required
            start && this.update();
        }
        return this;
    }
    /**
     * @param {Boolean} active
     * @returns {Number}
     */
    count(active = false) { return this.tasks(active).length; }
    /**
     * @returns {Boolean}
     */
    running() { return !!this.count(true); }
    /**
     * @returns {Boolean}
     */
    done() { return !this.running(); }
    /**
     * @returns {CoderClient}
     */
    clear() {
        this._tasks = [];
        return this;
    }
    /**
     * Subscribe to update and done events to fetch the results here
     * @returns {Boolean}
     */
    update() {
        const task = this.tasks(true)[0] || null;
        task && task.request();

        if (this.running()) {
            //keep looping through. CAll the update event for all buscribers
            this.$('update', this.progress());
            return true;
        }
        //finish and report if required
        const completed = this.tasks().filter(t => t.completed());
        //notify the completed loop
        this.$('done', completed).clear();
        return false;
    }
    /**
     * @returns {Number} %
     */
    progress() {
        const count = this.tasks().length;
        const progress = count && this.tasks(true) / parseFloat(count) || 0;
        return Math.floor(progress * 100);
    }


    /// define all task creators
    /**
     * list and populate all items in the clipboard
     * @param {String} id 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    list(id = '', callback = nul) {
        return this.add(new ClipTask('list', id && { 'id': id } || {}, callback));
    }
    /**
     * @param {String} id 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    load(id = '', callback = null) {
        return this.add(new ClipTask('load', { 'id': id }, callback));
    }
    /**
     * @param {File} file 
     * @param {String} id 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    upload(file = null, id = '', callback = null) {
        if (file instanceof File) {
            this.add(new UploadTask(file, id && { 'id': id } || {}, callback));
        }
        return this;
    };
    /**
     * @param {File[]} files 
     * @returns {ClipQueue}
     */
    queue(files = []) { return new ClipQueue(files, App.instance().id()); }
    /**
     * save clipdata
     * @param {ClipData} data 
     * @param {Function} callback 
     * @returns {CoderClient}
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
     * @returns {CoderClient}
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
     * @returns {CoderClient}
     */
    moveto(id = '', target = '', callback = null) {
        return this.add(new ClipTask('moveto', {
            'id': id,
            'target': target,
        }, callback));
    }
    /**
     * @param {ClipData} id 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    remove(id = '', callback = null) {
        return this.add(new ClipTask('remove', {
            'id': id,
        }, callback));
    }
    /**
     * @param {ClipData} id 
     * @param {Function} callback 
     * @returns {CoderClient}
     */
    sort(id = '', slot = 0, callback = null) {
        return this.add(new ClipTask('sort', {
            'id': id,
            'slot': slot || 0,
        }, callback));
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
    upload(files = []) {
        if (files && files.length && this.empty()) {
            this._items = files;
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
     * @returns {CoderClient}
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
 * handle storage selection operations
 */
class CoderDrive {
    /**
     * @param {String} storage 
     */
    constructor(storage = '') {
        this._storage = storage || '';
    }
    /**
     * @returns {String}
     */
    storage() { return this._storage; }
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
        this._status = ClipTask.Status.Ready;
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
    uploader() { return App.instance().display().uploader(); }
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
     * @returns {CoderClient}
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
    status() { return this._status; }
    /**
     * @returns {Boolean}
     */
    ready() { return this.status() === ClipTask.Status.Ready && this.valid(); }
    /**
     * @returns {Boolean}
     */
    running() { return this.status() === ClipTask.Status.Running; }
    /**
     * @returns {Boolean}
     */
    finished() { return [ClipTask.Status.Complete, ClipTask.Status.Failed].includes(this.status()); }
    /**
     * @returns {Boolean}
     */
    completed() { return this.status() === ClipTask.Status.Complete; }

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
    valid() {
        return !!this.task();
    }
    /**
     * @returns {ClipboardContent}
     */
    request() {
        if (this.valid()) {
            this._status = ClipTask.Status.Running;
            const content = this.createForm(this.data());
            //console.log(`Sending ${this.url()}`);
            fetch(this.url(), { method: 'POST', body: content })
                .then(r => r.json())
                .then(r => this.success(r))
                .catch(error => this.failure(error));
        }
        return this;
    }
    /**
     * @param {Object} response 
     */
    success(response = null) {
        if (response && response.success) {
            console.log('RESPONSE', response.data);
            this._response = response.data || {};
            this._status = ClipTask.Status.Complete;
            this.$('response', this.response());
            this.$('done', this);
        }
        else {
            this._status = ClipTask.Status.Failed;
        }
        return this;
    }
    /**
     * @param {Object} error 
     */
    failure(error) {
        console.log('Task Error', error, this);
        this._status = ClipTask.Status.Failed;
        this.$('done', this);
        return this;
    }
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
}
/**
 * @type {ClipTask.Status}
 */
ClipTask.Status = {
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
     * 
     * @param {String} id 
     * @param {String} parent 
     * @param {String} name 
     * @param {String} type
     * @param {String} title 
     * @param {String} desc
     * @param {Number} slot 
     */
    constructor(id = '', parent = '', name = '', type = '', title = '', desc = '', slot = 0) {
        super();
        this._id = id || '';
        this._slot = slot || 0;
        this._parent = parent || '';
        this._name = name || 'new-clip';
        this._type = type || '';
        this._title = title || this.name();
        this._desc = desc || '';
        this._tags = [];
        this._path = {};
    }
    /**
     * @param {Object} data 
     * @returns {ClipData}
     */
    static fromdata(data = null) {
        return data && new ClipData(
            data.id || '',
            data.parent_id || '',
            data.name || '',
            data.type || '',
            data.title || '',
            data.desc || '',
            data.slot || '',
        ) || null;
    }
    /**
     * @param {Object[]} list 
     * @returns {ClipData[]}
     */
    static fromlist(list = []) {
        list && list.map(data => this.fromdata(data)) || [];
    }
    /**
     * 
     * @param {ClipData} clip 
     * @returns {ClipData}
     */
    copy(clip = null) {
        clip && Object.keys(this).forEach(key => {
            if (key !== '_id' || !this.id()) {
                this[key] = clip[key];
            }
        });
        return this;
    }
    /**
     * @returns {String}
     */
    link() { return App.server().clipurl(this.id()); }

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
    refresh() {
        if (this.id()) {
            App.server().load(this.id(), r => {
                this.copy(ClipData.fromdata(r.item || null));
            });
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
    type() { return this._type; }
    /**
     * @returns {String}
     */
    name() { return this._name; }
    /**
     * @returns {String}
     */
    title() { return this._title; }
    /**
     * @returns {String}
     */
    desc() { return this._desc; }
    /**
     * @returns {String}
     */
    id() { return this._id; }
    /**
     * @returns {String}
     */
    parent() { return this._parent; }
    /**
     * @returns {String[]}
     */
    tags() { return this._tags; }
    /**
     * @returns {Number}
     */
    slot() { return this._slot; }
    /**
     * @returns {Object}
     */
    path() { return this._path; }
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
        this._node = this.selector(name);
        //this._node = this.render(name);
        this._remove = false;
        this.create();
    }
    /**
     * Setup internal component data, before all components have been created
     */
    create() {
        //override
        this._components = [];
    }
    /**
     * Initialize component data, fetching and events once all components have been created
     */
    initialize() {
        //initialize view data and events
        if(!this._node){
            this._node = this.render();
        }
        console.log(`Initializing ${ this.constructor.name }`)
    }
    /**
     * @param {String} selector 
     * @returns {Element}
     */
    selector(selector = '') {
        if( selector){
            const query = ['#wpbody-content', '.coders-clipboard'];
            query.push(selector);
            return document.querySelector(query.join(' ')) || null;
        }
        return null;
        //return [...document.getElementsByClassName(name)][0] || null;
    }
    /**
     * @param {ViewComponent} c 
     * @returns {ViewComponent}
     */
    append(c = null) {
        if ( c && c instanceof ViewComponent) {
            console.log(this,c);
            !c.node() && c.initialize();
            this.components().push(c);
            if( this.node() && c.node() ){
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
        this._remove;
        this.$('remove', this);
        return this;
    }
    /**
     * @param {ViewComponent} component 
     * @returns {ViewComponent}
     */
    drop(component = null) {
        if (component instanceof ViewComponent) {
            this._components = this.components().filter(c => c._remove);
        }
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
    text(text = '') { return App.text(text); }
    /**
     * @returns {CoderClient}
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
        this._queue = null;
    }
    /**
     * 
     */
    initialize() {
        super.initialize();
        //prepare all events
        this.onDragStart();
        this.onDragEnd();
        this.onDragOver();
        this.populate();
    }
    /**
     * @returns {Collection}
     */
    populate() {
        const id = App.instance().id() || '';
        App.server().list(id, (r) => {
            console.log(id, r);
            this.fill((r.items || []).map(data => ClipData.fromdata(data)));
        });
        return this;
    }
    /**
     * @returns {ClipView[]}
     */
    items() { return this.components(); }
    /**
     * @param {ClipData} clip 
     * @returns {Collection}
     */
    add(clip = null) {
        if (clip instanceof ClipData) {
            this.append(new ClipView(clip));
        }
        return this;
    }
    /**
     * @returns {ClipData}
     */
    clip() { return App.clipview(); }
    /**
     * @param {ClipData[]} items 
     * @returns {Collection}
     */
    fill(items = []) {
        const content = this.node();
        content && (items || []).forEach(data => this.add(data));
        return this;
    }
    /**
     * @param {String} id 
     * @param {String} target 
     * @returns {Collection}
     */
    move(id = '', target = '') {
        App.server().moveto(id, target, r => {
            const id = r.id || '';
            const item = this.items().find(item => item.id() === id);
            item.remove();
        });
        return this;
    }
    /**
     * @param {String} id 
     * @param {Number} slot 
     * @returns {Collection}
     */
    sort(id = '', slot = 0) {
        App.server().sort(id, slot, data => {
            this.arrange(ClipData.fromdata(data));
        });
        return this;
    }

    /**
     * @returns {Collection}
     */
    onDragEnd() {
        const node = this.node();
        document.addEventListener('dragend', e => {
            e.preventDefault();
            node.classList.remove('move');
            const source = node.querySelector('li.item.moving');
            source.classList.remove('moving');
        });
        return this;
    }
    /**
     * @returns {Collection}
     */
    onDragStart() {
        const node = this.node();
        node && document.addEventListener('dragstart', e => {
            /*e.preventDefault();*/
            const item = e.target.closest('li.item');
            if (!item || !node.contains(item)) return;

            node.classList.add('move');
            item.classList.add('moving');

            const id = item.dataset.id;
            const slot = item.dataset.slot;

            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData(
                'application/json',
                JSON.stringify({ 'id': id, 'slot': slot }));
            // Create a custom drag image
            this.createGhost(item.querySelector('img.media'));

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
            if (ghost) {
                //console.log(ghost);
                ghost.style.borderRadius = '50%';
                ghost.style.position = 'absolute';
                ghost.style.top = '-1000px';
                ghost.style.left = '-1000px';
                ghost.style.zIndex = '-1'; // avoid blocking other elements
                ghost.style.pointerEvents = 'none';
                document.body.appendChild(ghost);

                // Wait for the browser to render the ghost before setting it as the drag image
                requestAnimationFrame(() => {
                    e.dataTransfer.setDragImage(ghost, 0, 0);
                    // Optional cleanup
                    setTimeout(() => ghost.remove(), 1000);
                });
            }
        }
        return this;
    }
    /**
     * @returns {Collection}
     */
    onDragOver() {
        const content = this.node();
        content.addEventListener('dragover', (e) => {
            e.preventDefault(); // allow drop
        });
        return this;
    }
    /**
     * @returns {Collection}
     */
    onDrop() {
        const content = this.node();
        content && content.addEventListener('drop', e => {
            e.preventDefault();
            const target = e.target;
            //console.log(target.closest('li.item'));
            const item = target.closest('li.item');
            const data = JSON.parse(e.dataTransfer.getData('application/json') || '{}');
            const _id = data.id || '';
            const _slot = parseInt(data.slot) || 0;

            const action = target.classList.contains('placeholder') && 'sort'
                || target.classList.contains('caption') && 'move' || '';

            switch (action) {
                case 'move':
                    const id = item && item.dataset.id || '';
                    //const target_id = target.dataset.id;
                    if (_id !== id) {
                        //console.log(`Moving ${source_id} to ${target_id}`);
                        this.move(_id, id);
                    }
                    break;
                case 'sort':
                    const slot = item && parseInt(item.dataset.slot) || 0;
                    //const slot = target.dataset.slot;
                    if (slot && slot !== _slot) {
                        //console.log(`Moving ${source_id} to slot ${slot}`);
                        this.sort(_id, slot);
                    }
                    break;
                default:
                    //console.log(`No target selected`);
                    break;
            }
        });
        return this;
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
     * @returns {Element}
     */
    notifier() { return this.node(); }
    /**
     * @param {String} content 
     * @param {String} type 
     * @returns {Notifier}
     */
    show(content = '', type = 'info') {
        console.log(message, type);
        if (content && this.notifier()) {
            this.notifier().appendChild(this.html('div', { 'class': `is-dismissible notice type-${type}` }, content));
        }
        return this;
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
    ismedia() { return this.isimage() ||this.isvideo(); }
    /**
     * @returns {String}
     */
    type() { return this.data() && this.data().type() || ''; }

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
     * @param {Element} element 
     * @returns {ClipData}
     */
    static fromelement(element = null) {
        if (element) {
            return new ClipData(
                element.getAttribute('data-id') || '',
            );
        }
        return null;
    }
    /**
     * @returns {Element}
     */
    overlay() {
        return this.html('a', {
            'data-id': this.data().id(),
            'href': this.data().post(),
        }, this.data().title());
    }
    /**
     * @returns {Element[]}
     */
    buttons() {
        return [
            //add all action butttons here (use builtin methods to setup events)
            this.btnremove(),
            this.btnmove(),
            this.count(),
        ];
    }
    /**
     * @returns {Element}
     */
    count() {
        const element = this.html('span', { 'class': 'btn remove' }, this.text('count'));
        //add dashicons contents
        return element;
    }
    /**
    /**
     * @returns {Element}
     */
    btnmove() {
        const element = this.html('span', { 'class': 'btn remove' }, this.text('move'));
        //add dashicons contents
        //add actions
        return element;
    }
    /**
     * @returns {Element}
     */
    btnremove() {
        const element = this.html('span', { 'class': 'btn remove' }, this.text('remove'));
        //add dashicons contents
        //add actions
        return element;
    }
    /**
     * @returns {Element}
     */
    render() {

        const item = this.html('li', { class: 'item clip', });
        const content = this.html('div', { 'class': 'content', 'draggable': 'true' }, this.makecontent());
        const placeholder = this.html('span', { 'class': 'placeholder', 'data-slot': this.slot() });
        item.appendChild(placeholder);
        item.appendChild(content);
        this.buttons().forEach(button => item.appendChild(button));

        return item;
    }
    /**
     * @returns {Object}
     */
    attributes(){
        const att = {};
        const d = this.data();
        const type = this.ismedia() && 'media' || 'attachment';
        if( d){
            att['class'] = type + ' ' + d.tags().join(' ');
        }
        return att;
    }
    /**
     * @returns {Element}
     */
    makecontent() {
        const data = this.data();
        if (data) {
            const data = this.data();
            const atts = this.attributes();
            switch (true) {
                case this.isimage():
                    atts['src'] = data.link();
                    atts['alt'] = data.name();
                    atts['title'] = data.title();
                    return this.html('img',atts);;
                case this.ismedia():
                    return this.html('span',atts);
            }
            return this.html('span',atts,data.name());
        }
        return super.render();
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
        this.load(App.instance().id());
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
        this._clip = id && new ClipData(id) || null;
        if (this.clip()) {
            this.clip().refresh();
            //refresh all data changes when the clip geets updated
            this.clip()._('update', clip => { this.refresh(clip, false); });
            //fire clip data event
            this.$('load', this.clip());
        }
        return this;
    }
    /**
     * @param {ClipData} clip 
     * @returns {ClipFormView}
     */
    refresh(clip = null, runevent = false) {
        if (clip instanceof ClipData) {
            this._clip = this.clip() ? this.clip().copy(clip) : clip;
            runevent && this.$('refresh', this.clip());
            //fill fporm data here
            console.log('Form Data ', clip);
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
    }
    /**
     * 
     */
    create() {
        super.create();
        this._queue = new ClipQueue(App.instance().id());
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
        this.queue()._('done', completed => this.clear());
    }
    /**
     * @returns {ClipQueue}
     */
    queue() { return this._queue; }
    /**
     * @returns {Element}
     */
    fileinput() { return this.node().querySelector('input[type=file]') || null; }
    /**
     * @returns {Boolean}
     */
    useajax() {
        const input = this.fileinput();
        return input && input.classList.contains('ajax') || false;
    }
    /**
     * @returns {Uploader}
     */
    setupFileInput() {
        if (this.useajax()) {
            const id = App.instance().id();
            this.fileinput().addEventListener('change', e => App.server().upload(
                e.target.files, id //attach to parent id if included
            ));
        }
        return this;
    }
    /**
     * @returns {Element}
     */
    gauge() { return this.node().querySelector('.gauge .progress'); }
    /**
     * @param {Number} progress 
     * @returns {Uploader}
     */
    progress(progress = 0) {
        const gauge = this.gauge();
        //console.log(gauge, progress);
        if (gauge) {
            !this.isactive() && gauge.classList.add('active');
            gauge.style.width = `${Math.min(progress, 100)}%`;
        }
        return this;
    }
    /**
     * @returns {Boolean}
     */
    isactive() { return this.gauge() && this.gauge().classList.contains('active') || false; }
    /**
     * @returns {Uploader}
     */
    clear() {
        this.isactive() && this.gauge().classList.remove('active');
        return this;
    }
    /**
     * @returns {Collection}
     */
    collection() { return App.collection(); }
    /**
     * @param {File[]} files 
     * @returns {Uploader}
     */
    enqueue(files = []) {
        console.log('Uploading: ', e.dataTransfer.files);
        this.queue().upload(files);
        return this;
    }
    /**
     * @param {File} file 
     * @param {String} id 
     * @returns {Uploader}
     */
    upload(file = null, id = '') {
        if (file instanceof File) {
            const server = App.server();
            server.upload(file, id, r => {
                //files,count
                this.fill(ClipData.fromlist(r.files || []));
                r.count && App.notify(`${r.count} Clips uploaded`, 'update');
            });
        }
        return this;
    }
    /**
     * @returns {Uploader}
     */
    onDragDrop() {
        // Drag-drop
        document.addEventListener('dragover', e => {
            e.preventDefault();
        });
        document.addEventListener('drop', e => {
            e.preventDefault();
            if (e.dataTransfer.files.length) {
                this.enqueue(e.dataTransfer.files);
            }
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
        clipview._('load', clip => clip && this.refresh(clip.path()));
    }

    /**
     * @returns {ClipFormView}
     */
    clipview() { return App.clipview(); }
    /**
     * @param {Object} path 
     * @returns {NavigatorView}
     */
    refresh(path = {}) {
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
            copylink.addEventListener('click', function (e) {
                e.preventDefault();
                const link = this.dataset.link || '';
                if (link) {
                    navigator.clipboard.writeText(link)
                        .then(() => {
                            ContentView.notify('URL copied to clipboard!', 'updated');
                        })
                        .catch(err => {
                            ContentView.notify('Failed to copy: ', err);
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
    }
    /**
     * 
     */
    initialize() {
        super.initialize();
    }
    /**
     * 
     */
    create() {
        super.create();
        //register events here
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

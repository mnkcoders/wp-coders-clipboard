/****
 * {task: list, id:string|empty} retrieve the list of items in the current view to fill the collection
 * {task: moveup, id:string|string[]} move up to the upper level one or more items (accepts array of ids)
 * {task: moveto, id:string|string[],parentid} move up to the new parent id (sibling in the same colleciton)
 * {task: remove, id:string|string[]} can remove several selected items
 * {task: sort, id: string , position: (1,...) } requests a sorting update to arrange the collection
 * 
 * {filetask: file, id: string } uploads a file with a special task which includes the File object
 */
//Loader
document.addEventListener('DOMContentLoaded', function () {

    CodersClipboard.instance( CoderInput.create().get('id') );
});
/**
 * 
 */
class CoderInput{
    /**
     * 
     */
    constructor(){
        const url = location.href.split('?');
        this._url = url[0];
        this._input = this.parse(url[1] || '');
    }
    /**
     * @returns {CoderInput}
     */
    static create(){ return new CoderInput(); }
    /**
     * @param {String} key 
     * @returns {String}
     */
    get(key = '' ){ return key && this._input[key] || ''; }
    /**
     * @param {String} key 
     * @returns {Number}
     */
    getInt(key = ''){ return parseInt(this.get(key)) || 0; }
    /**
     * @param {String} input 
     * @returns {Object}
     */
    parse( input  ='' ){
        const content = {};
        input && input.split('&')
            .map( param => param.split('='))
            .forEach( pair => content[pair[0]] = pair[1] || '');
        return content;
    }
}

/**
 * @type {CoderEvents}
 */
class CoderEvents {
    constructor() {
        this._e = {};
    }
    /**
     * Register an event
     * @param {String} e 
     * @param {Function} call 
     * @returns {CoderEvents}
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
 * 
 * @type CodersClipboard
 */
class CodersClipboard extends CoderEvents {
    /**
     * @param {String} id
     * @returns {CodersClipboard}
     */
    constructor( id = '') {
        super();
        if (CodersClipboard.__instance) {
            return CodersClipboard.__instance;
        }
        CodersClipboard.__instance = this;

        //this._id = id || '';
        this._clip = id && new ClipData(id) || null;
        //new version models
        this._strings = new CoderStrings();
        this._display = new ClipboardView();
        this._drive = new CoderDrive('content');
        this._api = this.readapi();

        //old version models
        //this._clipboard = ClipboardContent.create();
        //this.initialize();
        this.initialize();
        console.log(this);
    }
    /***
     * 
     */
    initialize(){
        this.display().form().attach(this.clip());
        this.display().refresh( this.id() );
    }
    /**
     * @returns {Object}
     */
    readapi(){
        return CodersAPI || { public: '' , admin: '', ajax: ajaxurl, nonce: '' };
    }
    /**
     * @param {ClipboardContent} cb
     * @returns {bool}
     */
    initializeBAK() {

        if (this.clipboard().ready()) {
            this.setupFileInput();
            this.setupDragDrop();
            this.setupDragStart();
            this.setupDragEnd();
            this.setupDragOver();
            this.setupDrop();

            this.setupCopy();
            this.setupPaste();
        }
        this.setupTabs();
    }
    /**
     * @param {String} id
     * @returns {CodersClipboard}
     */
    static instance( id = '') {
        return CodersClipboard.__instance || new CodersClipboard( id );
    }

    /**
     * @param {String} message 
     * @param {String} type 
     * @returns {ClipboardView}
     */
    static notify(message, type = 'info') {
        console.log(message,type);
        this.instance().display().notifier().show(message, type);
        return this;
    }    
    /**
     * @param {String} text 
     * @returns {String}
     */
    static text(text = '') { return this.instance().strings().get(text); }
    /**
     * @returns {Object}
     */
    static api() { return this.instance()._api; }
    /**
     * @param {String} id 
     * @returns {String}
     */
    static public(id = ''){ return this.api().public && this.api().public + id || ''; }
    /**
     * @param {String} id 
     */
    static admin( id = '' ){ this.api().admin && id ? `${this.api().admin}&id=${id}` : this.api().admin || ''; }
    /**
     * @returns {CoderStrings}
     */
    strings() { return this._strings; }
    /**
     * Selected drive
     * @returns {String}
     */
    drive() { return this._drive; }
    /**
     * @returns {ClipboardView}
     */
    display() { return this._display; }
    /**
     * @returns {ClipData}
     */
    clip(){ return this._clip; }
    /**
     * @returns {String}
     */
    id(){ return this.clip() && this.clip().id() || ''; }
    /**
     * @returns {ClipView[]}
     */
    list(){ return this.display().collection().items(); }

    upload( items = []){
        const uploader = this.display().uploader();
        const collection = this.collection();
        (items || []).map( file => new UploadTask(file,{}, (r,task) => {
            //make item from task
            //collection.appendChild( )
        })).forEach( item => uploader.add(item));
        return this;        
    }


    /**
     * @returns {ClipboardContent}
     */
    clipboard() { return this._clipboard; }
    /**
     * @returns {Element}
     */
    collection() {
        return this.clipboard() && this.clipboard().view().itemBox() || null;
    }
    /**
     * 
     */
    setupTabs() {
        const tabs = document.querySelector('.coders-clipboard .container .tab > .toggle');

        //tabs.prepend( document.createElement('span') );
        if (tabs) {
            tabs.addEventListener('click', function (e) {
                e.preventDefault();
                (this).parentNode.classList.toggle('collapsed');
                return true;
            });
        }
    }

    setupFileInput() {
        const cb = this.clipboard();
        //file upload
        document.querySelectorAll('input[type=file]').forEach(input => {
            if (input.classList.contains('ajax')) {
                //only allow on ajax mode, otherwise just use uploads button
                input.addEventListener('change', (e) => {
                    cb.upload(e.target.files);
                });
            }
        });
    }
    setupDragDrop() {
        // Drag-drop
        const cb = this.clipboard();
        document.addEventListener('dragover', e => e.preventDefault());
        document.addEventListener('drop', e => {
            e.preventDefault();
            if (e.dataTransfer.files.length) {
                cb.upload(e.dataTransfer.files);
            }
        });
    }
    setupPaste() {
        const cb = this.clipboard();
        // Paste
        document.addEventListener('paste', (e) => {
            if (e.clipboardData.files.length) {
                cb.upload(e.clipboardData.files);
            }
            else {
                cb.upload((e.clipboardData.items || [])
                    .filter(item => item.kind === 'file')
                    .map(item => item.getAsFile() || null)
                    .filter(item => item !== null));
            }
        });
    }
    /**
     * 
     */
    setupDragEnd() {
        const collection = this.collection();
        document.addEventListener('dragend', (e) => {
            e.preventDefault();
            collection.classList.remove('move');
            const source = collection.querySelector('li.item.moving');
            source.classList.remove('moving');
        });
    }
    /**
     * 
     */
    setupDragStart() {

        const cb = this.clipboard();
        const collection = this.collection();

        document.addEventListener('dragstart', (e) => {
            /*e.preventDefault();*/
            const item = e.target.closest('li.item');
            if (!item || !collection.contains(item)) return;

            collection.classList.add('move');
            item.classList.add('moving');

            //console.log(item.dataset.id,item.dataset.slot);
            const item_id = item.dataset.id;
            const slot = item.dataset.slot;

            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('application/json', JSON.stringify({ 'id': item_id, 'slot': slot })); // store item ID
            //console.log('DRAG!!', item_id, slot);

            // Create a custom drag image
            //const ghost = e.target.cloneNode(true);
            //const image = e.target.closest('img.media');
            const image = item.querySelector('img.media');
            const ghost = image.cloneNode(true);
            if (ghost) {
                console.log(ghost);
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
        });
    }
    /**
     * 
     */
    setupDragOver() {
        const collection = this.collection();
        collection.addEventListener('dragover', (e) => {
            e.preventDefault(); // allow drop
        });
    }
    setupDrop() {
        this.collection().addEventListener('drop', (e) => {
            e.preventDefault();
            const target = e.target;
            console.log(target.closest('li.item'));
            const targetItem = target.closest('li.item');
            const data = JSON.parse(e.dataTransfer.getData('application/json') || '{}');
            const source_id = data.id;
            const source_slot = parseInt(data.slot);

            const action = target.classList.contains('placeholder') && 'sort' || target.classList.contains('caption') && 'move' || '';

            switch (action) {
                case 'move':
                    const target_id = targetItem && targetItem.dataset.id || '';
                    //const target_id = target.dataset.id;
                    if (source_id !== target_id) {
                        //console.log(`Moving ${source_id} to ${target_id}`);
                        cb.move(source_id, target_id);
                    }
                    break;
                case 'sort':
                    const slot = targetItem && parseInt(targetItem.dataset.slot) || false;
                    //const slot = target.dataset.slot;
                    if (slot !== false && source_slot !== slot) {
                        //console.log(`Moving ${source_id} to slot ${slot}`);
                        cb.sort(source_id, slot);
                    }
                    break;
                default:
                    //console.log(`No target selected`);
                    break;
            }
        });
    }
    /**
     * 
     */
    setupCopy() {
        const copylink = document.querySelector('.copy-link');
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
    }
}


/**
 * @deprecated Keep this class as reference while implementing the new models 
 * @class {ClipboardContent}
 */
class ClipboardContent {
    /**
     * @param {String} uploadBox 
     * @param {String} itemBox 
     */
    constructor(uploadBox = '', itemBox = '') {
        this._ts = this.timestamp();
        this._view = new ContentView(uploadBox, itemBox);
        this._tasks = [];
        this._timeout = 200;
        //console.log(this);
        this._headerDone = false;
    }
    /**
     * @param {String} uploads 
     * @param {String} items 
     * @returns {ClipboardContent}
     */
    static create(uploads = 'clipboard-box', items = 'collections') {
        return new ClipboardContent(uploads, items);
    }
    /**
     * @returns {ContentView}
     */
    view() {
        return this._view;
    }
    /**
     * @returns {Boolean}
     */
    ready() {
        return !!(this.view().uploadBox() && this.view().itemBox());
    }
    /**
     * @returns {Boolean}
     */
    isClipboard() {
        return this.view().hasClipboard();
    }
    /**
     * @returns {Boolean}
     */
    isMain() {
        return !this.hasContext();
    }
    /**
     * @param {Boolean} listReady
     * @returns {ClipTask[]}
     */
    tasks(listReady = false) {
        return listReady ? this._tasks.filter(task => task.ready()) : this._tasks;
    }
    /**
     * @returns {ClipboardContent}
     */
    reset() {
        this._tasks = [];
        return this;
    }
    /**
     * @returns {String}
     */
    contextId() {
        return this.view().contextId();
    }
    /**
     * @returns {Boolean}
     */
    hasContext() {
        return !!this.view().contextId();
    }
    /**
     * @returns {Boolean}
     */
    isHeader() {
        return !this._headerDone;
    }
    /**
     * @returns {String}
     */
    timestamp() {
        return new Date().toISOString();
    }
    /**
     * @returns {Object}
     */
    contextData() {
        const data = { action: 'clipboard' };
        if (this.hasContext()) {
            data.id = this.contextId();
        }
        return data;
    }
    /**
     * @param {File[]} files 
     * @returns {ClipboardContent}
     */
    queue(files) {
        this._headerDone = false;
        Array.from(files).forEach(file => {
            const task = new UploadTask(
                file,
                this.contextData(),
                this.uploaded.bind(this)
            );
            //this.view().attach(task);
            this.tasks().push(task);
        });
        this.view().clearEmptyBlock().busy();
        return this.wait2Next()
    }
    /**
     * @returns {ClipboardContent}
     */
    next() {
        const task = this.tasks(true)[0] || null;
        console.log('Next Task', task);
        task && task.request() || this.view().idle();
        return this;
    }
    /**
     * @returns {ClipboardContent}
     */
    wait2Next() {
        window.setTimeout(() => { this.next() }, this._timeout);
        return this;
    }
    /**
     * 
     * @param {Object[]} response 
     * @param {ClipTask} task 
     * @returns {ClipboardContent}
     */
    uploaded(response = {}, task = null) {
        console.log('UPLOADED!!', response, task);
        if (response && response.content) {
            const view = this.view();
            //console.log( this.isHeader(),this.isMain());
            if (this.isHeader() || !this.isMain()) {
                response.content.forEach(item => {
                    view.createItem(item)
                    if (this.isMain()) {
                        this.setHeader(item.id);
                    }
                });
            }
            //console.log( typeof task );
            const preview = task && task.ref() || null;
            if (preview) {
                preview.remove();
            }
            this.wait2Next();
        }
        return this;
    }
    /**
     * @param {String} id 
     * @returns {ClipboardContent}
     */
    setHeader(id = '') {
        if (id) {
            this.tasks(true).filter(task => task.hasAttachment()).forEach(task => task.data().id = id);
            //console.log(this.tasks(true));
            this._headerDone = true;
        }
        return this;
    }
    /**
     * 
     * @param {DataTransferItem[]} items 
     * @returns {ClipboardContent}
     */
    upload(items = []) {
        if (items.length) {
            this.queue(items);
        }
        return this;
    }
    /**
     * @param {Blob} blob 
     * @param {String} filename 
     * @returns {ClipboardContent}
     */
    /*paste(blob, filename = '') {
        if (filename.length === 0) {
            filename = this.timestamp();
        }
        const file = new File([blob], filename, { type: blob.type });
        return this.queue([file]);
    }*/

    /**
     * 
     * @param {String} id 
     * @param {String} parent_id 
     * @returns {ClipboardContent}
     */
    move(id = '', parent_id = '') {
        if (id) {
            //console.log(`Moving [${id}] to [${parent_id || 'ROOT'}]`);
            const _view = this.view();
            const task = new ClipTask('move',
                { 'id': id, 'parent_id': parent_id, 'context_id': this.contextId() },
                _view.remove.bind(_view));
            task.request();
        }

        return this;
    }
    /**
     * @param {String} id 
     * @param {Number} slot 
     * @returns {ClipboardContent}
     */
    sort(id = '', slot = 0) {
        if (id) {
            //console.log(`Moving [${id}] to slot [${slot}]`);
            const _view = this.view();
            const task = new ClipTask(
                'sort',
                { 'id': id, 'slot': slot, 'context_id': this.contextId() },
                _view.sort.bind(_view));
            task.request();
        }
        return this;
    }
}

/**
 * handle storage selection operations
 */
class CoderDrive{
    /**
     * @param {String} storage 
     */
    constructor( storage = '' ){
        this._storage = storage || '';
    }
    /**
     * @returns {String}
     */
    storage(){ return this._storage; }
}

/**
 * all actions for hook {action: coder_clipboard}
 * {task: remove, id:string|string[]} can remove several selected items
 * {task: moveup, id:string|string[]} move up to the upper level one or more items (accepts array of ids)
 * {task: moveto, id:string|string[],parentid} move up to the new parent id (sibling in the same colleciton)
 * {task: sort, id: string , position: (1,...) } requests a sorting update to arrange the collection
 * @type {ClipTask}
 */
class ClipTask extends CoderEvents {
    /**
     * @param {String} task 
     * @param {Object} data
     * @param {Function} callback
     */
    constructor(task = '', data = {}, callback = null) {
        super();
        this._status = ClipTask.Status.Ready;
        this._task = task || 'main';
        this._data = data || {};
        this._ref = null;
        //this._callback = callback || null;
        this._response = {};
        this._('response',callback );
        console.log(this);

        //add to queue?
        this.uploader().add(this);
    }
    /**
     * @param {String} task 
     * @param {Object} data 
     * @param {Function} callback 
     * @returns {ClipTask}
     */
    static create( task = '' , data ={} , callback = null ){
        return new ClipTask(task,data,callback);
    }
    /**
     * @returns {UploaderView}
     */
    uploader(){ return CodersClipboard.instance().display().uploader(); }
    /**
     * @returns {Object}
     */
    response(){ return this._response || {}; }
    /**
     * @returns {Boolean}
     */
    success(){ return !!this.response()._response; }
    /**
     * Messsages
     * @returns {Object[]}
     */
    log(){ return this.response()._log || []; }
    /**
     * @returns {Object}
     */
    api() { return CodersClipboard.api(); }
    /**
     * @returns {String}
     */
    url() { return this.api().ajax || ''; }
    /**
     * @returns {String}
     */
    nonce() { return this.api().nonce; }
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
    task() { return this._task; }
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
     * @returns {Element}
     */
    ref() {
        return this._ref;
    }
    /**
     * @param {Object} ref 
     * @returns {ClipTask}
     */
    setRef(ref) {
        this._ref = ref;
        return this;
    }
    /**
     * @returns {Boolean}
     */
    hasRef() {
        return this.ref() !== null;
    }
    /**
     * @returns {ClipboardContent}
     */
    request() {
        if (this.valid()) {
            this._status = ClipTask.Status.Running;
            const content = this.createForm(this.data());
            console.log(`Sending ${this.url()}`);
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
        if( response && response.success ){
            console.log('RESPONSE', response.data);
            this._response = response.data || {};
            this._status = ClipTask.Status.Complete;
            this.$('response',this.response());
            //this.$('done', this);
        }
        else{
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
        //this.$('done', this);
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
    createForm( input = {} ){
        const form = super.createForm( input );
        if (this.hasAttachment()) {
            form.append('upload', this.attachment());
        }        
        return form;
    }
}
/**
 * Use to save the current gallery item's meta data
 */
class FormTask extends ClipTask{
    /**
     * @param {Object} content 
     * @param {Function} callback 
     */
    constructor( content = null , callback = null){
        super( 'save' ,
            content instanceof ClipData ? content.data() : {},
            callback );
    }
}


/**
 * Use to portdata between the list and the server tasks
 * @type {ClipData}
 */
class ClipData extends CoderEvents{
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
    constructor(id = '', parent = '' , name = '', type = '' , title = '' , desc = '' , slot = 0) {
        super();
        this._id = id || '';
        this._slot = slot || 0;
        this._parent = parent || '';
        this._name = name || 'new-clip';
        this._type = type || '';
        this._title = title ||this.name();
        this._desc = desc || '';
        this._tags = [];
        this._tree = {};
    }
    /**
     * @param {Object} data 
     * @returns {ClipData}
     */
    static fromdata( data ){
        return new ClipData(
            data.id || '',
            data.parent_id || '',
            data.name || '',
            data.type || '',
            data.title || '',
            data.desc || '',
            data.slot || '',
        );
    }
    link(){ return CodersClipboard.api().public + this.id(); }
    /**
     * @param {Object} data 
     * @returns {ClipData}
     */
    refresh( data = null ){
        console.log(data);
        if(data instanceof Object){
            //fill in all data
            const copy = ClipData.fromdata(data);
            Object.keys(copy).forEach( key => this[key] = copy[key]);
        }
        return this;
    }
    /**
     * @returns {Boolean}
     */
    empty(){ return !this.id(); }
    /**
     * @returns {Object}
     */
    data(){
        return {
            id: this.id(),
            name : this.name(),
            title: this.title()  || this.name(),
            parent: this.parent(),
            slot: this.slot(),
        };
    }
    /**
     * @returns {FormTask}
     */
    save(){
        return new FormTask( this, task => {
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
    desc(){ return this._desc; }
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
     * @returns {Boolean}
     */
    isimage() {
        return false;
    }
    /**
     * @returns {Boolean}
     */
    isvideo() {
        return false;
    }
    /**
     * @returns {Boolean}
     */
    isattachment() {
        return false;
    }
    /**
     * @returns {String[]}
     */
    nodes() { return Object.keys(this._tree || {}); }
    /**
     * @returns {Object}
     */
    path() {
        const path = {};
        this.nodes().forEach(id => path[id] = this._tree[id]);
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
 * Obsolete / old version
 * @type {ContentView}
 */
class ContentView {
    /**
     * @param {String} uploadBox 
     * @param {String} itemBox 
     */
    constructor(uploadBox, itemBox) {
        //this._queue = document.getElementById(list);
        this._uploader = [...document.getElementsByClassName(uploadBox)][0] || null;
        this._collection = [...document.getElementsByClassName(itemBox)][0] || null;
        this._container = this.importContext() || '';
    }
    /**
     * @returns {Boolean}
     */
    hasClipboard() {
        return this.itemBox() !== null && this.uploadBox() !== null;
    }
    /**
     * @returns {String}
     */
    importContext() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('context_id') || '';
    }
    /**
     * @returns {String}
     */
    contextId() {
        return this._container;
    }
    /**
     * @returns {Element}
     */
    uploadBox() {
        return this._uploader;
    }
    /**
     * @returns {Element}
     */
    itemBox() {
        return this._collection;
    }
    /**
     * @param {String} name 
     * @param {Object} attributes 
     * @param {String} content
     * @returns {Element}
     */
    static element(name = 'span', attributes = {}, content = '') {
        const element = document.createElement(name);
        Object.keys(attributes).forEach(att => {
            element[att] = attributes[att];
        });
        if (content.length) {
            element.textContent = content;
        }
        return element;
    }
    /**
     * @param {ClipTask} task 
     * @returns {ContentView}
     */
    attach(task) {
        if (task && task.hasAttachment()) {
            const file = task.attachment();
            const item = ContentView.element('li', { 'className': 'item' });
            const reader = new FileReader();
            reader.onload = (e) => {
                const preview = this.preview(file, e.target.result);
                item.appendChild(preview);
            };
            this.uploadBox().appendChild(item);
            task.setRef(item); // Store reference
            reader.readAsDataURL(file)
        }
        return this;
    }
    /**
     * @param {Object} itemData 
     * @returns {Element}
     */
    createItem(itemData = {}) {
        //console.log(itemData,this.isMedia(itemData.type || ''));
        const item = ContentView.element('li', { 'className': 'item' });
        const content = ContentView.element('span', { 'className': 'content' });

        if (this.isMedia(itemData.type || '')) {
            content.appendChild(ContentView.element('img', {
                'src': itemData.link,
                'alt': itemData.name,
                'title': itemData.title || itemData.name,
                'className': 'media',
            }));
        }
        else {
            content.appendChild(ContentView.element('span', { 'className': 'dashicons dashicons-media-document' }));
        }

        content.appendChild(ContentView.element('a', {
            'href': itemData.post || '#',
            'target': '_self',
            'className': 'caption'
        }, itemData.title));

        item.appendChild(ContentView.element('span', { 'className': 'placeholder' }));
        item.appendChild(content);
        this.itemBox().appendChild(item);

        return this;
    }
    /**
 * @param {String} type 
 * @returns {Boolean}
 */
    isMedia(type = '') {
        switch (type) {
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/gif':
            case 'image/png':
            case 'image/webp':
                return true;
        }
        return false;
    }

    /**
     * @returns {Element}
     */
    preview(file, buffer) {
        if (file instanceof File) {
            switch (file.type) {
                case 'image/png':
                case 'image/gif':
                case 'image/jpeg':
                    return ContentView.element('img', {
                        'className': 'content media',
                        'src': buffer,
                        'alt': file.name
                    });
                default:
                    return ContentView.element('span', {
                        'className': 'content attachment'
                    },
                        file.name);
            }
        }
        return ContentView.element('span', { 'className': 'content empty' });
    }
    /**
     * @returns {ContentView}
     */
    idle() {
        this.uploadBox().classList.remove('running');
        return this;
    }
    /**
     * @returns {ContentView}
     */
    busy() {
        this.uploadBox().classList.add('running');
        return this;
    }
    /**
     * 
     * @returns {ContentView}
     */
    clearEmptyBlock() {
        const empty = this.itemBox().querySelector('li.empty');
        if (empty) {
            empty.remove();
        }
        return this;
    }
    /**
     * 
     * @param {String} id 
     * @returns {Element}
     */
    getItem(id) {
        console.log(this.itemBox(), `li.item[data-id="${id}"]`, this.itemBox().querySelector(`li.item[data-id="${id}"]`))
        return this.itemBox().querySelector(`li.item[data-id="${id}"]`);
    }
    /**
     * 
     * @param {String} id 
     * @returns {ClipboardContent}
     */
    remove(response = {}) {
        if (response && response.id) {
            const item = this.getItem(response.id);
            console.log(response.id, item);
            if (item) item.remove();
        }
        return this;
    }
    sort(response = {}) {
        if (response.id) {
            const id = response.id;
            const slot = parseInt(response.slot);
            const item = this.getItem(id);
            const placeholders = this.itemBox().querySelectorAll('.placeholder');

            // Find the placeholder by slot index
            const target = [...placeholders].find(p => p.dataset.slot == slot);
            if (!target) return;

            // Detach the item
            item.remove();

            // Insert before the target placeholder's parent (which is the target li.item)
            const selected = target.closest('li.item');
            if (selected) {
                this.itemBox().insertBefore(item, selected);
            } else {
                // If no item found (e.g., last placeholder), just append
                this.itemBox().appendChild(item);
            }
        }
    }
    /**
     * @param {String} content 
     * @param {String} type 
     */
    static notify(content, type = 'info') {
        const notifier = document.querySelector('.coders-clipboard .notifier') || null;
        if (notifier) {
            const message = ContentView.element('div', {
                'className': 'is-dismissible notice type-' + type
            }, content);
            notifier.appendChild(message);
            window.setTimeout(() => {
                message.remove();
            }, 2000);
        }
    }
}




/**
 * Base View class
 */
class CoderView extends CoderEvents {
    /**
     * 
     */
    constructor() {
        super();
        this.initialize();
    }
    /**
     * 
     */
    initialize() {
        //override
    }
    /**
     * @param {String} text 
     * @returns {String}
     */
    text(text = '') { return CodersClipboard.instance().strings().get(text); }
    /**
     * @returns {Element}
     */
    render() {
        //override
        return this.html('div', { 'class': 'empty' }, '');
    }
    /**
     * @returns {Object}
     */
    api() { return CodersClipboard.api(); }
    /**
     * @returns {String}
     */
    url() { return this.api().url; }
    /**
     * @returns {String}
     */
    public(){ return this.api().public; }
    /**
     * @returns {String}
     */
    nonce() { return this.api().nonce; }


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
     * @param {Object} request
     * @param {String|Element} content 
     * @param {String} className 
     * @param {String} target _self|_blank
     * @returns {Element}
     */
    link(request = {}, content, className = '', target = '_self') {
        const base = `${this.url()}?page=coder_clipboard`;
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
class ClipboardView extends CoderView {
    /**
     * 
     */
    constructor() {
        super();
        this._form = new ClipFormView('content');
        this._notifier = new NotifierView('coder-notifier');
        this._collection = new CollectionView('collections');
        this._uploader = new UploaderView('upload');
        this._toolbar = new ToolbarView('tools');
        this._navigator = new NavigatorView('coder-navigator');
        this.initialize();
    }
    /**
     * 
     */
    initialize(){
        this.onDragDrop();
        this.onDragStart();
        this.onDragOver();
        this.onDragEnd();
        this.onFileInput();
        this.onCopy();
        this.onPaste();
    }
    /**
     * @returns {NotifierView}
     */
    notifier() { return this._notifier; };
    /**
     * @returns {CollectionView}
     */
    collection() { return this._collection; };
    /**
     * @returns {UploaderView}
     */
    uploader() { return this._uploader; };
    /**
     * @returns {ToolbarView}
     */
    toolbar() { return this._toolbar; };
    /**
     * @returns {NavigatorView}
     */
    navigator() { return this._navigator; };
    /**
     * @returns {ClipFormView}
     */
    form(){ return this._form; }

    /**
     * @param {String} id 
     * @returns {ClipboardView}
     */
    refresh( id = ''){
        ClipTask.create('list', id ? {'id':id} : {}, (r) =>{
            this.collection().fill( r.items || [] );
        });
        return this;
    }



    onFileInput(){}
    onDragDrop(){}
    onDragStart(){}
    onDragEnd(){}
    onDragOver(){}
    onDrop(){}
    onCopy(){}
    onPaste(){}
}
/**
 * 
 */
class CollectionView extends CoderView {
    /**
     * @param {String} classname 
     */
    constructor(classname = '') {
        super();
        this._collection = [...document.getElementsByClassName(classname)][0] || null;
    }
    /**
     * @returns {Element}
     */
    collection(){ return this._collection; }
    /**
     * @param {Object[]} items 
     */
    fill( items = [] ){
        console.log(items);
        const collection = this.collection();
        if( collection){
            (items || []).map( data => ClipData.fromdata(data)).map( clip => new ClipView(clip) ).forEach( element => {
                collection.appendChild( element.render() );
            });
        }
    }
    /**
     * @returns {ClipView[]}
     */
    items(){ return Array.from(this.collection().childNodes()).map( element => ClipView.fromelement(element) ); }
}
/**
 * 
 */
class NotifierView extends CoderView {
    /**
     * @param {String} classname 
     */
    constructor(classname = '') {
        super();
        this._notifier = [...document.getElementsByClassName(classname)][0] || null;
    }
    /**
     * @returns {Element}
     */
    notifier() { return this._notifier; }
    /**
     * @param {String} content 
     * @param {String} type 
     * @returns {NotifierView}
     */
    show(content = '', type = 'info') {
        if (content && this.notifier()) {
            this.notifier().appendChild(this.html('div', { 'class': `is-dismissible notice type-${type}` }, content));
        }
        return this;
    }
}

/**
 * Use this new model instead ClipData
 */
class ClipView extends CoderView {
    /**
     * @param {String} id 
     * @param {String} parentid 
     * @param {String} name 
     * @param {String} type
     * @param {String} title 
     * @param {Number} slot 
     */
    constructor( data = null ) {
        super();
        this._data = data instanceof ClipData ? data : null;
        console.log(this.data());
    }
    /**
     * @returns {ClipData}
     */
    data() { return this._data; }
    /**
     * @returns {Boolean}
     */
    empty(){ return !this.data(); }
    /**
     * @returns {String}
     */
    dataurl(){ return !this.empty() ? this.public() + this.data().id() : ''; }
    /**
     * @returns {String}
     */
    clipboardurl(){ return `${this.public()}/clipboard/${this.id()}`;}
    /**
     * @returns {String}
     */
    adminurl(){ return `${this.url()}?page=coder_clipboard&id=${this.id()}`; }

    /**
     * @param {Element} element 
     * @returns {ClipData}
     */
    static fromelement( element = null ){
        if( element ){
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
     * @returns {Element}
     */
    content() {
        const element = this.html('div', { class: 'content' });
        //append item dusplay type (image, video, attachment ...)
        const content = ClipContentView.create(this.data());
        element.appendChild(content.render());
        //append title overlay (caption)
        element.appendChild(this.overlay());
        return element;
    }
    /**
     * @returns {Element[]}
     */
    buttons() {
        return [
            //add all action butttons here (use builtin methods to setup events)
            this.remove(),
            this.move(),
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
    move() {
        const element = this.html('span', { 'class': 'btn remove' }, this.text('move'));
        //add dashicons contents
        //add actions
        return element;
    }
    /**
     * @returns {Element}
     */
    remove() {
        const element = this.html('span', { 'class': 'btn remove' }, this.text('remove'));
        //add dashicons contents
        //add actions
        return element;
    }
    /**
     * @returns {Element}
     */
    render() {
        const element = this.html('li', {
            class: 'item clip',
        }, ClipContentView.create(this.data()));
        this.buttons().forEach(button => element.appendChild(button));
        return element;
    }
}
/**
 * 
 */
class ClipContentView extends CoderView {
    /**
     * 
     * @param {ClipData} clip 
     */
    constructor(clip = null) {
        super();
        this._clip = clip instanceof ClipData ? clip : null;
    }
    /**
     * @returns {ClipData}
     */
    data() { return this._clip; }
    /**
     * @returns {String}
     */
    attributes() {
        return {
            'class': `clipdata ${this.data().type()} ${this.data().tags().join(' ')}`,
            'data-id': this.data().id(),
        };
    }
    /**
     * @returns {Element}
     */
    render() { return this.html('span', this.attributes()); }
    /**
     * @param {ClipData} content 
     */
    static create(content = null) {
        if (content instanceof ClipData) {
            switch (true) {
                case content.isimage():
                    return new ClipImageView(content);
                case content.isvideo():
                    return new ClipMediaView(content);
                default:
                    return new ClipContentView(content);
            }
        }
    }
}
/**
 * 
 */
class ClipImageView extends ClipContentView {
    /**
     * @param {ClipData} clip 
     */
    constructor(clip = null) {
        this._clip = clip;
    }
}
/**
 * 
 */
class ClipMediaView extends ClipContentView {
    /**
     * @param {ClipData} clip 
     */
    constructor(clip = null) {
        this._clip = clip;
    }
}

/**
 * 
 */
class ClipFormView extends CoderView {
    /**
     * @param {String} classname 
     */
    constructor(classname = '') {
        super();
        this._form = [...document.getElementsByClassName(classname)][0] || null;
        this._clip = null;
    }
    /**
     * @param {ClipData} clip 
     * @returns {ClipFormView}
     */
    attach( clip = null ){
        this._clip = clip instanceof ClipData && clip || null;
        if( this.clip()){
            //refresh all data changes when the clip geets updated
            this.clip()._('update', clip => { this.refresh(clip); });
            //any time the form changes, send the data back to the clip
            this._('update', data => this.clip().refresh(data || {}));
            //load clip data
            ClipTask.create('load',{'id':this.clip().id()}, data => { this.clip().refresh(data.item || {}); });
        }
        return this;
    }
    /**
     * @param {ClipData} clip 
     * @returns {ClipFormView}
     */
    refresh( clip = null ){
        if( clip instanceof ClipData){
            //fill fporm data here
            console.log('Form Data ' , clip);
        }
        return this;
    }

    /**
     * @returns {ClipData}
     */
    clip(){ return this._clip; }
    /**
     * @returns {ClipFormView}
     */
    clear(){
        return this;
    }
}
/**
 * Manage tasks and uploads, update the progress with a progress bar
 */
class UploaderView extends CoderView {
    /**
     * @param {String} classname 
     */
    constructor(classname = '') {
        super();
        this._uploader = [...document.getElementsByClassName(classname)][0] || null;
        this._tasks = [];
    }
    /**
     * @returns {ClipTask[]}
     */
    tasks(active = false) { return active ? this.tasks().filter(t => !t.finished()) : this._tasks; }
    /**
     * @param {ClipTask} task 
     * @returns {UploaderView}
     */
    add(task = null) {
        if (task instanceof ClipTask) {
            const runloop = !this.count();
            this.tasks().push(task);
            runloop && this.update();
        }
        return this;
    }
    /**
     * @param {Boolean} active
     * @returns {Number}
     */
    count(active = false ) { return this.tasks(active).length; }
    /**
     * @returns {Boolean}
     */
    update() {
        const task = this.tasks(true)[0] || null;
        task && task.request();
        
        if( this.count(true)){
            //keep looping through
            return true;
        }
        //finish and report if required
        const summary = this.count();
        if( summary > 1 ){
            const completed = this.tasks().filter( t => t.completed() );
            CodersClipboard.notify(`${completed} / ${summary} tasks completed`,'update');
        }
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
}
/**
 * Component to build the gallery navigation hierarchy at the top
 */
class NavigatorView extends CoderView {
    /**
     * @param {String} classname 
     */
    constructor(classname = '') {
        super();
        this._nav = [...document.getElementsByClassName(classname)][0] || null;
    }
    /**
     * @returns {Element}
     */
    navigagor() { return this._nav; }
    /**
     * @returns {NavigatorView}
     */
    clear() { this.navigagor().innerHTML = ''; return this; }
    /**
     * @param {Object} pathdata 
     * @returns {NavigatorView}
     */
    render(pathdata = {}) {
        const nav = this.clear().navigagor();
        nav.appendChild('li', { 'class': 'item home' }, this.text('clipboard'));
        if (pathdata instanceof Object) {
            Object.keys(pathdata).forEach(id => {
                nav.appendChild(id ?
                    this.link({ 'id': id }, pathdata[id], 'link') :
                    this.html('span', { 'class': 'current' }, pathdata[id]));
            });
        }
        return this;
    }
    /**
     * @param {ClipData} content 
     * @returns {NavigatorView}
     */
    refresh(content = null) {

        if (content instanceof ClipData) {
            this.render(content.path());
        }

        return this;
    }
}
/**
 * Selective actions for the collection view
 */
class ToolbarView extends CoderView {
    /**
     * @param {String} classname 
     */
    constructor(classname = '') {
        super();
        this._toolbar = [...document.getElementsByClassName(classname)][0] || null;
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

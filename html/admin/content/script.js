
//Loader
document.addEventListener('DOMContentLoaded', function () {

    CodersClipboard.instance();

    //console.log(CodersClipboard.instance());
});

/**
 * @type {CoderEvents}
 */
<<<<<<< HEAD
class CoderEvents {
    /**
     * 
     */
    constructor() { this._e = {}; }
    /**
     * @returns {String[]}
     */
    __(){ return Object.keys(this._e); }
=======
class CoderEventHandler {
    constructor() {
        this._e = {};
    }
>>>>>>> 0ab99431254ed7665d1987f6fae15ac12acbba31
    /**
     * @param {String} e 
     * @param {Function} call 
     * @returns {CoderEvents}
     */
    _(e = '', call = null ) {
        if( e && typeof call === 'function' ){
<<<<<<< HEAD
            if( !this._e[e] ) this._e[e] = [];
            this._e[e].push( typeof call === 'function' && call || (data => console.log(data) ) );
=======
            this._e.push( e , call );
>>>>>>> 0ab99431254ed7665d1987f6fae15ac12acbba31
        }
        return this;
    }
    /**
     * 
     * @param {String} e 
     * @param {*} data 
     * @returns 
     */
<<<<<<< HEAD
    $(e = '', data = false ) {
        e && this._e[e] && this._e[e].forEach(call => call(data));
=======
    $(e, data = false ) {
        if (this._e[e]) {
            this._e[e].forEach(call => call(data));
        }
>>>>>>> 0ab99431254ed7665d1987f6fae15ac12acbba31
        return this;
    }
}

/**
 * 
 * @type CodersClipboard
 */
class CodersClipboard extends CoderEvents {
    /**
     * @returns {CodersClipboard}
     */
    constructor(){
        super();
        if( CodersClipboard.__instance ){
            return CodersClipboard.__instance;
        }
        CodersClipboard.__instance = this;
        
        this._clipboard = ClipboardContent.create();
        this.initialize(  );
    }
    /**
     * @param {ClipboardContent} cb
     * @returns {bool}
     */
    initialize(  ){

        this._strings = new CoderStrings();
        //this._view = 

        this._drive = 'content';
        if(this.clipboard().ready()){
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
     * @returns {CodersClipboard}
     */
    static instance(){
        return CodersClipboard.__intsance || new CodersClipboard();
    }
    /**
     * @param {String} text 
     * @returns {String}
     */
    static text(text = ''){ return this.instance().strings().get(text); }
    /**
     * @returns {CoderStrings}
     */
    strings(){ return this._strings; }
    /**
     * Selected drive
     * @returns {String}
     */
    drive(){
        return this._drive;
    }
    /**
     * @returns {ClipboardContent}
     */
    clipboard(){
        return this._clipboard;
    }
    /**
     * @returns {Element}
     */
    collection(){
        return this.clipboard() && this.clipboard().view().itemBox() || null;
    }
    /**
     * 
     */
    setupTabs(){
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
    
    setupFileInput(){
        const cb = this.clipboard();
        //file upload
        document.querySelectorAll('input[type=file]').forEach(input => {
            if(input.classList.contains('ajax')){
                //only allow on ajax mode, otherwise just use uploads button
                input.addEventListener('change', (e) => {
                    cb.upload(e.target.files);
                });
            }
        });        
    }
    setupDragDrop(){
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
    setupPaste(){
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
    setupDragEnd(){
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
    setupDragStart(){

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
            if( ghost ){
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
    setupDragOver(){
        const collection = this.collection();
        collection.addEventListener('dragover', (e) => {
            e.preventDefault(); // allow drop
        });
    }
    setupDrop(){
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
                    if ( slot !== false && source_slot !== slot) {
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
    setupCopy(){
        const copylink = document.querySelector('.copy-link');
        if( copylink ){
            copylink.addEventListener('click', function(e){
                e.preventDefault();
                const link = this.dataset.link || '';
                if( link ){
                    navigator.clipboard.writeText(link)
                    .then(() => {
                        ContentView.notify('URL copied to clipboard!','updated');
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
    static create( uploads = 'clipboard-box', items = 'collections' ){
        return new ClipboardContent(uploads , items);
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
    ready(){
        return !!(this.view().uploadBox() && this.view().itemBox());
    }
    /**
     * @returns {Boolean}
     */
    isClipboard(){
        return this.view().hasClipboard();
    }
    /**
     * @returns {Boolean}
     */
    isMain(){
        return !this.hasContext();
    }
    /**
     * @param {Boolean} listReady
     * @returns {ClipTask[]}
     */
    tasks( listReady = false ){
        return listReady ? this._tasks.filter(task => task.ready() ) : this._tasks;
    }
    /**
     * @returns {ClipboardContent}
     */
    reset(){
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
    hasContext(){
        return !!this.view().contextId();
    }
    /**
     * @returns {Boolean}
     */
    isHeader(){
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
    contextData(){
        const data = { action: 'clipboard' };
        if( this.hasContext()){
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
            this.view().attach( task );
            this.tasks().push( task );
        });
        this.view().clearEmptyBlock().busy();
        return this.wait2Next()
    }
    /**
     * @returns {ClipboardContent}
     */
    next() {
        const task = this.tasks(true)[0] || null;
        console.log('Next Task',task);
        task && task.send() || this.view().idle();
        return this;
    }
    /**
     * @returns {ClipboardContent}
     */
    wait2Next(){
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
        console.log('UPLOADED!!',response,task);
        if (response && response.content ) {
            const view = this.view();
            //console.log( this.isHeader(),this.isMain());
            if( this.isHeader() || !this.isMain()){
                response.content.forEach(item => {
                    view.createItem(item)
                    if( this.isMain()){
                        this.setHeader(item.id);
                    }
                });
            }
            //console.log( typeof task );
            const preview = task && task.ref() || null;
            if( preview ){
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
    setHeader( id = ''){
        if(id ){
            this.tasks(true).filter(task => task.hasAttachment()).forEach( task => task.data().id = id );
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
                {'id':id,'parent_id':parent_id,'context_id':this.contextId()},
                _view.remove.bind(_view));
            task.send();
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
                    {'id':id,'slot':slot,'context_id':this.contextId()},
                    _view.sort.bind(_view));
            task.send();
        }
        return this;
    }
}

/**
 * @type {ClipTask}
 */
class ClipTask {
    /**
     * @param {String} task 
     * @param {Object} data
     * @param {Function} callback
     */
    constructor(task = 'default', data = {} , callback = null) {
        this._status = ClipTask.Status.Ready;
        this._task = task || '';
        this._data = data || {};
        //this._contextId = context_id;
        this._ref = null;
        this._callback = callback || null;
        console.log('New Task',this);
    }
    /**
     * @returns {String}
     */
    url(){
        return ajaxurl;
    }
    /**
     * @returns {String}
     */
    status() {
        return this._status;
    }
    /**
     * @returns {Boolean}
     */
    ready(){
        return this.status() === ClipTask.Status.Ready && this.valid();
    }
    /**
     * @returns {Boolean}
     */
    running(){
        return this.status() === ClipTask.Status.Running;
    }
    /**
     * @returns {String}
     */
    task() {
        return this._task;
    }
    /**
     * @returns {Object|File}
     */
    data(){
        return this._data;
    }
    /**
     * @returns {Boolean}
     */
    hasData(){
        return Object.keys(this.data()).length > 0;
    }
    /**
     * @returns {Boolean}
     */
    hasAttachment(){
        return false;
    }
    /**
     * @returns {Boolean}
     */
    valid(){
        return !!this.task();
    }
    /**
     * @returns {Element}
     */
    ref(){
        return this._ref;
    }
    /**
     * @param {Object} ref 
     * @returns {ClipTask}
     */
    setRef( ref ){
        this._ref = ref;
        return this;
    }
    /**
     * @returns {Boolean}
     */
    hasRef(){
        return this.ref() !== null;
    }
    /**
     * @returns {ClipboardContent}
     */
    send() {
        if ( this.valid() ) {
            this._status = ClipTask.Status.Running;
            const formData = this.createForm(this.data());
            console.log( `Sending ${this.url()}`, formData );
            fetch(this.url(), { method: 'POST', body: formData })
                .then(res => res.json())
                .then(response => this.success(response.data))
                .catch(error => this.failure(error));
        }
        return this;
    }
    /**
     * @param {Object} response 
     */
    success( response = null ) {
        console.log( 'RESPONSE',response );
        if (response) {
            const callback = this._callback;
            if( typeof callback === 'function' ){
                callback(response , this );
                //callback(...Object.values(response) , this );
            }
            else{
                console.log(response , this);
            }
        }
        console.log('Task Completed', this );
        this._status = ClipTask.Status.Complete;
        return this;
    }
    /**
     * @param {Object} error 
     */
    failure(error) {
        console.log('Task Error', error, this );
        this._status = ClipTask.Status.Failed;
        return this;
    }
    /**
     * @param {Object} input 
     * @returns {FormData}
     */
    createForm(input = {}) {
        const content = new FormData();
        content.append('action', 'clipboard_action');
        content.append('task',this.task());
        if( this.hasAttachment()){
            content.append('upload',this.attachment());
        }
        Object.keys(input).forEach(key => content.append(key, input[key]));
        return content;
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
 * 
 */
class UploadTask extends ClipTask{

    constructor( file = null ,  data = {} , callback ){
        super('upload',data, callback );
        this._attachment = file instanceof File && file || null;
    }
    /**
     * @returns {File}
     */
    attachment(){
        return this._attachment || null;
    }
    /**
     * @returns {Boolean}
     */
    hasAttachment(){
        return !!this.attachment();
    }
}



/**
 * @type {ClipData}
 */
<<<<<<< HEAD
class ClipItem extends CoderEvents {
=======
class ClipData extends CoderEventHandler {
>>>>>>> 0ab99431254ed7665d1987f6fae15ac12acbba31
    /**
     * @param {String} id
     * @param {Number} slot
     * @param {String} parent
     * @returns {ClipData}
     */
    constructor( id = '', slot = 0 , parent = '') {
        super();
        this._id = id || '';
        this._slot = slot || 0;
        this._parent = parent || '';
        this._name = '';
        this._type = '';
        this._title = '';
        this._tags = [];
    }
    /**
     * @returns {String}
     */
    type(){ return this._type;}
    /**
     * @returns {String}
     */
    name(){ return this._name;}
    /**
     * @returns {String}
     */
    title(){ return this._title;}
    /**
     * @returns {String}
     */
    id(){ return this._id;}
    /**
     * @returns {String}
     */
    parent(){ return this._parent;}
    /**
     * @returns {String[]}
     */
    tags(){ return this._tags;}
    /**
     * @returns {Number}
     */
    slot(){ return this._slot;}
    /**
     * @returns {Boolean}
     */
    isimage(){
        return false;
    }
    /**
     * @returns {Boolean}
     */
    isvideo(){
        return false;
    }
    /**
     * @returns {Boolean}
     */
    isattachment(){
        return false;
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
    hasClipboard(){
        return this.itemBox() !== null && this.uploadBox() !== null;
    }
    /**
     * @returns {String}
     */
    importContext(){
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
        else{
            content.appendChild(ContentView.element('span',{'className':'dashicons dashicons-media-document'}));
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
    idle(){
        this.uploadBox().classList.remove('running');
        return this;
    }
    /**
     * @returns {ContentView}
     */
    busy(){
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
        console.log(this.itemBox(),`li.item[data-id="${id}"]`,this.itemBox().querySelector(`li.item[data-id="${id}"]`))
        return this.itemBox().querySelector(`li.item[data-id="${id}"]`);
    }
    /**
     * 
     * @param {String} id 
     * @returns {ClipboardContent}
     */
    remove( response = {}) {
        if ( response && response.id) {
            const item = this.getItem(response.id);
            console.log(response.id,item);
            if (item) item.remove();
        }
        return this;
    }
    sort( response = {} ) {
        if( response.id ){
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
    static notify( content , type = 'info'){
        const notifier = document.querySelector('.coders-clipboard .notifier') || null;
        if( notifier ){
            const message = ContentView.element('div',{
                'className':'is-dismissible notice type-' + type
            },content);
            notifier.appendChild(message);
            window.setTimeout( () => {
                message.remove();
            }, 2000 );
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
    text( text = ''){ return CodersClipboard.instance().strings().get(text); }
    /**
     * @returns {Element}
     */
    render() {
        //override
        return this.html('div', { 'class': 'empty' }, '');
    }
    /**
     * @returns {String}
     */
    url(){ return ajaxurl; }


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
    constructor(){
        super();
        this._collection = [...document.getElementsByClassName('clipboard-collection')][0] || null;
        this._uploader = [...document.getElementsByClassName('uploader')][0] || null;
        this._toolbar = [...document.getElementsByClassName('clipboard-toolbar')][0] || null;
        this._navigator = [...document.getElementsByClassName('clipboard-navigator')][0] || null;
    }
}
/**
 * 
 */
class CollectionView extends CoderView {
    /**
     * @param {String} classname 
     */
    constructor( classname = '' ){
        super();
        this._collection = [...document.getElementsByClassName(classname)][0] || null;
    }
}
/**
 * 
 */
class ClipView extends CoderView {
    /**
     * @param {ClipData} clip 
     */
    constructor( clip = null ){
        super();
        this._clip = clip instanceof ClipData ? clip : null;
    }
    /**
     * @returns {ClipData}
     */
    data(){ return this._clip; }
    /**
     * @returns {Element}
     */
    overlay(){
        return this.html('a',{
            'data-id':this.data().id(),
            'href' : this.data().post(),
        }, this.data().title());
    }
    /**
     * @returns {Element}
     */
    content(){
        const element = this.html('div',{class:'content'});
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
    buttons( ){
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
    count(){
        const element = this.html('span',{'class':'btn remove'},this.text('count'));
        //add dashicons contents
        return element;
    }
    /**
    /**
     * @returns {Element}
     */
    move(){
        const element = this.html('span',{'class':'btn remove'},this.text('move'));
        //add dashicons contents
        //add actions
        return element;
    }
    /**
     * @returns {Element}
     */
    remove(){
        const element = this.html('span',{'class':'btn remove'},this.text('remove'));
        //add dashicons contents
        //add actions
        return element;
    }
    /**
     * @returns {Element}
     */
    render(){
        const element = this.html('li',{
            class: 'item clip',
        },ClipContentView.create(this.data()));
        this.buttons().forEach( button => element.appendChild(button));
        return element;
    }
}

class ClipContentView extends CoderView{
    /**
     * 
     * @param {ClipData} clip 
     */
    constructor( clip = null ){
        super();
        this._clip = clip instanceof ClipData ? clip : null;
    }
    /**
     * @returns {ClipData}
     */
    data(){ return this._clip; }
    /**
     * @returns {String}
     */
    attributes(){ return {
        'class':`clipdata ${this.data().type()} ${this.data().tags().join(' ')}`,
        'data-id': this.data().id(),
    }; }
    /**
     * @returns {Element}
     */
    render(){ return this.html('span', this.attributes()); }
    /**
     * @param {ClipData} content 
     */
    static create(content = null){
        if( content instanceof ClipData){
            switch(true){
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
class ClipImageView extends ClipContentView{
    /**
     * @param {ClipData} clip 
     */
    constructor( clip = null){
        this._clip = clip;
    }
}
/**
 * 
 */
class ClipMediaView extends ClipContentView{
    /**
     * @param {ClipData} clip 
     */
    constructor( clip = null){
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
    constructor( classname = ''){
        super();
        this._form = [...document.getElementsByClassName(classname)][0] || null;
    }
}

class UploaderView extends CoderView {
    /**
     * @param {String} classname 
     */
    constructor( classname = ''){
        super();
        this._uploader = [...document.getElementsByClassName(classname)][0] || null;
    }
}

class NavigatorView extends CoderView {
    /**
     * @param {String} classname 
     */
    constructor( classname = ''){
        super();
        this._nav = [...document.getElementsByClassName(classname)][0] || null;
    }
}

class ToolbarView extends CoderView {
    /**
     * @param {String} classname 
     */
    constructor( classname = ''){
        super();
        this._toolbar = [...document.getElementsByClassName(classname)][0] || null;
    }
}


/**
 * 
 */
class CoderStrings{
    /**
     * 
     */
    constructor(){
        this._strings = {};
    }
    /**
     * @param {*} content 
     */
    fill( content = null ){
        Object.keys(content instanceof Object && content || {})
            //.filter()
            .forEach( key => this._strings[key] = content[key]);
    }
    /**
     * @param {String} text 
     * @returns {String}
     */
    get(text){ return text && this._strings[text] || text; } 
}

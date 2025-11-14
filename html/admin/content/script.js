
//Loader
document.addEventListener('DOMContentLoaded', function () {

    CodersClipboard.instance();

    //console.log(CodersClipboard.instance());
});


/**
 * 
 * @type CodersClipboard
 */
class CodersClipboard{
    /**
     * @returns {CodersClipboard}
     */
    constructor(){
        if( CodersClipboard.__instance ){
            return CodersClipboard.__instance;
        }
        this._clipboard = ClipboardContent.create();
        this.initialize(  );
    }
    /**
     * @returns {CodersClipboard}
     */
    static instance(){
        return CodersClipboard.__intsance || new CodersClipboard();
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
     * @param {ClipboardContent} cb
     * @returns {bool}
     */
    initialize(  ){
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
            input.addEventListener('change', (e) => {
                cb.upload(e.target.files);
            });
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
                        ClipboardView.notify('URL copied to clipboard!','updated');
                    })
                    .catch(err => {
                        ClipboardView.notify('Failed to copy: ', err);
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
        this._view = new ClipboardView(uploadBox, itemBox);
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
     * @returns {ClipboardView}
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
        return !this.view().hasContext();
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
        return this.view().hasContext();
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
     * @param {File[]} files 
     * @returns {ClipboardContent}
     */
    queue(files) {
        this._headerDone = false;
        Array.from(files).forEach(file => {
            const task = new ClipTask(
                'upload',
                this.hasContext() ? {'id':this.contextId()} : {},
                this.uploaded.bind(this) );
            task.attach(file);
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
        const ready = this.tasks(true);
        if( ready.length ){
            ready[0].send();
        }
        else{
            this.view().idle();
        }
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
        //console.log('UPLOADED!!',response,task);
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
            this.tasks(true).filter(task => task.hasAttachment()).forEach( task => task.content().id = id );
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
    constructor(task = '', data = null , callback = null) {
        this._status = ClipTask.Status.Ready;
        this._task = task || '';
        this._data = data || {};
        //this._contextId = context_id;
        this._ref = null;
        this._callback = callback || null;
        this._attachment = null;
    }
    /**
     * @returns {String}
     */
    url(){
        return ajaxurl;
    }
    /**
     * @param {File} file
     * @returns {ClipTask} 
     */
    attach( file ){
        if( file instanceof File ){
            this._attachment = file;
        }
        return this;
    }
    /**
     * @returns {File}
     */
    attachment(){
        return this._attachment || null;
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
    content(){
        return this._data;
    }
    /**
     * @returns {Boolean}
     */
    hasData(){
        return Object.keys(this.content()).length > 0;
    }
    /**
     * @returns {Boolean}
     */
    hasAttachment(){
        return this.attachment() && this.attachment() instanceof File;
    }
    /**
     * @returns {Boolean}
     */
    valid(){
        return this.task().length;
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
            const formData = this.createForm(this.content());
            console.log('Sending',this.content());
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
        if (response) {
            console.log( 'RESPONSE',response );
            const callback = this._callback;
            if( typeof callback === 'function' ){
                callback(response , this );
                //callback(...Object.values(response) , this );
            }
            else{
                console.log(response , this);
            }
        }
        return this.complete();
    }
    /**
     * @param {Object} content 
     * @param {Object} error 
     */
    failure(error) {
        console.log('ERROR', error, this );
        return this.complete();
    }
    /**
     * @returns {ClipTask}
     */
    complete(){
        this._status = ClipTask.Status.Complete;
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
    'Ready': 'ready',
    'Running': 'running',
    'Complete': 'complete',
};
/**
 * @type {ClipItem}
 */
class ClipItem {
    /**
     * @param {String} id
     * @param {Number} slot
     * @param {String} context
     * @returns {ClipItem}
     */
    constructor( id = '', slot = 0 , context = '') {
        this.id = id || '';
        this.slot = slot || 0;
        this.context_id = context || '';
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
 * @type {ClipboardView}
 */
class ClipboardView {
    /**
     * @param {String} uploadBox 
     * @param {String} itemBox 
     */
    constructor(uploadBox, itemBox) {
        //this._queue = document.getElementById(list);
        this._uploader = [...document.getElementsByClassName(uploadBox)][0] || null;
        this._collection = [...document.getElementsByClassName(itemBox)][0] || null;
        this._contextId = this.importContext();
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
        return this._contextId;
    }
    /**
     * @returns {Boolean}
     */
    hasContext() {
        return this.contextId().length > 0;
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
     * @returns {ClipboardView}
     */
    attach(task) {
        if (task && task.hasAttachment()) {
            const file = task.attachment();
            const item = ClipboardView.element('li', { 'className': 'item' });
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
        const item = ClipboardView.element('li', { 'className': 'item' });
        const content = ClipboardView.element('span', { 'className': 'content' });

        if (this.isMedia(itemData.type || '')) {
            content.appendChild(ClipboardView.element('img', {
                'src': itemData.link,
                'alt': itemData.name,
                'title': itemData.title || itemData.name,
                'className': 'media',
            }));
        }
        else{
            content.appendChild(ClipboardView.element('span',{'className':'dashicons dashicons-media-document'}));
        }

        content.appendChild(ClipboardView.element('a', {
            'href': itemData.post || '#',
            'target': '_self',
            'className': 'caption'
        }, itemData.title));

        item.appendChild(ClipboardView.element('span', { 'className': 'placeholder' }));
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
                    return ClipboardView.element('img', {
                        'className': 'content media',
                        'src': buffer,
                        'alt': file.name
                    });
                default:
                    return ClipboardView.element('span', {
                        'className': 'content attachment'
                    },
                        file.name);
            }
        }
        return ClipboardView.element('span', { 'className': 'content empty' });
    }
    /**
     * @returns {ClipboardView}
     */
    idle(){
        this.uploadBox().classList.remove('running');
        return this;
    }
    /**
     * @returns {ClipboardView}
     */
    busy(){
        this.uploadBox().classList.add('running');
        return this;
    }
    /**
     * 
     * @returns {ClipboardView}
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
            const message = ClipboardView.element('div',{
                'className':'is-dismissible notice type-' + type
            },content);
            notifier.appendChild(message);
            window.setTimeout( () => {
                message.remove();
            }, 2000 );
        }
    }
}





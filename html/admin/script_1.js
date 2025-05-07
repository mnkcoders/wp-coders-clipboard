/**
 * @class {CodersClipboard}
 */
class CodersClipboard {
    /**
     * @param {String} list 
     * @param {String} collection 
     */
    constructor(list = 'clipboard-box', collection = 'collections ') {
        this._ts = CodersClipboard.createTimeStamp();
        this._view = new ClipboardView(list, collection);
        this._list = document.getElementById(list);
        this._collection = document.getElementsByClassName(collection);

        const urlParams = new URLSearchParams(window.location.search);
        const id = urlParams.get('id');
        this._contextId = id || '';

        this._files = [];

        this._tasks = [];

        console.log(this);
    }
    /**
     * @returns {ClipboardView}
     */
    view() {
        return this._view;
    }
    /**
     * @param {Boolean} listReady
     * @returns {ClipTask[]}
     */
    tasks( listReady = false ){
        return listReady ? this._tasks.filter(task => task.ready() ) : this._tasks;
    }
    /**
     * @returns {CodersClipboard}
     */
    clear(){
        this._tasks = [];
        return this;
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
    hasContextId() {
        return this.contextId().length > 0;
    }
    /**
     * @returns {Boolean}
     */
    isMain() {
        return this.hasContextId();
    }
    /**
     * @returns {String}
     */
    static createTimeStamp() {
        return new Date().toISOString();
    }
    /**
     * 
     * @returns {String}
     */
    timestamp() {
        return this._ts;
    }
    /**
     * @returns {Element}
     */
    list() {
        return this._list;
    }
    /**
     * @returns {Element}
     */
    collection() {
        return [...this._collection][0] || null;
    }
    /**
     * @param {File[]} files 
     * @returns {CodersClipboard}
     */
    queue(files) {
        for (const file of files) {
            this.add(file);
        }
        /*files.map( file => new ClipTask('upload',file,this.uploaded)).forEach( task => {
            task.content().context_id = this.contextId();
            this.view().attach( task );
            this.tasks().push( task )
        });*/
        this.view().clear();
        return this.wait2Next()
    }
    /**
     * @param {File} file 
     * @returns {CodersClipboard}
     */
    add(file) {
        const item = document.createElement('li');
        item.className = 'item';
        //item.textContent = `Uploading: ${file.name}`;
        const reader = new FileReader();
        reader.onload = e => {
            const preview = CodersClipboard.preview(file, e.target.result);
            item.appendChild(preview);
        };
        this.list().appendChild(item);
        file._uploadElement = item; // Store reference
        reader.readAsDataURL(file)

        this._files.push(file);
        return this;
    }
    /**
     * @returns {CodersClipboard}
     */
    next() {
        return this._files.length ? this.onUpload(this._files.shift()) : this;

        const ready = this.tasks(true);
        if( ready.length ){
            ready.shift().send();
        }
        return this;
    }
    /**
     * @returns {CodersClipboard}
     */
    wait2Next(){
        window.setTimeout(() => { this.next() }, 300);
        return this;
    }
    /**
     * 
     * @param {Object} response 
     * @param {ClipTask} task 
     * @returns {CodersClipboard}
     */
    uploaded(response, task = null) {
        console.log('UPLOADED!!',response);
        if (response) {
            const container = task && task.reference() || null;
            (response.content || []).forEach(item => this.createItem(item));
            if( container ){
                container.remove();
            }
            this.wait2Next();
        }
        return this;
    }
    /**
     * @returns {Element}
     */
    static preview(file, buffer) {
        if (file instanceof File) {
            switch (file.type) {
                case 'image/png':
                case 'image/gif':
                case 'image/jpeg':
                    const image = document.createElement('img');
                    image.className = 'content media';
                    image.src = buffer;
                    image.alt = file.name;
                    return image;
                default:
                    const attachment = document.createElement('span');
                    attachment.className = 'content attachment';
                    attachment.textContent = file.name;
                    return attachment;
            }
        }
        const empty = document.createElement('span');
        empty.className = 'content empty';
        return empty;
    }
    /**
     * @param {File} file 
     * @returns {CodersClipboard}
     */
    onUpload(file) {
        const formData = new FormData();
        formData.append('action', 'clipboard_action');
        formData.append('task', 'upload');
        formData.append('upload', file);
        if (this.hasContextId()) {
            formData.append('id', this.contextId());
        }

        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(json => this.afterUpload(file, json.data || null))
            .catch(err => this.failed(file, err));

        return this;
    }
    /**
     * 
     * @param {File} file 
     * @param {Object} response 
     */
    afterUpload(file, response) {
        if (response) {
            //console.log('UPLOADED!!',response);
            const container = file._uploadElement;
            (response.content || []).forEach(item => this.createItem(item));
            container.remove();
            this.wait2Next();
        }
    }
    /**
     * @param {File} file 
     * @param {String} error 
     */
    failed(file, error) {
        const container = file._uploadElement;
        container.textContent = `Failed: ${file.name}`;
        container.classList.add('error');
        console.error('ERROR', file, error);
        this.wait2Next();
    }
    /**
     * @param {Object} itemData 
     * @returns {CodersClipboard}
     */
    createItem(itemData) {
        if (!this.isMain() || !this.hasContextId()) {
            this.collection() && this.collection().appendChild(CodersClipboard.showItem(itemData));
        }
        if (!this.hasContextId() && itemData.id) {
            //console.log(this.contextId(),itemData.id,itemData.parent_id);
            this._contextId = itemData.id;
        }
        return this;
    }
    /**
     * @param {Object} itemData 
     * @returns {Element}
     */
    static showItem(itemData) {
        //console.log(itemData,CodersClipboard.isMedia(itemData.type || ''));
        const item = document.createElement('li');
        item.className = 'item';
        const placeholder = document.createElement('span');
        placeholder.className = 'placeholder';
        const content = document.createElement('span');
        content.className = 'content';

        if (CodersClipboard.isMedia(itemData.type || '')) {
            const image = document.createElement('img');
            image.src = itemData.link;
            image.alt = itemData.name;
            image.title = itemData.title;
            image.className = 'media';
            content.appendChild(image);
        }

        const link = document.createElement('a');
        link.href = itemData.post || '#';
        link.target = '_self';
        link.className = 'cover';
        link.textContent = itemData.title;

        content.appendChild(link);
        item.appendChild(placeholder);
        item.appendChild(content);

        return item;
    }
    /**
     * @param {String} type 
     * @returns {Boolean}
     */
    static isMedia(type = '') {
        switch (type) {
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/gif':
            case 'image/png':
                return true;
        }
        return false;
    }




    /**
     * 
     * @param {DataTransferItem[]} items 
     * @returns {CodersClipboard}
     */
    upload(items = []) {
        //console.log('UPLOADING... ', items);
        if (items.length) {
            this.queue(items);
        }
        return this;
    }
    /**
     * @param {Blob} blob 
     * @param {String} filename 
     * @returns {CodersClipboard}
     */
    paste(blob, filename = '') {
        if (filename.length === 0) {
            filename = CodersClipboard.createTimeStamp();
        }
        const file = new File([blob], filename, { type: blob.type });
        return this.queue([file]);
    }

    /**
     * 
     * @param {String} id 
     * @param {String} parent_id 
     * @returns {CodersClipboard}
     */
    move(id = '', parent_id = '') {
        if (id) {
            console.log(`Moving [${id}] to [${parent_id || 'ROOT'}]`);
            this.onUpdate('move', { 'id': id, 'parent_id': parent_id }, this.removeItem);
            
            //const task = new ClipTask('move',{'id':id,'parent_id':parent_id'context_id':this.contextId()},this.view().remove);
            //task.send(this.view().remove);
        }

        return this;
    }
    /**
     * @param {String} id 
     * @param {Number} slot 
     * @returns {CodersClipboard}
     */
    sort(id = '', slot = 0) {
        if (id) {
            console.log(`Moving [${id}] to slot [${slot}]`);
            this.onUpdate('sort', { 'id': id, 'slot': slot }, this.moveToSlot);
            
            //const task = new ClipTask('sort',{'id':id,'slot':slot,'context_id':this.contextId()},this.view().sort);
            //task.send(this.view().sort);
        }
        return this;
    }

    /**
     * @param {String} task
     * @param {Object} data
     * @returns {CodersClipboard}
     */
    onUpdate(task = '', data, callback = null) {
        const formData = new FormData();

        Object.keys(data).forEach(key => formData.append(key, data[key]));

        if (this.hasContextId()) {
            formData.append('context_id', this.contextId());
        }
        formData.append('action', 'clipboard_action');
        formData.append('task', task || '');

        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(json => this.afterUpdate(json.data || null, callback))
            .catch(err => this.updateFailed(task, data, err));

        return this;
    }
    /**
     * 
     * @param {String} task 
     * @param {Object} data 
     * @param {Object} error 
     * @returns {CodersClipboard}
     */
    updateFailed(task, data, error) {
        console.log('Error', task, data, error);
        return this;
    }
    /**
     * @param {String} task 
     * @param {Object} response 
     * @param {Function} callback 
     * @returns {CodersClipboard}
     */
    afterUpdate(response, callback = null) {
        console.log(response);
        if (typeof callback === 'function') {
            const data = Object.values(response);
            callback.call(this, ...data);
        }
        return this;
    }
    /**
     * 
     * @param {String} id 
     * @returns {Element}
     */
    getItem(id) {
        return this.collection().querySelector(`li.item[data-item="${id}"]`);
    }
    /**
     * 
     * @param {String} id 
     * @returns {CodersClipboard}
     */
    removeItem(id = '') {
        if (id) {
            const item = this.getItem(id);
            console.log(item);
            if (item) item.remove();
        }
        return this;
    }
    moveToSlot(id = '', slot = 0) {

        const item = this.getItem(id);

        const placeholders = this.collection().querySelectorAll('.placeholder');

        // Find the placeholder by slot index
        const target = [...placeholders].find(p => p.dataset.slot == slot);
        if (!target) return;

        // Detach the item
        item.remove();

        // Insert before the target placeholder's parent (which is the target li.item)
        const selected = target.closest('li.item');
        if (selected) {
            this.collection().insertBefore(item, selected);
        } else {
            // If no item found (e.g., last placeholder), just append
            this.collection().appendChild(item);
        }
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
    hasFile(){
        return this.content() instanceof File;
    }
    /**
     * @returns {Boolean}
     */
    valid(){
        return this.task().length && this.hasData();
    }
    /**
     * @returns {Element}
     */
    reference(){
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
        return this.reference() !== null;
    }
    /**
     * @returns {CodersClipboard}
     */
    send() {
        if ( this.valid() ) {
            this._status = ClipTask.Status.Running;
            const formData = this.createForm(this.content());

            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(response => this.success(response.data))
                .catch(error => this.failure(data, error));
        }
        return this;
    }
    /**
     * @param {Object} response 
     */
    success( response = null ) {
        if (response) {
            const callback = this._callback;
            if( callback ){
                callback.call(this, response , this._data );
            }
            else{
                console.log(response , this._data);
            }
        }
        return this.complete();
    }
    /**
     * @param {Object} content 
     * @param {Object} error 
     */
    failure(content, error) {
        console.log('ERROR', error, this.task(), content);
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
        content.action = 'clipboard_action';
        content.task = this.task();
        if (input instanceof File) {
            //create upload package
            //content.task = 'upload';
            content.upload = input;
            if( input.hasOwnProperty('context_id')){
                content.context_id = input.context_id;
            }
        }
        else {
            //just prepare all input fields
            Object.keys(input).forEach(key => content.append(key, input[key]));
        }
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
    constructor() {

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
            var slice = byteCharacters.slice(offset, offset + 512);

            var byteNumbers = new Array(slice.length);
            for (var i = 0; i < slice.length; i++) {
                byteNumbers[i] = slice.charCodeAt(i);
            }

            var byteArray = new Uint8Array(byteNumbers);
            byteArrays.push(byteArray);
        }

        return new Blob(byteArrays, { type: contentType });
    }    
}
/**
 * @type {ClipboardView}
 */
class ClipboardView {
    /**
     * @param {String} list 
     * @param {String} collection 
     */
    constructor(list, collection) {
        this._queue = document.getElementById(list);
        this._collection = document.getElementsByClassName(collection);

        this._contextId = this.importContext();
    }
    /**
     * @returns {String}
     */
    importContext(){
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id') || '';
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
     * @returns {Boolean}
     */
    isMain() {
        return !this.hasContext();
    }
    /**
     * @returns {Element}
     */
    queue() {
        return this._queue;
    }
    /**
     * @returns {Element}
     */
    collection() {
        return [...this._collection][0] || null;
    }
    /**
     * @param {String} name 
     * @param {Object} attributes 
     * @param {String} content
     * @returns {Element}
     */
    element(name = 'span', attributes = {}, content = '') {
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
        if (task && task.hasFile()) {
            const file = task.content();
            const item = this.element('li', { 'className': 'item' });
            const reader = new FileReader();
            reader.onload = (e) => {
                const preview = this.preview(file, e.target.result);
                item.appendChild(preview);
            };
            this.queue().appendChild(item);
            task.setRef(item); // Store reference
            reader.readAsDataURL(file)
        }
        return this;
    }
    /**
     * @param {Object} itemData 
     * @returns {ClipboardView}
     */
    addItem(itemData = {}) {
        if (!this.isMain() || !this.hasContext()) {
            this.collection() && this.collection().appendChild(this.createItem(itemData));
        }
        if (!this.hasContext() && itemData.id) {
            //console.log(this.contextId(),itemData.id,itemData.parent_id);
            this._contextId = itemData.id;
        }
        return this;
    }
    /**
     * @param {Object} itemData 
     * @returns {Element}
     */
    createItem(itemData = {}) {
        //console.log(itemData,CodersClipboard.isMedia(itemData.type || ''));
        const item = this.element('li', { 'className': 'item' });
        const content = this.element('span', { 'className': 'content' });

        if (this.isMedia(itemData.type || '')) {
            content.appendChild(this.element('img', {
                'src': itemData.link,
                'alt': itemData.name,
                'title': itemData.title || itemData.name,
                'className': 'media',
            }));
        }

        content.appendChild(this.element('a', {
            'href': itemData.post || '#',
            'target': '_self',
            'className': 'cover'
        }, itemData.title));

        item.appendChild(this.element('span', { 'className': 'placeholder' }));
        item.appendChild(content);

        return item;
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
                    return this.element('img', {
                        'className': 'content media',
                        'src': buffer,
                        'alt': file.name
                    });
                default:
                    return this.element('span', {
                        'className': 'content attachment'
                    },
                        file.name);
            }
        }
        return this.element('span', { 'className': 'content empty' });
    }
    /**
     * 
     * @returns {ClipboardView}
     */
    clear() {
        const empty = this.collection().querySelector('li.empty');
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
        return this.collection().querySelector(`li.item[data-item="${id}"]`);
    }
    /**
     * 
     * @param {String} id 
     * @returns {CodersClipboard}
     */
    remove(id = '') {
        if (id) {
            const item = this.getItem(id);
            if (item) item.remove();
        }
        return this;
    }
    sort(id = '', slot = 0) {

        const item = this.getItem(id);
        const placeholders = this.collection().querySelectorAll('.placeholder');

        // Find the placeholder by slot index
        const target = [...placeholders].find(p => p.dataset.slot == slot);
        if (!target) return;

        // Detach the item
        item.remove();

        // Insert before the target placeholder's parent (which is the target li.item)
        const selected = target.closest('li.item');
        if (selected) {
            this.collection().insertBefore(item, selected);
        } else {
            // If no item found (e.g., last placeholder), just append
            this.collection().appendChild(item);
        }
    }
}


//Loader
document.addEventListener('DOMContentLoaded', function () {

    const cb = new CodersClipboard();
    const collection = cb.collection();


    // File input
    document.querySelectorAll('input[type=file]').forEach(input => {
        input.addEventListener('change', (e) => {
            const items = e.target.files;
            cb.upload(e.target.files);
        });
    });

    // Drag-drop
    document.addEventListener('dragover', e => e.preventDefault());
    document.addEventListener('drop', e => {
        e.preventDefault();
        if (e.dataTransfer.files.length) {
            const items = e.dataTransfer.files;
            cb.upload(items);
        }
    });

    // Paste
    document.addEventListener('paste', (e) => {
        const files = e.clipboardData.files;
        if (files.length) {
            cb.upload(files);
        }
        else {
            cb.upload((e.clipboardData.items || [])
                .filter(item => item.kind === 'file')
                .map(item => item.getAsFile() || null)
                .filter(item => item !== null));
        }
    });

    document.addEventListener('dragstart', (e) => {

        const item = e.target.closest('li.item');
        if (!item || !collection.contains(item)) return;

        collection.classList.add('move');
        item.classList.add('moving');

        const item_id = item.querySelector('.caption').dataset.id;
        const slot = item.querySelector('.placeholder').dataset.slot;

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('application/json', JSON.stringify({ 'id': item_id, 'slot': slot })); // store item ID
        console.log('DRAG!!', item_id, slot);

        // Create a custom drag image
        //const ghost = e.target.cloneNode(true);
        const image = e.target.closest('img.media');
        const ghost = image && image.cloneNode(true) || e.target.cloneNode(true);
        console.log('ghost', e.target);
        ghost.style.position = 'absolute';
        ghost.style.top = '-1000px';
        ghost.style.left = '-1000px';
        document.body.appendChild(ghost);
        e.dataTransfer.setDragImage(ghost, 0, 0);

        // Clean up later
        setTimeout(() => ghost.remove(), 0);
    });

    document.addEventListener('dragend', (e) => {
        collection.classList.remove('move');
        const source = collection.querySelector('li.item.moving');
        source.classList.remove('moving');
    });

    collection.addEventListener('dragover', (e) => {
        e.preventDefault(); // allow drop
    });
    collection.addEventListener('drop', (e) => {
        const target = e.target;
        const data = JSON.parse(e.dataTransfer.getData('application/json') || '{}');
        const source_id = data.id;
        const source_slot = parseInt(data.slot);

        const action = target.classList.contains('placeholder') && 'sort' || target.classList.contains('cover') && 'move' || '';

        switch (action) {
            case 'move':
                const target_id = target.dataset.id;
                if (source_id !== target_id) {
                    //console.log(`Moving ${source_id} to ${target_id}`);
                    cb.move(source_id, target_id);
                }
                break;
            case 'sort':
                const slot = target.dataset.slot;
                if (source_slot !== parseInt(slot)) {
                    //console.log(`Moving ${source_id} to slot ${slot}`);
                    cb.sort(source_id, slot);
                }
                break;
            default:
                console.log(`No target selected`);
                break;
        }
    });
});





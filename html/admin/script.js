/**
 * @class {CodersClipboard}
 */
class CodersClipboard {
    /**
     * @param {String} container 
     * @param {String} collection 
     */
    constructor( container = 'clipboard-box' , collection = 'collections ') {
        this._ts = new Date().toISOString();
        this._list = document.getElementById(container);
        this._collection = document.getElementsByClassName(collection);

        const urlParams = new URLSearchParams(window.location.search);
        const id = urlParams.get('id');
        this._contextId = id || '';
        
        this._root = !this.hasContextId();

        this._files = [];

        console.log(this);
    }
    /**
     * @returns {String}
     */
    contextId(){
        return this._contextId;
    }
    /**
     * @returns {Boolean}
     */
    hasContextId(){
        return this.contextId().length > 0;
    }
    /**
     * @returns {Boolean}
     */
    isRoot(){
        return this._root;
    }
    /**
     * 
     * @returns {String}
     */
    timestamp(){
        return this._ts;
    }
    /**
     * @returns {Element}
     */
    list(){
        return this._list;
    }
    /**
     * @returns {Element[]}
     */
    collection(){
        return [...this._collection];
    }
    /**
     * @param {File[]} files 
     * @returns {CodersClipboard}
     */
    queue(files) {
        for (const file of files) {
            this.add(file);
        }
        return this.next(true);
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
            const preview = CodersClipboard.preview( file,e.target.result);
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
    next( wait = false){
        if( wait ){
            window.setTimeout( () => { this.next() } , 300 );
            return this;
        }
        return this._files.length ? this.send(this._files.shift()) : this;
    }
    /**
     * @returns {Element}
     */
    static preview( file , buffer ){
        if( file instanceof File ){
            switch( file.type ){
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
    send(file) {
        const formData = new FormData();
        formData.append('action', 'clipboard_action');
        formData.append('task','upload');
        formData.append('upload', file);
        if( this.hasContextId() ){
            formData.append('id',this.contextId());
        }

        fetch(ajaxurl, {method: 'POST',body: formData})
            .then(res => res.json())
            .then(json => this.completed(file, json.data || null) )
            .catch(err => this.failed(file, err) );

        return this;
    }
    /**
     * 
     * @param {File} file 
     * @param {Object} response 
     */
    completed(file, response) {
        if( response ){
            //console.log('UPLOADED!!',response);
            const container = file._uploadElement;
            //container.classList.add('uploaded');
            //container.textContent = `Uploaded: ${file.name}`;
            // You can now add it to the current item collection if needed
            (response.content || []).forEach( item => this.createItem( item ));
            container.remove();
            this.next(true);
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
        console.error('ERROR',file,error);
        this.next(true);
    }
    /**
     * @param {Object} itemData 
     * @returns {CodersClipboard}
     */
    createItem( itemData ){
        if( !this.isRoot() || !this.hasContextId() ){
            this.collection().forEach( box => box.appendChild(CodersClipboard.showItem(itemData)));
        }
        if( !this.hasContextId() && itemData.id ){
            //console.log(this.contextId(),itemData.id,itemData.parent_id);
            this._contextId = itemData.id;
        }
        return this;
    }
    /**
     * @param {Object} itemData 
     * @returns {Element}
     */
    static showItem( itemData ){
        //console.log(itemData,CodersClipboard.isMedia(itemData.type || ''));
        const item = document.createElement('li');
        item.className = 'item';
        const placeholder = document.createElement('span');
        placeholder.className = 'placeholder';
        const content = document.createElement('span');
        content.className = 'content';

        if( CodersClipboard.isMedia(itemData.type || '') ){
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
    static isMedia( type = ''){
        switch( type ){
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/gif':
            case 'image/png':
                return true;
        }
        return false;
    }


    /**
     * @param {String} data
     * @returns {String}
     */
    getMime = function( data ){
        var matches = data.match(/^data:(.*?);base64,/);
        return matches !== null ? matches[1] : 'text/plain';
    };
    /**
     * @param {String} base64String
     * @returns {Blob}
     */
    base64ToBlob = function(base64String) {
        const contentType = this.getMime( base64String );
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

    /**
     * 
     * @param {DataTransferItem[]} items 
     * @returns {CodersClipboard}
     */
    static upload( items = [] ){
        const clipboard = new CodersClipboard();
        if( items.length ){
            clipboard.queue( items );
        }
        return clipboard;
    }
    /**
     * @param {Blob} blob 
     * @param {String} filename 
     * @returns {CodersClipboard}
     */
    static pasteClipboard(blob, filename = '') {
        const clipboard = new CodersClipboard();
        if( filename.length === 0){
            filename = clipboard.timestamp();
        }
        const file = new File([blob], filename, { type: blob.type });
        clipboard.queue([file]);
        return clipboard;
    }
}


//Loader
document.addEventListener('DOMContentLoaded', function () {

        // File input
        document.querySelectorAll('input[type=file]').forEach(input => {
            input.addEventListener('change', (e) => {
                const items = e.target.files;
                const clipboard = CodersClipboard.upload(e.target.files);
                //this.queue(e.target.files);
                console.log(clipboard,items);
            });
        });

        // Drag-drop
        document.addEventListener('dragover', e => e.preventDefault());
        document.addEventListener('drop', e => {
            e.preventDefault();
            if (e.dataTransfer.files.length) {
                const items = e.dataTransfer.files;
                const clipboard = CodersClipboard.upload(items);
                //this.queue(e.dataTransfer.files);
                //console.log(clipboard,items);
            }
        });

        // Paste
        document.addEventListener('paste', (e) => {
            const files = e.clipboardData.files;
            if( files.length ){
                const clipboard = CodersClipboard.upload(files);
                //console.log(clipboard,files);
            }
            else{
                const items = (e.clipboardData.items || [])
                .filter( item => item.kind === 'file')
                .map( item => item.getAsFile() || null )
                .filter( item => item !== null );
                const clipboard = CodersClipboard.upload( items );
                //console.log(clipboard,items);
            }
        });    

        document.addEventListener('dragstart', (e) => {
            console.log('DRAG!!');
            const clipboard = new CodersClipboard();
            clipboard.collection().forEach( c => c.classList.add('move'));
        });
        document.addEventListener('dragend', (e) => {
            console.log('DROP!!');
            const clipboard = new CodersClipboard();
            clipboard.collection().forEach( c => c.classList.remove('move'));
        });

    //capture the on-paste event
    /*document.onpaste = function (event) {
        const items = Array.from((event.clipboardData || event.originalEvent.clipboardData).items);
        const clipboard = CodersClipboard.upload(items.filter(item => item.kind === 'file') );
        console.log(clipboard,items);
    }*/
});






/**
 * @returns {CodersClipBoard}
 */
function CodersClipBoard(){
    /**
     * @type Element
     */
    this._collection = document.getElementById('clipboard-capture');
    /**
     * @type FormData
     */    
    this._formData = new FormData();
    /**
     * @type Number
     */
    this._items = 0;
    
    this._ts = this.createTimeStamp();
};
/**
 * @returns {String}
 */
CodersClipBoard.prototype.createTimeStamp = function(){
    // Extract date components
    const date = new Date();
    const year = date.getFullYear();
    const month = ('0' + (date.getMonth() + 1)).slice(-2); // Month is zero-based, so add 1
    const day = ('0' + date.getDate()).slice(-2);
    const hour = ('0' + date.getHours()).slice(-2);
    const minute = ('0' + date.getMinutes()).slice(-2);
    const second = ('0' + date.getSeconds()).slice(-2);
    // Construct formatted date string
    return `${year}-${month}-${day}-${hour}-${minute}-${second}`;
};
/**
 * @returns {Number}
 */
CodersClipBoard.prototype.ts = function(){
    return this._ts;
};
/**
 * @returns {Number}
 */
CodersClipBoard.prototype.items = function(){
    return this._items;
};
/**
 * @returns {Element}
 */
CodersClipBoard.prototype.collection = function(){
    return this._collection;
};
/**
 * @returns {FormData}
 */
CodersClipBoard.prototype.form = function(){
    return this._formData;
};
/**
 * @returns {FormData}
 */
CodersClipBoard.prototype.upload = function(){
    // Send FormData via AJAX to WordPress
    var ajaxUrl = 'http://yourwordpresssite.com/wp-admin/admin-ajax.php';
    jQuery.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: this.form(),
        contentType: false,
        processData: false,
        success: function(response) {
            console.log('Files uploaded successfully:', response);
        },
        error: function(xhr, status, error) {
            console.error('Error uploading files:', error);
        }
    });
};
/**
 * @returns {CodersClipBoard}
 */
CodersClipBoard.prototype.uploadWhenReady = function(){
    
    return this;
};
/**
 * @param {Boolean} show 
 * @returns {CodersClipBoard}
 */
CodersClipBoard.prototype.toggle = function( show ){
    if( typeof show === 'boolean' && show ){
        this.collection().classList.remove('hidden');
    }
    else{
        this.collection().classList.add('hidden');
    }
    return this;
};
/**
 * @returns {CodersClipBoard}
 */
CodersClipBoard.prototype.show = function(){
    this.collection().classList.remove('hidden');
    return this;
};
/**
 * @returns {CodersClipBoard}
 */
CodersClipBoard.prototype.hide = function(){
    this.collection().classList.add('hidden');
    return this;
};
/**
 * @param {String} data
 * @returns {String}
 */
CodersClipBoard.prototype.getMime = function( data ){
    var matches = data.match(/^data:(.*?);base64,/);
    return matches !== null ? matches[1] : 'text/plain';
};
/**
 * @param {String} base64String
 * @returns {Blob}
 */
CodersClipBoard.prototype.base64ToBlob = function(base64String) {
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
 * @param {String} buffer
 * @param {Number} index
 * @returns {Element|String}
 */
CodersClipBoard.prototype.createImage = function( buffer , index ){
    if( typeof buffer === 'string' && buffer.length ){
        const img =  document.createElement('img');
        img.src = buffer;
        img.title = 'Clipboard';
        img.alt = this.ts() + '-' + index;
        img.setAttribute('data-id',index);
        img.setAttribute('data-type',this.getMime(buffer));
        return img;
    }
    return '<!-- empty -->';
};
/**
 * @param {String} content
 * @returns {CodersClipBoard}
 */
CodersClipBoard.prototype.addItem = function( content ){
    this._items++;
    const li = document.createElement("li");
    li.appendChild( this.createImage(content,this.items()));
    //li.setAttribute('data-id',this.items());
    this.collection().appendChild(li);
    
    const blob = this.base64ToBlob(content); // Adjust MIME type if needed
    this.form().append('file[]', blob, 'image_' + this.items() + '.jpg');
    
    return this;
};

//Loader
document.addEventListener('DOMContentLoaded', function () {

    document.getElementById('upload-button').addEventListener('click', function(e){
        e.preventDefault();
        
        return true;
    });

    document.onpaste = function (event) {
        const items = Array.from((event.clipboardData || event.originalEvent.clipboardData).items);
        const clipboard = new CodersClipBoard();
        
        clipboard.toggle(items.filter( item => item.kind === 'file' ).length > 0);
        
        console.log(items); // will give you the mime types
        for( var i = 0 ; i < items.length ; i++ ){
            if( items[i].kind === 'file' ){
                var blob = items[i].getAsFile();
                var reader = new FileReader();
                reader.onload = function (event) {
                    console.log(event.target.result);
                    clipboard.addItem(event.target.result).uploadWhenReady();
                }; // data url!
                reader.readAsDataURL(blob);                
            }
        }
    }
});


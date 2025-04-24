<?php defined('ABSPATH') or die ?>
<!-- CODERS CLIPBOARD CONTAINER OPENER -->
<div class="wrap coders-clipboard main">
    <h1 class="wp-heading-inline"><?php print get_admin_page_title() ?></h1>
    <p><?php print self::class; ?></p>
    <div class="clipboard container">
        <div class="uploader container">
            <form name="uploader" action="<?php print $this->url_upload ?>" method="post" enctype="multipart/form-data">
                <label for="id_files" class="button files">Select files</label>
                <input id="id_files" type="file" name="upload[]" class="hidden" />
                <button type="submit" name="upload" id="upload-button">Upload</button> 
            </form>
        </div>
        <ul class="image-clipboard inline hidden" id="clipboard-capture">
            <!-- add images from clipboard here -->
        </ul>
    </div>
    <div class="uploader">
        
    </div>
</div>
<!-- CODERS CLIPBOARD CONTAINER CLOSER -->
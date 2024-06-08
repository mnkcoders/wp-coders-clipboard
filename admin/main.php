<?php defined('ABSPATH') or die ?>
<!-- CODERS CLIPBOARD CONTAINER OPENER -->
<div class="wrap coders-clipboard main">
    <h1 class="wp-heading-inline"><?php print get_admin_page_title() ?></h1>
    <p><?php print self::class; ?></p>
    <p><?php print CODERS_CLIPBOARD; ?></p>
    <div class="clipboard">
        <ul class="image-clipboard inline hidden" id="clipboard-capture">
            <!-- add images from clipboard here -->
        </ul>
        <button type="button" name="upload" id="upload-button">Upload</button>
    </div>
    <div class="uploader">
        
    </div>
</div>
<!-- CODERS CLIPBOARD CONTAINER CLOSER -->
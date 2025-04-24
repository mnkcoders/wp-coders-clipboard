<?php defined('ABSPATH') or die; ?>
<form name="upload" action="<?php print $this->get_form() ?>" method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('clipboard_upload'); ?>                
    <label for="clipboard-files">
        <input id="clipboard-files" type="file" name="upload[]" multiple="multiple" />
    </label>
    <button class="right button button-primary" type="submit" name="action" value="clipboard_upload">
        <span class="dashicons dashicons-upload"></span>                        
        <?php print __('Drag or select your files here to upload!', 'coders_clipboard'); ?>                        
    </button>
    <?php if ($this->is_valid()) : ?>
        <input type="hidden" name="parent_id" value="<?php print $this->id ?>">
    <?php endif; ?>
</form>
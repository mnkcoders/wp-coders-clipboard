<?php defined('ABSPATH') or die; ?>
<div class="fullwitdh container solid upload">
    <div class="fullwitdh drag-drop container centered">
        <form name="upload" action="<?php print $this->get_form() ?>" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('clipboard_upload'); ?>                
            <label for="clipboard-files" class="button-primary wide">
                <span class="dashicons dashicons-upload"></span>
                <span><?php print __('Drag or select your files here') ?></span>
                <input id="clipboard-files"
                       type="file"
                       class="<?php print $this->get_mode() ?>"
                       name="upload[]"
                       multiple="multiple" />        
            </label>
            <?php if( !$this->is_ajaxmode() ): ?>
            <button class="button button-primary wide right" type="submit" name="action" value="upload">
                <span class="dashicons dashicons-upload"></span>                        
                <?php print __('Upload!', 'coder_clipboard'); ?>                        
            </button>
            <?php endif; ?>
            <?php if ($this->is_valid()) : ?>
                <input type="hidden" name="id" value="<?php print $this->id ?>" />
            <?php endif; ?>
        </form>
    </div>
    <div class="gauge"> <span class="progress"></span> </div>
</div>
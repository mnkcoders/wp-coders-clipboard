<?php defined('ABSPATH') or die; ?>

<div class="fullwitdh container solid">
    <div class="fullwitdh drag-drop container centered upload">
        <form name="upload" action="<?php print $this->get_form() ?>" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('clipboard_upload'); ?>                
            <label for="clipboard-files" class="button-primary wide">
                <span class="dashicons dashicons-upload"></span>
                <span><?php print __('Drag or select your files here') ?></span>
                <input id="clipboard-files" type="file" name="upload[]" multiple="multiple" />        
            </label>
            <!--button class="button button-primary wide right" type="submit" name="task" value="upload">
                <span class="dashicons dashicons-upload"></span>                        
                <?php print __('Upload!', 'coders_clipboard'); ?>                        
            </button-->
            <?php if ($this->is_valid()) : ?>
                <input type="hidden" name="id" value="<?php print $this->id ?>" />
            <?php endif; ?>
        </form>
    </div>
</div>
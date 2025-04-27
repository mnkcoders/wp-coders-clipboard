<?php defined('ABSPATH') or die; ?>
<div>
    <div class="fullwitdh drag-drop container centered solid">
        <form name="upload" action="<?php print $this->get_form() ?>" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('clipboard_upload'); ?>                
            <label for="clipboard-files" class="button wide">
                <span><?php print __('Drag or select your files here') ?></span>
                <input id="clipboard-files" type="file" name="upload[]" multiple="multiple" />        
            </label>
            <button class="button button-primary wide right" type="submit" name="task" value="upload">
                <span class="dashicons dashicons-upload"></span>                        
                <?php print __('Upload!', 'coders_clipboard'); ?>                        
            </button>
            <?php if ($this->is_valid()) : ?>
                <input type="hidden" name="id" value="<?php print $this->id ?>">
            <?php endif; ?>
        </form>
    </div>
    <ul class="fullwidth container tools inline">
        <li>
            <a class="button" target="_self" href="<?php
                print $this->action_recover($this->get_id()) ?>">
                <span class="dashicons dashicons-search"></span>
                <?php print __('Bring here all lost resources','coders_clipboard') ?>
            </a>
        </li> 
        <li>
            <a class="button" href="<?php
                print $this->action_renameall($this->get_id()) ?>">
                <?php print __('Rename all items below','coders_clipboard') ?>
            </a>
        </li> 
        <li>
            <a class="button" href="<?php
                print $this->action_propagate($this->get_id()) ?>">
                <?php print __('Set permissions and layout to items below','coders_clipboard') ?>
            </a>
        </li> 
        <li>
            <a class="button" href="<?php
                print $this->action_arrange() ?>">
                <?php print __('Arrange item list','coders_clipboard') ?>
            </a>
        </li> 
    </ul>
</div>
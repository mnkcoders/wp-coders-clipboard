<?php defined('ABSPATH') or die; ?>
<div class="wrap coders-clipboard main">
        <?php if( $this->has_content()) : ?>
        <?php $this->show_header() ?>
        <?php else : ?>
        <h2 class="wp-heading-inline">
            <span class="dashicons dashicons-art"></span>
            <?php print get_admin_page_title() ?>
        </h2>
        <?php endif; ?>
    
    <?php $this->show_messages() ?>

    <?php if ($this->is_valid()) : ?>
            <?php $this->show_content() ?>
    <?php endif; ?>
    
    <?php $this->show_uploader() ?>
    <?php $this->show_tasks() ?>    
    <?php $this->show_items() ?>
</div>


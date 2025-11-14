<?php defined('ABSPATH') or die; ?>

<div class="wrap coders-clipboard main">
    <?php $this->show_header() ?>
    <?php $this->show_messages() ?>

    <?php if ($this->is_valid()) : ?>
        <!-- CONTENT BLOCK -->
        <div class="content container">
            <?php $this->show_content() ?>
        </div>
    <?php else: ?>
        <div class="container main">
        </div>
    <?php endif; ?>
    <!-- UPLOADER -->
    <?php $this->show_uploader() ?>
    <?php $this->show_tasks() ?>    
    <?php $this->show_items() ?>
</div>


<?php defined('ABSPATH') or die; ?>

<div class="wrap coders-clipboard main">
    <?php $this->part_header() ?>
    <?php $this->part_messages() ?>

    <?php if ($this->is_valid()) : ?>
        <!-- CONTENT BLOCK -->
        <div class="content container">
            <?php $this->part_content() ?>
        </div>
    <?php else: ?>
        <div class="container main">
        </div>
    <?php endif; ?>
    <!-- UPLOADER -->
    <?php $this->part_uploader() ?>
    <?php $this->part_tasks() ?>    
    <?php $this->part_items() ?>
</div>


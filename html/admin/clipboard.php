<?php defined('ABSPATH') or die; ?>
<div class="wrap coders-clipboard main">
    <?php if ($this->has_content()) : ?>
        <?php $this->show_path() ?>
    <?php else : ?>
        <?php $this->show_title() ?>
    <?php endif; ?>    
    
    <?php $this->show_log() ?>

    <div class="content container">
        <?php if ($this->is_valid()) : ?>
            <?php $this->show_clipform() ?>
        <?php else: ?>
            <?php $this->show_sidebar() ?>
        <?php endif; ?>
    </div>

    <?php $this->show_uploader() ?>
    <?php $this->show_tasks() ?>    
    <?php $this->show_collection() ?>
</div>


<?php defined('ABSPATH') or die; ?>
<div class="wrap coders-clipboard main">
    <?php $this->show_title() ?>
    <?php $this->show_path() ?>
    <?php $this->show_log() ?>
    <div class="content container <?php print $this->get_main() ?>">
        <?php if ($this->has_content()) : ?>
            <?php $this->show_attachment() ?>
            <?php $this->show_toolbox() ?>
            <?php $this->show_content() ?>
        <?php else: ?>
            <?php $this->show_sidebar() ?>
        <?php endif; ?>
    </div>
    <div class="clipboard container">
        <?php $this->show_uploader() ?>
        <?php $this->show_toolbar() ?>    
        <?php $this->show_collection() ?>
    </div>
</div>


<?php defined('ABSPATH') or die; ?>
<div class="wrap coders-clipboard container <?php print $this->layout ?>">
    <?php $this->show_header() ?>
    <?php $this->show_content() ?>
    <div class="container <?php print $this->get_css() ?>">
    <?php $this->show_media() ?>        
    </div>
    <?php $this->show_items() ?>
</div>

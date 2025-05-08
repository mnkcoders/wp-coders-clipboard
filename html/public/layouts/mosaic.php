<?php defined('ABSPATH') or die; ?>
<div class="wrap coders-clipboard container <?php print $this->layout ?>">
    <?php $this->part_header() ?>
    <?php $this->part_content() ?>
    <div class="container <?php print $this->get_css() ?>">
    <?php $this->part_media() ?>        
    </div>
    <?php $this->part_items() ?>
</div>

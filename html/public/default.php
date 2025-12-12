<?php defined('ABSPATH') or die; ?>
<?php do_action('coder_menu') ?>
<div class="wrap coders-clipboard container <?php print $this->layout ?>">
    <?php $this->show_header() ?>
    <?php $this->show_media() ?>        
    <?php $this->show_content() ?>
    <?php $this->show_items() ?>
</div>


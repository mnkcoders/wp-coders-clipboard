<?php defined('ABSPATH') or die; ?>

<h1 class="wp-heading-inline"><?php print get_admin_page_title() ?></h1>

<?php $this->part_messages() ?>

<div class="wrap coders-clipboard settings">
    
    <div class="container dev-only">
        <div class="container solid">
            <a clasS="button right" href="<?php print $this->action_nuke() ?>"><?php print __('Reset Content Data','coders_clipboard') ?></a>
        </div>        
    </div>
</div>




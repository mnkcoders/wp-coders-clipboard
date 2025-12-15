<?php defined('ABSPATH') or die; ?>
<div class="wrap coders-clipboard settings">
    <?php $this->show_messages() ?>
    <div class="container dev-only">
        <div class="container solid">
            <a clasS="button right" href="<?php
            print $this->action_nuke() ?>"><?php
            print $this->text_nuke ?></a>
        </div>        
    </div>
</div>




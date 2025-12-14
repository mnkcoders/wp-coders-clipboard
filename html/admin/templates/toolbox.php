<?php defined('ABSPATH') or die; ?>
<ul class="widefat tools inline">
        <li class="item">
            <a class="button" href="<?php
                print $this->action_renameall($this->get_id()) ?>" title="<?php
                print __('Copy current name and title to all items','coder_clipboard') ?>">
                <span class="dashicons dashicons-edit"></span>
            </a>
        </li> 
        <li class="item">
            <a class="button" href="<?php print $this->action_propagate($this->get_id()) ?>" title="<?php
                print __('Copy current tier access to all items','coder_clipboard') ?>">
                <span class="dashicons dashicons-admin-network"></span>
            </a>
        </li> 
        <li class="item">
            <a class="button" href="<?php print $this->action_layout($this->get_id()) ?>" title="<?php
                print __('Copy current layout to all items','coder_clipboard') ?>">
                <span class="dashicons dashicons-admin-page"></span>
            </a>
        </li> 
        <li class="item right">
            <a target="_blank" class="button" href="<?php print $this->clipboard ?>">
                <span class="dashicons dashicons-images-alt2"></span>
                <?php print __('Preview', 'coder_clipboard') ?>
            </a>            
        </li>
</ul>
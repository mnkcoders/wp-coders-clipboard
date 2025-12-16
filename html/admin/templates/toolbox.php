<?php defined('ABSPATH') or die; ?>
<ul class="widefat tools inline">
        <li class="item">
            <a class="button" href="<?php
                print $this->action_rename() ?>" title="<?php
                print $this->copy_name ?>">
                <span class="dashicons dashicons-edit"></span>
            </a>
        </li> 
        <li class="item">
            <a class="button" href="<?php print $this->action_propagate($this->get_id()) ?>" title="<?php
                print $this->copy_role ?>">
                <span class="dashicons dashicons-admin-network"></span>
            </a>
        </li> 
        <li class="item">
            <a class="button" href="<?php print $this->action_layout($this->get_id()) ?>" title="<?php
                print $this->copy_layout ?>">
                <span class="dashicons dashicons-admin-page"></span>
            </a>
        </li> 
        <li class="item right">
            <a target="_blank" class="button" title="<?php
                print $this->text_previewdesc ?>" href="<?php
                print $this->clipboard ?>">
                <span class="dashicons dashicons-images-alt2"></span>
                <?php print $this->text_preview ?>
            </a>            
        </li>
</ul>
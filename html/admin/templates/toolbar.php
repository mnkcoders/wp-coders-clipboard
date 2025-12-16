<?php defined('ABSPATH') or die; ?>
<ul class="widefat container toolbar inline">
    <li>
        <span class="info">
            <span class="dashicons dashicons-format-gallery"></span>
            <strong class="counter"></strong> <strong class="total hide"></strong>
        </span>
    </li>
    <li class="right">
        <a class="button" href="<?php print $this->action_arrange() ?>" title="<?php
            print __('Sort all items','coder_clipboard') ?>">
            <span class="dashicons dashicons-editor-ol"></span>
        </a>
    </li> 
</ul>

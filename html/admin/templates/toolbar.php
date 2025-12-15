<?php defined('ABSPATH') or die; ?>
<ul class="widefat container tools inline">
    <li>
        <span class="button">
            <span class="dashicons dashicons-format-gallery"></span>
            <strong class="counter"></strong>
        </span>
    </li>
    <li>
        <a class="button" href="<?php print $this->action_arrange($this->id) ?>" title="<?php
            print __('Sort all items','coder_clipboard') ?>">
            <span class="dashicons dashicons-editor-ol"></span>
        </a>
    </li> 

    <li class="right">
        <button class="button" data-size="2">x2</button>
        <button class="button" data-size="4">x4</button>
        <button class="button" data-size="6">x6</button>
        <button class="button" data-size="8">x8</button>
    </li>
</ul>

<?php defined('ABSPATH') || die; ?>

<ul class="options">
    <li class="item">
        <button class="option button toggle-mode widefat"><?php
            print __('Toggle ajax mode','coder_clipboard');
            ?>
            <span class="dashicons dashicons-image-rotate"></span>
        </button>
    </li>
    <li class="item">
        <a class="option button widefat" target="_self" href="<?php
            print $this->action_recover($this->get_id()) ?>"><?php
            print __('Recover lost clips','coder_clipboard') ?>
            <span class="dashicons dashicons-search"></span>
        </a>
    </li>     
</ul>
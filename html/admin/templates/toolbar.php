<?php defined('ABSPATH') or die; ?>
<ul class="fullwidth container tools inline">
    <li>
        <span class="button">
            <span class="dashicons dashicons-format-gallery"></span>
            <strong class="counter"></strong>
        </span>
    </li>
    <li>
        <a class="button" href="<?php print $this->action_arrange() ?>" title="<?php
            print __('Sort all items','coder_clipboard') ?>">
            <span class="dashicons dashicons-editor-ol"></span>
        </a>
    </li> 
    <?php if( $this->has_content()) : ?>
        <li>
            <a class="button" href="<?php
                print $this->action_renameall($this->get_id()) ?>" title="<?php
                print __('Copy current name and title to all items','coder_clipboard') ?>">
                <span class="dashicons dashicons-edit"></span>
            </a>
        </li> 
        <li>
            <a class="button" href="<?php print $this->action_propagate($this->get_id()) ?>" title="<?php
                print __('Copy current tier access to all items','coder_clipboard') ?>">
                <span class="dashicons dashicons-admin-network"></span>
            </a>
        </li> 
        <li>
            <a class="button" href="<?php print $this->action_layout($this->get_id()) ?>" title="<?php
                print __('Copy current layout to all items','coder_clipboard') ?>">
                <span class="dashicons dashicons-admin-page"></span>
            </a>
        </li> 
    <?php else : ?>
    <li>
        <a class="button" target="_self" href="<?php print $this->action_recover($this->get_id()) ?>" title="<?php
            print __('Recover lost clips','coder_clipboard') ?>">
            <span class="dashicons dashicons-search"></span>
        </a>
    </li> 
    <?php endif; ?>
    <li class="right">
        <button class="button" data-size="2">x2</button>
        <button class="button" data-size="4">x4</button>
        <button class="button" data-size="6">x6</button>
        <button class="button" data-size="8">x8</button>
    </li>
</ul>

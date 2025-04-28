<?php defined('ABSPATH') or die; ?>
<ul class="fullwidth container tools inline">
    <li>
        <a class="button" target="_self" href="<?php print $this->action_recover($this->get_id()) ?>">
            <span class="dashicons dashicons-search"></span>
            <?php print __('Bring here all lost resources', 'coders_clipboard') ?>
        </a>
    </li> 
    <li>
        <a class="button" href="<?php print $this->action_arrange() ?>">
        <?php print __('Arrange item list', 'coders_clipboard') ?>
        </a>
    </li> 
    <?php if( $this->has_content()) : ?>
    <li>
        <a class="button" href="<?php print $this->action_renameall($this->get_id()) ?>">
           <?php print __('Rename all items below', 'coders_clipboard') ?>
        </a>
    </li> 
    <li>
        <a class="button" href="<?php print $this->action_propagate($this->get_id()) ?>">
        <?php print __('Set permissions and layout to items below', 'coders_clipboard') ?>
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

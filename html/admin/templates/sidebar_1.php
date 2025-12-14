<?php defined('ABSPATH') || die; ?>
<ul class="options drives">
    <?php foreach ($this->list_drives() as $drive => $current) : ?>
        <li class="item">
            <?php if ($current) : ?>
                <span class="selected widefat option"><?php print $drive ?></span>
            <?php else: ?>
                <a class="widefat option button" target="_self" href="<?php
                    print $this->action_drive( $drive ) ?>" ><?php
                    print $drive; ?></a>
               <?php endif; ?>
        </li>
        <?php endforeach; ?>
        <li class="item">
            <form name="drive" action="<?php print $this->get_form() ?>" method="post">
                <p><input class="widefat" type="text" name="drive" placeholder="<?php
                print __('New drive','coder_clipboard') ?>"></p>
                <?php wp_nonce_field('coder_nonce') ?>
                <input type="hidden" name="action" value="coder_clipboard" />
                <button type="submit" name="task" value="drive" class="button-primary widefat"><?php
                    print __('Create','coder_clipboard') ?></button>
            </form>
        </li>
</ul>

<ul class="options">
    <li class="item">
        <button class="option button toggle-mode widefat"><?php
            print __('Toggle ajax mode','coder_clipboard');
            ?></button>
    </li>
</ul>
<?php defined('ABSPATH') or die; ?>
<?php if( $this->is_media() ):  ?>
<img class="media" src="<?php
    print $this->url ?>" alt="<?php
    print $this->name ?>" title="<?php
    print $this->title ?>">
<?php else: ?>
    <a href="<?php
        print $this->get_clipboard() ?>" class="container">
        <span class="dashicons dashicons-admin-links"></span>
        <?php print $this->title ?>
    </a>
<?php endif; ?>


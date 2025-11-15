<?php defined('ABSPATH') or die; ?>

<a class="content collapsed <?php print $this->css ?>" href="<?php print $this->clipboard ?>" target="_blank">
    <?php if( $this->is_media() ) : ?>
    <img src="<?php print $this->url ?>" alt="<?php print $this->name ?>" title="<?php print $this->title ?>">
    <?php else:  ?>
    <span class="block solid">
        <span class="dashicons dashicons-media-document"></span>
        <?php print $this->title ?>
    </span>
    <?php endif; ?>
</a>
<?php defined('ABSPATH') or die; ?>

<a class="content collapsed <?php print $this->get_css() ?>" href="<?php print $this->get_clipboard() ?>" target="_blank">
    <?php if( $this->is_media() ) : ?>
    <img src="<?php print $this->get_url() ?>" alt="<?php print $this->name ?>" title="<?php print $this->title ?>">
    <?php else:  ?>
    <span class="block solid">
        <span class="dashicons dashicons-media-document"></span>
        <?php print $this->title ?>
    </span>
    <?php endif; ?>
</a>
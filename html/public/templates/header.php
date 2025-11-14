<?php defined('ABSPATH') or die; ?>
<?php if( $this->has_content() ) : ?>
    <ul class="container path">
        <?php foreach ($this->list_path() as $id => $title) : ?>
            <li class="location">
            <?php if ( trim($id) !== $this->id ) : ?>
                    <a class="link" href="<?php
                            print $this->get_clipboard($id); ?>" target="_self"><?php
                            print $title; ?></a>
            <?php else : ?>
                <span class="current"><?php print $title ?></span>
            <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif ;?>

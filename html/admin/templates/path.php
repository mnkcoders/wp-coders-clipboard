<?php defined('ABSPATH') or die; ?>
<?php if( $this->has_content() ) : ?>
<ul class="coders-clipboard-title path container">
    <li class="path">
        <a href="<?php print $this->base ?>" target="_self">
            <span class="dashicons dashicons-images-alt"></span>
        </a>
    </li>
        <?php foreach ($this->list_path() as $id => $title) : ?>
            <li class="path">
                <?php if (strlen($id)) : ?>
                    <?php if ( trim($id) !== $this->id ) : ?>
                        <a class="content" href="<?php
                            print $this->get_post($id) ?>" target="_self"><?php
                            print $title ?></a>
                    <?php else: ?>
                        <span data-id="<?php print $this->id ?>">
                            <?php print $title ?>
                        </span>
                        <span class="copy-link" data-link="<?php print $this->clipboard ?>">
                            <span class="dashicons dashicons-admin-links"></span>
                        </span>                        
                    <?php endif; ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
</ul>
<?php endif ;?>




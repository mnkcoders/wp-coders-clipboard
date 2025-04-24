<?php defined('ABSPATH') or die ?>
<nav><?php foreach( $this->list_path() as $id => $title) :  ?>
                <?php if(strlen($id)) : ?>
                <a href="<?php print $this->get_post($id) ?>" target="_self"><?php print $title ?></a>
                <?php else : ?>
                <span><?php print $title ?></span>
                <?php endif; ?>
                <?php endforeach; ?>
            </nav>
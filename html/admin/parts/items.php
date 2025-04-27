<?php defined('ABSPATH') or die; ?>
<!-- COLLECTION BLOCK -->
<ul class="collections container">
    <?php if ($this->count_items()) : ?>
        <?php foreach ($this->list_collection() as $item) : ?>
            <li class="item">
                <a href="<?php print $this->action_sort($item->id,$item->get_after()) ?>" class="dashicons dashicons-arrow-right"></a>
                <a href="<?php print $this->action_sort($item->id,$item->get_before()) ?>" class="dashicons dashicons-arrow-left"></a>
                <!--<?php print $this->action_sort($item->id,$item->get_after()) ?>-->
                <a class="content" href="<?php print $this->get_post($item->id) ?>">
                    <?php if ($item->is_image()) : ?>
                        <img class="media" src="<?php
                            print $item->get_url() ?>" alt="<?php
                            print $item->name ?>" title="<?php
                            print $item->title ?>" />
                        <span class="cover">
                            <?php print $item->title ?>
                        </span>
                    <?php else : ?>
                        <?php print $item->name?>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="container centered ">
            <h3>
                <span class="dashicons dashicons-info"></span>
                <?php print __('Just drag-drop to make your collection! :D', 'coders_clipboard'); ?>
            </h3>
        </div>
    <?php endif; ?>
</ul>

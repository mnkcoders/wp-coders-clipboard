<?php defined('ABSPATH') or die; ?>
<!-- ITEMS BLOCK -->
<ul class="collections container">
    <?php if ($this->count_items()) : ?>
        <?php foreach ($this->list_collection() as $item) : ?>
            <li class="item">
                <a target="_self" class="content <?php
                    print $item->get_css() ?>" href="<?php
                    print $item->get_clipboard() ?>" >
                    <?php if ($item->is_image()) : ?>
                    <!--<?php print $item->get_clipboard()  ?>-->
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
    <?php endif; ?>
</ul>


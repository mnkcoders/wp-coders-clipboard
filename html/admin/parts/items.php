<?php defined('ABSPATH') or die; ?>
<!-- COLLECTION BLOCK -->
<ul class="clipboard-box" class="inline queue"></ul>
<ul class="collections container drag-drop">
    <?php if ($this->count_items()) : ?>
        <?php foreach ($this->list_collection() as $item) : ?>
            <li class="item" data-item="<?php print $item->id ?>">
                <span class="placeholder" data-slot="<?php print $item->slot ?>"></span>
                <div class="content">
                    <?php if ($item->is_media()) : ?>
                        <img class="media <?php print $item->tags ?>" src="<?php
                            print $item->get_url() ?>" alt="<?php
                            print $item->name ?>" title="<?php
                            print $item->title ?>" />
                    <?php endif; ?>
                    <a class="cover" data-id="<?php
                        print $item->id ?>" href="<?php
                        print $this->get_post($item->id) ?>"><?php
                        print $item->title ?></a>                        
                </div>
                <!-- commands -->
                    <?php if($this->is_valid()):  ?>
                    <a class="task top-center dashicons dashicons-arrow-up-alt" href="<?php
                        print $this->action_move($item->id,$this->parent_id) ?>"></a>
                    <?php endif; ?>
                    <a class="task top-right dashicons dashicons-arrow-right-alt2" href="<?php
                        print $this->action_sort($item->id,$item->get_after()) ?>"></a>
                    <a class="task top-left dashicons dashicons-arrow-left-alt2" href="<?php
                        print $this->action_sort($item->id,$item->get_before()) ?>"></a>
                    <label class="task bottom-right select" for="<?php
                            print sprintf('select_%s',$item->id)?>">
                        <input type="checkbox" id="<?php
                            print sprintf('select_%s',$item->id) ?>" value="<?php print $item->id ?>"
                    </label>
                    <?php if($item->has_items()) : ?>
                    <span class="task bottom-left dashicons dashicons-images-alt" ><?php
                        print $item->count_items();
                    ?></span>
                    <?php endif; ?>
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <li class="container centered empty">
                    <h3>
                        <span class="dashicons dashicons-info"></span>
                        <?php print __('Just drag-drop to make your collection! :D', 'coders_clipboard'); ?>
                    </h3>
        </li>            
    <?php endif; ?>
</ul>


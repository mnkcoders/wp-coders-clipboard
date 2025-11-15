<?php defined('ABSPATH') or die; ?>
<!-- COLLECTION BLOCK -->
<ul class="clipboard-box" class="inline queue"></ul>
<ul class="collections container drag-drop">
    <?php if (!$this->is_empty()) : ?>
        <?php foreach ($this->list_items() as $item) : ?>
            <li class="item" data-id="<?php
                    print $item->id ?>" data-slot="<?php 
                    print $item->slot ?>">
                <span class="placeholder" data-slot="<?php print $item->slot ?>"></span>
                <div class="content">
                    <?php if ($item->is_media()) : ?>
                        <img class="media <?php print $item->tags ?>" src="<?php
                            print $item->url ?>" alt="<?php
                            print $item->name ?>" title="<?php
                            print $item->title ?>" />
                    <?php else : ?>
                        <span class="dashicons attachment dashicons-media-document"></span>
                    <?php endif; ?>
                    <a class="caption" data-id="<?php
                        print $item->id ?>" href="<?php
                        print $this->get_post($item->id) ?>"><?php
                        print $item->title ?></a>                        
                </div>
                <!-- commands -->
                    <?php if($this->has_content()):  ?>
                    <a class="task top-right dashicons dashicons-arrow-up-alt" href="<?php
                        print $this->action_moveup($item->id) ?>"></a>
                    <?php endif; ?>
                    <label class="task top-left select" for="<?php
                            print sprintf('select_%s',$item->id)?>">
                        <input type="checkbox" id="<?php
                            print sprintf('select_%s',$item->id) ?>" value="<?php
                            print $item->id ?>" />
                    </label>
                    <?php if($item->has_items()) : ?>
                    <span class="task counter visible bottom-left" >
                        <?php print $item->count_items(); ?>
                    </span>
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


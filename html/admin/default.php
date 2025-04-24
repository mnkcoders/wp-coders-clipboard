<?php defined('ABSPATH') or die; ?>

<h1 class="wp-heading-inline"><?php print get_admin_page_title() ?></h1>

<div class="wrap coders-clipboard main">
    <!-- UPLOADER -->
    <div class="fullwitdh container solid">
        <div class="fullwitdh drag-drop container ">
            <form name="upload" action="<?php print $this->get_form() ?>" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('clipboard_upload'); ?>                
                <label for="clipboard-files">
                    <input id="clipboard-files" type="file" name="upload[]" multiple="multiple" />
                </label>
                <button class="right button button-primary" type="submit" name="action" value="cod_clipboard_upload">
                    <span class="dashicons dashicons-upload"></span>                        
                    <?php print __('Drag or select your files here to upload!', 'coders_clipboard'); ?>                        
                </button>
                <?php if ($this->is_valid()) : ?>
                    <input type="hidden" name="parent_id" value="<?php print $this->id ?>"
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($this->is_valid()) : ?>
        <!-- CONTENT BLOCK -->
                
        <div class="content container">
            <div class="container half content">

                <h2><?php print $this->title ?></h2>

                <ul class="path">
                    <?php foreach ($this->list_path() as $id => $title) : ?>
                        <li class="node">
                            <?php if (strlen($id)) : ?>
                                <?php if ($id !== $this->id) : ?>
                                    <a href="<?php print $this->get_post($id) ?>" target="_self"><?php print $title ?></a>
                                <?php else: ?>
                                    <span ><?php print $title ?></span>
                                <?php endif; ?>
                            <?php else : ?>
                                <a class="dashicons dashicons-art" href="<?php print $this->get_post() ?>" target="_self"></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>


                <span class="block solid"><?php print $this->name ?></span>
                <span class="block solid"><?php print $this->description ?></span>
                <span class="block solid"><?php print $this->layout ?></span>
                <span class="block solid"><?php print $this->acl ?></span>
                <span class="block solid"><?php print $this->created_at ?></span>
            </div>
            <div class="container media half">
                <img src="<?php print $this->get_url() ?>" alt="<?php print $this->name ?>" title="<?php print $this->title ?>">
            </div>
        </div>
    <?php endif; ?>


    <!-- COLLECTION BLOCK -->
    <ul class="collections container">
        <?php if ($this->count_items()) : ?>
            <?php foreach ($this->list_items() as $item) : ?>
                <li class="item">
                    <a class="content" href="<?php print $this->get_post($item['id']) ?>">
                        <?php if ($this->is_image($item['type'])) : ?>
                            <img class="media" src="<?php print $this->get_link($item['id']) ?>" alt="<?php print $item['name'] ?>" title="<?php print $item['title'] ?>" />
                        <?php else : ?>
                            <?php print $item['name'] ?>
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

</div>


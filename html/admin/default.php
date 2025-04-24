<?php defined('ABSPATH') or die; ?>

<h1 class="wp-heading-inline">
    <span><?php print get_admin_page_title() ?></span>
    <?php if (!$this->is_valid()) : ?>
        <a class="dashicons dashicons-search" href="<?php print '#' ?>"></a>
    <?php endif; ?>
</h1>

<div class="wrap coders-clipboard main">
    <?php if ($this->is_valid()) : ?>
        <!-- CONTENT BLOCK -->
        <div class="content container">
            <div class="container half content">
                <?php $this->part_path() ?>
                <?php $this->part_content() ?>
            </div>
            <div class="container media half">
                <?php $this->part_attachment() ?>
            </div>
        </div>
    <?php else: ?>
        <div class="container">
        </div>
    <?php endif; ?>
    <!-- UPLOADER -->
    <div class="fullwitdh container solid">
        <div class="fullwitdh drag-drop container ">
            <?php $this->part_uploader() ?>
        </div>
    </div>

    <?php $this->part_collections() ?>
</div>


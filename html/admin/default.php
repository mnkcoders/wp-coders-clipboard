<?php defined('ABSPATH') or die; ?>

<h1 class="wp-heading-inline">
    <span><?php print get_admin_page_title() ?></span>
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
    <?php $this->part_uploader() ?>
    <?php $this->part_collections() ?>
</div>


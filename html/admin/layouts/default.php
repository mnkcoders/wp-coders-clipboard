<?php defined('ABSPATH') or die; ?>

<?php if( $this->is_valid() ) : ?>
<?php $this->part_path() ?>
<?php else : ?>
<h1 class="wp-heading-inline">
    <span class="dashicons dashicons-art"></span>
    <?php print get_admin_page_title() ?></h1>
<?php endif; ?>

<div class="wrap coders-clipboard main">
    <?php if ($this->is_valid()) : ?>
        <!-- CONTENT BLOCK -->
        <div class="content container">
            <?php $this->part_content() ?>
        </div>
    <?php else: ?>
        <div class="container main">
        </div>
    <?php endif; ?>
    <!-- UPLOADER -->
    <?php $this->part_uploader() ?>
    <?php $this->part_tasks() ?>    
    <?php $this->part_items() ?>
</div>


<?php defined('ABSPATH') or die; ?>
<?php if ($this->is_valid()) : ?>
    <!-- CONTENT BLOCK -->
    <div class="container <?php print $this->layout ?>">
        <h1 class="container header">
            <?php print $this->title ?>
            <?php if( $this->slot > 0) :  ?>
            <span>- Page<?php print $this->slot ?></span>
            <?php endif; ?>
        </h1>
        <div class="container content <?php print $this->layout ?>">
            <p><?php print $this->description ?></p>
        </div>
    </div>
<?php endif; ?>
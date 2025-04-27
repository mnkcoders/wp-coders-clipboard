<?php defined('ABSPATH') or die; ?>
<?php if ($this->is_valid()) : ?>
    <!-- CONTENT BLOCK -->
    <div class="container <?php print $this->layout ?>">
        <h1 class="container header"><?php print $this->title ?></h1>
        <div class="container content <?php print $this->layout ?>">
            <p><?php print $this->description ?></p>
        </div>
    </div>
<?php endif; ?>
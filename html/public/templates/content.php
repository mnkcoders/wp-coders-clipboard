<?php defined('ABSPATH') or die; ?>
<?php if ($this->is_valid()) : ?>
    <div class="container">
        <h1 class="container header">
            <?php print $this->title ?>
            <?php if( $this->slot > 0) :  ?>
            <span><?php printf('- %s %s',
                    __('Page','coders_clipboard') ,
                    $this->slot ) ?></span>
            <?php endif; ?>
        </h1>
        <div class="container content <?php print $this->layout ?>">
            <p><?php print $this->description ?></p>
        </div>
    </div>
<?php endif; ?>
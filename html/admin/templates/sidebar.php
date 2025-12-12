<?php defined('ABSPATH' )|| die; ?>
<div class="container">
<select class="widefat input right" name="clipboard-drive" size="1" id="drive-select">
    <?php foreach( $this->list_drives() as $drive => $selected ): ?>
    <option <?php
        print $selected ? 'selected' : '' ?>><?php
        print $drive;
    ?></option>
    <?php endforeach; ?>
</select>
    </div>

<div class="container ">
<button class="button toggle-mode widefat"><?php
    print __('Toggle ajax mode','coder_clipboard');
    ?></button>
</div>
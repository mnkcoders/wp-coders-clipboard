<?php defined('ABSPATH' )|| die; ?>
<select class="input right" name="clipboard-drive" size="1" id="drive-select">
    <?php foreach( $this->list_drives() as $drive => $selected ): ?>
    <option <?php
        print $selected ? 'selected' : '' ?>><?php
        print $drive;
    ?></option>
    <?php endforeach; ?>
</select>
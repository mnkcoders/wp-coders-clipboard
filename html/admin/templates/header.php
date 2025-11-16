<?php defined('ABSPATH') or die; ?>
<select class="input right" name="clipboard-drive" size="1" id="drive-select">
    <?php foreach( $this->list_drives() as $drive => $selected ): ?>
    <option <?php
        print $selected ? 'selected' : '' ?>><?php
        print $drive;
    ?></option>
    <?php endforeach; ?>
</select>

<ul class="coders-clipboard-title path container">
    <li class="node">
        <span class="dashicons dashicons-art"></span>
        <?php if( $this->has_content()) : ?>
        <a href="<?php print $this->base ?>" target="_self">
            <?php print get_admin_page_title() ?>
        </a>
        <?php else : ?>
        <span><?php print get_admin_page_title() ?></span>
        <?php endif; ?>
    </li>
    <?php if( $this->has_content() ) : ?>
        <?php foreach ($this->list_path() as $id => $title) : ?>
            <li class="node">
                <?php if (strlen($id)) : ?>
                    <?php if ( trim($id) !== $this->id ) : ?>
                        <a href="<?php print $this->get_post($id) ?>" target="_self"><?php print $title ?></a>
                    <?php else: ?>
                        <span >
                            <?php print $title ?>
                        </span>
                        <span class="copy-link" data-link="<?php print $this->get_clipboard() ?>">
                            <span class="dashicons dashicons-admin-links"></span>
                        </span>                        
                    <?php endif; ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    <?php endif ;?>
</ul>




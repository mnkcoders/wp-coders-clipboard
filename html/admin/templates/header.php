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
    <li class="path">
        <a class="base" href="<?php print $this->base ?>" target="_self">
            <span class="dashicons dashicons-art"></span>
            <?php print get_admin_page_title() ?>
        </a>
    </li>
    <?php if( $this->has_content() ) : ?>
        <?php foreach ($this->list_path() as $id => $title) : ?>
            <li class="path">
                <?php if (strlen($id)) : ?>
                    <?php if ( trim($id) !== $this->id ) : ?>
                        <a class="content" href="<?php
                            print $this->get_post($id) ?>" target="_self"><?php
                            print $title ?></a>
                    <?php else: ?>
                        <span data-id="<?php print $this->id ?>">
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




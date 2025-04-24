<?php defined('ABSPATH') or die; ?>

<ul class="path">
    <li class="node">
        <a class="dashicons dashicons-art" href="<?php print $this->get_post() ?>" target="_self"></a>
    </li>
    <?php foreach ($this->list_path() as $id => $title) : ?>
        <li class="node">
            <?php if (strlen($id)) : ?>
                <?php if ($id !== $this->id) : ?>
                    <a href="<?php print $this->get_post($id) ?>" target="_self"><?php print $title ?></a>
                <?php else: ?>
                    <span ><?php print $title ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>

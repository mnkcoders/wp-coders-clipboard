<?php defined('ABSPATH') or die; ?>
<h2 class="wp-heading-inline"><?php print get_admin_page_title() ?></h2>
<?php foreach (Clipboard::messages() as $message) : ?>
    <p class="container notice type-<?php print $message['type'] ?>"><?php print $message['content'] ?></p>
<?php endforeach; ?>
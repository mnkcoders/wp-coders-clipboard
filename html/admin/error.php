<?php defined('ABSPATH') or die; ?>
<?php foreach (Clipboard::messages() as $message) : ?>
    <p class="container notice type-<?php print $message['type'] ?>"><?php print $message['content'] ?></p>
<?php endforeach; ?>
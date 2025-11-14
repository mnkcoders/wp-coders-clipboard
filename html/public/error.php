<?php defined('ABSPATH') or die; ?>
<div class="coders-clipboard container error">
<?php foreach ($this->list_messages() as $message) : ?>
    <p class="container notice type-<?php print $message['type'] ?>"><?php print $message['content'] ?></p>
<?php endforeach; ?>
</div>
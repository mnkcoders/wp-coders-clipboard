<?php defined('ABSPATH') or die; ?>
<div class="notifier container">
<?php foreach(ClipboardAdmin::messages() as $message) : ?>
    <div class="is-dismissible notice type-<?php print $message['type'] ?>">
        <?php print $message['content']?>
    </div>
<?php endforeach; ?>    
</div>

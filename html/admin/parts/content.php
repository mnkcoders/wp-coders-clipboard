<?php defined('ABSPATH') or die; ?>
<form name="content" action="<?php print $this->get_form() ?>" method="post" enctype="text/plain">
    <h2 class="header">
        <input id="id_title" class="form-input" name="title" value="<?php print $this->title ?>" placeholder="<?php print __('Set a title', 'coders_clipboard') ?>">
    </h2>

    <span class="block solid">
        <label><?php print __('Created', 'coders_clipboard') ?></label>
        <?php print $this->created_at ?>
    </span>

    <span class="block edit">
        <input id="id_name" class="form-input" name="name" value="<?php print $this->name ?>" placeholder="<?php print __('File Name', 'coders_clipboard') ?>">
    </span>

    <span class="block edit">
        <label for="id_layout"><?php
            print __('Layout', 'coders_clipboard')
            ?></label>
        <select id="id_layout" class="form-input" name="layout">
            <?php foreach ($this->list_layouts() as $layout => $label) : ?>
                <option value="<?php print $layout ?>" <?php print $layout === 'default' ? 'selected' : ''  ?>><?php print $label ?></option>
            <?php endforeach; ?>
        </select>
    </span>
    <span class="block edit">
        <label for="id_acl"><?php
            print __('Role', 'coders_clipboard')
            ?></label>
        <select id="id_acl" class="form-input" name="acl">
            <?php foreach ($this->list_roles() as $role => $label) : ?>
                <option value="<?php print $role ?>" <?php print $role === 'private' ? 'selected' : ''  ?>><?php print $label ?></option>
            <?php endforeach; ?>
        </select>
    </span>
    <span class="block edit">
        <label for="id_description"><?php
            print __('Description', 'coders_clipboard')
            ?></label>
        <textarea id="id_description" name="description" class="form-input" placeholder="<?php print __('add a description', 'coders_clipboard') ?>">
            <?php print $this->description ?>
        </textarea>
    </span>
    <span class="block">
        <a class="button" target="_self" href="<?php print $this->action_delete() ?>"><?php print __('delete', 'coders_clipboard') ?></a>
        <button class="button-primary right" type="submit" name="action" value="clipboard_update"><?php
            print __('Update', 'coders_clipboard');
            ?></button>
    </span>
</form>
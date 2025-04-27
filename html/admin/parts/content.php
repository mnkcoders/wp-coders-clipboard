<?php defined('ABSPATH') or die; ?>
<form name="content" action="<?php print $this->get_form() ?>" method="post">
    <input type="hidden" name="id" value="<?php print $this->id ?>" />
        
    <input id="id_title" class="block form-input header" name="title" value="<?php print $this->title ?>" placeholder="<?php print __('Set a title', 'coders_clipboard') ?>">

    <span class="block solid">
        <label><?php print __('Created', 'coders_clipboard') ?></label>
        <span class="right"><?php print $this->created_at ?></span>
    </span>

    <span class="block solid edit">
        <input id="id_name" class="form-input" name="name" value="<?php
            print $this->name ?>" placeholder="<?php
            print __('File Name', 'coders_clipboard') ?>">
    </span>

    <span class="block solid edit">
        <select id="id_layout" class="form-input" name="layout">
            <?php foreach ($this->list_layouts() as $layout => $label) : ?>
                <option value="<?php print $layout ?>" <?php print $this->get_currentLayout( $layout )  ?>><?php print $label ?></option>
            <?php endforeach; ?>
        </select>
    </span>
    <span class="block solid edit">
        <select id="id_acl" class="form-input" name="acl">
            <?php foreach ($this->list_roles() as $role => $label) : ?>
                <option value="<?php print $role ?>" <?php print $this->get_role( $role )  ?>><?php print $label ?></option>
            <?php endforeach; ?>
        </select>
    </span>
    <textarea id="id_description" name="description" class="block form-input" placeholder="<?php
            print __('add a description', 'coders_clipboard') ?>"><?php
            print $this->description ?></textarea>
    <span class="block">
        <a class="button" target="_self" href="<?php print $this->action_delete() ?>"><?php print __('delete', 'coders_clipboard') ?></a>
        <button class="button-primary right" type="submit" name="task" value="update"><?php
            print __('Update', 'coders_clipboard');
            ?></button>
    </span>
</form>
<?php defined('ABSPATH') or die; ?>
<form name="content" action="<?php print $this->get_form() ?>" method="post">
    <input type="hidden" name="id" value="<?php print $this->id ?>" />

    <div class="container">
        <input id="id_title" class="block form-input header" name="title" value="<?php print $this->title ?>" placeholder="<?php print __('Set a title', 'coders_clipboard') ?>">
    </div>

    <div class="tab collapsed">
        <span class="toggle"><span class="dashicons dashicons-arrow-down-alt2"></span></span>
            <div class="container half content">
        <span class="block solid">
            <label><?php print __('Created', 'coders_clipboard') ?></label>
            <span class="right"><?php print $this->created_at ?></span>
        </span>

        <span class="block solid edit">
            <input id="id_name" class="form-input" name="name" value="<?php print $this->name ?>" placeholder="<?php print __('File Name', 'coders_clipboard') ?>">
        </span>

        <span class="block solid edit">
            <select id="id_layout" class="form-input" name="layout">
            <?php foreach ($this->list_layouts() as $layout => $label) : ?>
                    <option value="<?php print $layout ?>" <?php print $this->get_currentLayout($layout) ?>><?php print $label ?></option>
                <?php endforeach; ?>
            </select>
        </span>
        <span class="block solid edit">
            <select id="id_acl" class="form-input" name="acl">
                <?php foreach ($this->list_roles() as $role => $label) : ?>
                    <option value="<?php print $role ?>" <?php print $this->get_role($role) ?>><?php print $label ?></option>
                <?php endforeach; ?>
            </select>
        </span>

    </div>
    <div class="container media half compact clip">
        <?php $this->part_attachment() ?>
    </div>
    <div class="container">
        <?php $this->editor_description() ?>
    </div>
    <div class="container bottom">
        <a class="button" target="_self" href="<?php print $this->action_delete($this->id) ?>"><?php print __('delete', 'coders_clipboard') ?></a>
        <button class="button-primary right" type="submit" name="task" value="update"><?php
        print __('Update', 'coders_clipboard');
        ?></button>
    </div>
    </div>


</form>
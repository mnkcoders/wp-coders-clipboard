<?php defined('ABSPATH') or die; ?>
<form name="content" action="<?php print $this->get_form() ?>" method="post">
    <div class="tab collapsed">
        <a target="_blank" class="button" href="<?php
            print $this->get_clipboard() ?>">
            <span class="dashicons dashicons-images-alt2"></span>
            <?php print __('View','coders_clipboard') ?>
        </a>
        <span class=" button-primary right toggle">
            <span class="dashicons dashicons-edit"></span>
            <?php print __('Edit','coders_clipboard') ?>
        </span>
        
        <div class="container">
            <!-- content top -->
            <input id="id_title" class="block form-input header" name="title" value="<?php print $this->title ?>" placeholder="<?php print __('Set a title', 'coders_clipboard') ?>">
            <input type="hidden" name="id" value="<?php print $this->id ?>" />
            <input type="hidden" name="context_id" value="<?php print $this->id ?>" />
        </div>
        
        <div class="container half content">
            <!-- content left -->
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
        <div class="container half compact clip">
            <!-- content right -->
            <?php $this->part_attachment() ?>
        </div>
        <div class="container">
            <!-- content bottom -->
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
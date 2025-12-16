<?php defined('ABSPATH') or die; ?>

<form name="content" action="<?php print $this->get_form() ?>" method="post">
            <input type="hidden" name="action" value="coder_clipboard" />
            <input type="hidden" name="id" value="<?php print $this->id ?>" />
            
            <!-- content top -->
            <input id="id_title" name="title" value="<?php
                print $this->title ?>" class="block form-input title header">
            
            <p class="separator">
                <button class="button-primary right" type="submit" name="task" value="update">
                    <span class="dashicons dashicons-saved"></span>
                    <?php print $this->text_update; ?>
                </button>
            </p>            
        
        
            <!-- content left -->
            <span class="block solid">
                <label><?php print $this->text_created ?></label>
                <span class="right"><?php print $this->created_at ?></span>
            </span>

            <span class="block edit">
                <input id="id_name" class="form-input name" name="name" value="<?php print $this->name ?>" placeholder="<?php print __('File Name', 'coder_clipboard') ?>">
            </span>
            <span class="block edit">
                <select id="id_layout" class="form-input" name="layout">
                <?php foreach ($this->list_layouts() as $layout => $label) : ?>
                        <option value="<?php
                            print $layout ?>" <?php
                            print $this->check_layout($layout) ? 'selected' : '' ?>><?php
                            print $label ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
            <span class="block edit">
                <select id="id_acl" class="form-input" name="acl">
                    <?php foreach ($this->list_roles() as $role => $label) : ?>
                        <option value="<?php
                            print $role ?>" <?php
                            print $this->check_role($role) ? 'selected' : '' ?>><?php
                            print $label ?></option>
                    <?php endforeach; ?>
                </select>
            </span>


            <!-- content bottom -->
            <?php $this->editor_description() ?>
            <p class="separator">
                <i>
                    <span class="dashicons dashicons-info"></span>
                    <?php print $this->text_removeitem ?>
                </i>
                <a class="button right" target="_self" href="<?php
                    print $this->action_remove($this->id) ?>">
                    <span class="dashicons dashicons-trash"></span>
                </a>
            </p>
</form>

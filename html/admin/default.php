<?php defined('ABSPATH') or die; ?>

<h1 class="wp-heading-inline"><?php print get_admin_page_title() ?></h1>

<div class="wrap coders-clipboard main">
    <?php if ($this->is_valid()) : ?>
        <!-- CONTENT BLOCK -->
        <div class="content container">
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


            <div class="container half content">
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
                        <a class="button" target="_self" href="<?php
                            print $this->action_delete() ?>"><?php
                            print __('delete','coders_clipboard') ?></a>
                        <button class="button-primary right" type="submit" name="action" value="update"><?php
                            print __('Update', 'coders_clipboard');
                            ?></button>
                    </span
                </form>
            </div>
            <div class="container media half">
                <a class="attachment" href="<?php print $this->get_link() ?>" target="_blank">
                    <img src="<?php print $this->get_url() ?>" alt="<?php print $this->name ?>" title="<?php print $this->title ?>">
                </a>
            </div>
        </div>
    <?php else: ?>
        <p><a class="button primary right" href="<?php print '#' ?>"><?php print __('Find lost files', 'coders_clipboard') ?></a></p>
    <?php endif; ?>
    <!-- UPLOADER -->
    <div class="fullwitdh container solid">
        <div class="fullwitdh drag-drop container ">
            <form name="upload" action="<?php print $this->get_form() ?>" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('clipboard_upload'); ?>                
                <label for="clipboard-files">
                    <input id="clipboard-files" type="file" name="upload[]" multiple="multiple" />
                </label>
                <button class="right button button-primary" type="submit" name="action" value="cod_clipboard_upload">
                    <span class="dashicons dashicons-upload"></span>                        
                    <?php print __('Drag or select your files here to upload!', 'coders_clipboard'); ?>                        
                </button>
                <?php if ($this->is_valid()) : ?>
                    <input type="hidden" name="parent_id" value="<?php print $this->id ?>">
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- COLLECTION BLOCK -->
    <ul class="collections container">
        <?php if ($this->count_items()) : ?>
            <?php foreach ($this->list_items() as $item) : ?>
                <li class="item">
                    <a class="content" href="<?php print $this->get_post($item['id']) ?>">
                        <?php if ($this->is_image($item['type'])) : ?>
                            <img class="media" src="<?php print $this->get_link($item['id']) ?>" alt="<?php print $item['name'] ?>" title="<?php print $item['title'] ?>" />
                        <?php else : ?>
                            <?php print $item['name'] ?>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="container centered ">
                <h3>
                    <span class="dashicons dashicons-info"></span>
                    <?php print __('Just drag-drop to make your collection! :D', 'coders_clipboard'); ?>
                </h3>
            </div>
        <?php endif; ?>
    </ul>
</div>


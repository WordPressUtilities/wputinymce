WPU TinyMCE Buttons
=================

Add new buttons to TinyMCE

How to install :
---

* Put this folder to your wp-content/plugins/ folder.
* Activate the plugin in "Plugins" admin section.

How to add buttons :
---

```php
add_filter('wputinymce_buttons', 'wputh_set_wputinymce_buttons');
function wputh_set_wputinymce_buttons($buttons) {
    $buttons['insert_table'] = array(
        'post_type' => array('post'),
        'title' => 'Insert a table',
        'image' => get_stylesheet_directory_uri() . '/images/tinymce/table.png',
        'html' => '<table class="wputinymce-table"><thead><tr><th>Entête</th><th>Entête</th></tr></thead><tbody><tr><td>Content</td><td>Content</td></tr></tbody></table>'
    );
    $buttons['wrap_test'] = array(
        'title' => 'Wrap test',
        'image' => get_stylesheet_directory_uri() . '/images/tinymce/table.png',
        'before_select' => '<span class="wrap-test">',
        'after_select' => '</span>',
    );
    return $buttons;
}
```

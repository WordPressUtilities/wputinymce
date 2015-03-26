<?php

/*
Plugin Name: WPU TinyMCE Buttons
Plugin URI: http://github.com/Darklg/WPUtilities
Description: Add new buttons to TinyMCE
Version: 0.8.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUTinyMCE
{
    public $plugin_version = '0.8.1';

    function __construct() {
        if (!is_admin()) {
            return;
        }

        $upload_dir = wp_upload_dir();
        $this->up_dir = $upload_dir['basedir'] . '/wpu_tinymce-cache';
        $this->up_url = $upload_dir['baseurl'] . '/wpu_tinymce-cache';
        $this->plugin_assets_dir = dirname(__FILE__) . '/assets/';

        add_action('init', array(&$this,
            'check_buttons_list'
        ));
        add_action('init', array(&$this,
            'set_options'
        ));
        add_action('init', array(&$this,
            'set_buttons'
        ));

        add_action('admin_print_footer_scripts', array(&$this,
            'set_quicktags'
        ));
        add_action('admin_enqueue_scripts', array(&$this,
            'load_assets'
        ));
    }

    function check_buttons_list() {

        // Import buttons
        $buttons = apply_filters('wputinymce_buttons', array());
        $this->buttons = array();
        $this->quicktags = array();

        // Check values
        foreach ($buttons as $button_id => $button) {

            $button['id'] = $button_id;

            // Default image
            if (!isset($button['image'])) {
                $button['image'] = $this->up_url . '/icon-list.png';
            }

            if (!isset($button['quicktag']) || !$button['quicktag']) {
                $button['quicktag'] = true;
            }

            // Default title
            if (!isset($button['title']) || empty($button['title'])) {
                $button['title'] = ucwords(str_replace('_', ' ', $button_id));
            }

            if (!isset($button['post_type'])) {
                $button['post_type'] = array(
                    'any'
                );
            }
            if (!is_array($button['post_type'])) {
                $button['post_type'] = array(
                    $button['post_type']
                );
            }

            if (isset($button['html'])) {
                if ($button['quicktag']) {
                    $this->quicktags[$button_id] = $button;
                }
                $this->buttons[$button_id] = $button;
            }
        }

        // Check version
        $buttons_version = md5($this->plugin_version . serialize($this->buttons));
        $buttons_version_option = get_option('wputinymce_buttons_list');

        // Same version : quit
        if ($buttons_version == $buttons_version_option) {
            return;
        }

        // Else : regenerate JS
        $this->regenerate_js_file();

        // Save version
        update_option('wputinymce_buttons_list', $buttons_version);
    }

    function regenerate_js_file() {

        // Check cache directory
        if (!is_dir($this->up_dir)) {
            @mkdir($this->up_dir, 0777);
            @chmod($this->up_dir, 0777);
        }

        // Regenerate JS
        $js = "(function(){\n";
        $js.= "var wpu_tinymce_items = [];\n";

        foreach ($this->buttons as $button_id => $button) {
            $js.= "wpu_tinymce_items.push(" . json_encode($button) . ");\n";
        }

        $js.= file_get_contents($this->plugin_assets_dir . "tinymce-create.js") . "\n";
        $js.= "}());";

        file_put_contents($this->up_dir . '/cache.js', $js);

        // Copy default icon
        if (!file_exists($this->up_dir . '/icon-list.png')) {
            copy($this->plugin_assets_dir . "icon-list.png", $this->up_dir . '/icon-list.png');
        }
    }

    function set_options() {
        $this->options = array(
            'plugin-id' => 'wpu_tinymce',
            'buttons' => $this->buttons,
            'quicktags' => $this->quicktags
        );
    }

    function load_assets() {
        $screen = get_current_screen();
        if ($screen->base == 'post') {
            wp_enqueue_script($this->options['plugin-id'] . '__functions', plugins_url('/assets/functions.js', __FILE__) , array() , $this->plugin_version);
        }
    }

    function set_buttons() {
        if (current_user_can('edit_posts') && current_user_can('edit_pages')) {
            add_filter("mce_external_plugins", array(&$this,
                'add_plugins'
            ));
            add_filter('mce_buttons', array(&$this,
                'add_buttons'
            ));
        }
    }

    // add more buttons to the html editor
    function set_quicktags() {

        if (!wp_script_is('quicktags')) {
            return;
        }
        $post_type = get_post_type();

        echo '<script type="text/javascript">';
        foreach ($this->options['quicktags'] as $button_id => $button) {
            if (in_array('any', $button['post_type']) || in_array($post_type, $button['post_type'])) {
                $item_id = "wputinycme_" . $button_id;
                $callback_item = 'callback__' . $item_id;
                echo 'function ' . $callback_item . '(){wputinymce_insertAtCursor(document.getElementById( \'content\' ),wputinymce_filter_vars("' . addslashes($button['html']) . '"));};';
                echo "QTags.addButton( '" . $item_id . "', '" . addslashes($button['title']) . "', " . $callback_item . ", '', '', '" . addslashes($button['title']) . "', 200 );\n";
            }
        }
        echo '</script>';
    }

    function add_plugins($plugins = array()) {
        $plugins[$this->options['plugin-id']] = $this->up_url . '/cache.js';
        return $plugins;
    }

    function add_buttons($buttons = array()) {
        $post_type = get_post_type();
        foreach ($this->options['buttons'] as $button_id => $button) {
            if (in_array('any', $button['post_type']) || in_array($post_type, $button['post_type'])) {
                $buttons[] = $button_id;
            }
        }
        return $buttons;
    }
}

new WPUTinyMCE();


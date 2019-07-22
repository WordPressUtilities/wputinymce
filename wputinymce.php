<?php

/*
Plugin Name: WPU TinyMCE Buttons
Plugin URI: http://github.com/Darklg/WPUtilities
Description: Add new buttons to TinyMCE
Version: 0.10.2
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUTinyMCE {
    public $plugin_version = '0.10.2';
    private $files_length = false;
    private $options = array(
        'plugin-id' => 'wpu_tinymce'
    );

    public function __construct() {

        $upload_dir = wp_upload_dir();
        $this->up_dir = $upload_dir['basedir'] . '/wpu_tinymce-cache';
        $this->up_url = $upload_dir['baseurl'] . '/wpu_tinymce-cache';
        $this->plugin_assets_dir = dirname(__FILE__) . '/assets/';

        add_action('init', array(&$this,
            'check_update'
        ));
        add_action('init', array(&$this,
            'check_buttons_list'
        ));
        add_action('init', array(&$this,
            'set_options'
        ));
        add_action('init', array(&$this,
            'set_buttons'
        ));

        add_action('wp_enqueue_scripts', array(&$this,
            'add_css_front'
        ));

        if (!is_admin()) {
            return;
        }

        add_action('admin_print_footer_scripts', array(&$this,
            'set_quicktags'
        ));
        add_action('admin_enqueue_scripts', array(&$this,
            'load_assets'
        ));
    }

    public function check_update() {
        include dirname(__FILE__) . '/inc/WPUBaseUpdate/WPUBaseUpdate.php';
        $this->settings_update = new \wputinymce\WPUBaseUpdate(
            'WordPressUtilities',
            'wputinymce',
            $this->plugin_version);
    }

    public function check_buttons_list() {

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

            if (!isset($button['html']) && isset($button['before_select'], $button['after_select'])) {
                $button['html'] = $button['before_select'] . $button['title'] . $button['after_select'];
            }

            if (isset($button['html'])) {
                if ($button['quicktag']) {
                    $this->quicktags[$button_id] = $button;
                }
                $this->buttons[$button_id] = $button;
            }
        }

        $buttons_version = $this->get_buttons_version();
        $buttons_version_option = get_option('wputinymce_buttons_version');
        $this->files_length = get_option('wputinymce_files_length');

        // File does not exists or invalid version
        if (!is_array($this->files_length) || !file_exists($this->up_dir . '/cache.js') || !file_exists($this->up_dir . '/cache.css') || !file_exists($this->up_dir . '/admin-cache.css') || !file_exists($this->up_dir . '/admin-cache.css') || $buttons_version != $buttons_version_option) {
            $this->regenerate_js_file();
            $this->regenerate_css_file();
            update_option('wputinymce_buttons_version', $buttons_version);
        }

    }

    public function regenerate_js_file() {

        // Check cache directory
        if (!is_dir($this->up_dir)) {
            @mkdir($this->up_dir, 0777);
            @chmod($this->up_dir, 0777);
        }

        // Regenerate JS
        $js = "(function(){\n";
        $js .= "var wpu_tinymce_items = [];\n";

        foreach ($this->buttons as $button_id => $button) {
            $js .= "wpu_tinymce_items.push(" . json_encode($button) . ");\n";
        }

        $js .= file_get_contents($this->plugin_assets_dir . "tinymce-create.js") . "\n";
        $js .= "}());";

        file_put_contents($this->up_dir . '/cache.js', $js);

        // Copy default icon
        if (!file_exists($this->up_dir . '/icon-list.png')) {
            copy($this->plugin_assets_dir . "icon-list.png", $this->up_dir . '/icon-list.png');
        }
    }

    public function regenerate_css_file() {

        // Check cache directory
        if (!is_dir($this->up_dir)) {
            @mkdir($this->up_dir, 0777);
            @chmod($this->up_dir, 0777);
        }

        // Regenerate CSS
        $css = '';
        $admin_css = '';
        $front_css = '';
        foreach ($this->buttons as $button_id => $button) {
            if (isset($button['css'])) {
                $css .= $button['css'];
            }
            if (isset($button['front_css'])) {
                $front_css .= $button['front_css'];
            }
            if (isset($button['admin_css'])) {
                $admin_css .= $button['admin_css'];
            }
        }

        /* Clean up CSS */
        $admin_css = trim($admin_css);
        $front_css = trim($front_css);
        $css = trim($css);

        /* Save files */
        file_put_contents($this->up_dir . '/admin-cache.css', $admin_css);
        file_put_contents($this->up_dir . '/front-cache.css', $front_css);
        file_put_contents($this->up_dir . '/cache.css', $css);

        /* Save details */
        $this->files_length = array(
            'admin' => strlen($admin_css),
            'front' => strlen($front_css),
            'css' => strlen($css)
        );
        update_option('wputinymce_files_length', $this->files_length);
    }

    public function set_options() {
        $this->options['buttons'] = $this->buttons;
        $this->options['quicktags'] = $this->quicktags;
    }

    public function load_assets() {
        wp_enqueue_script($this->options['plugin-id'] . '__functions', plugins_url('/assets/functions.js', __FILE__), array(), $this->plugin_version);
    }

    public function set_buttons() {
        if (current_user_can('edit_posts') && current_user_can('edit_pages')) {
            add_filter("mce_external_plugins", array(&$this,
                'add_plugins'
            ));
            add_filter("mce_css", array(&$this,
                'add_styles'
            ));
            add_filter('mce_buttons', array(&$this,
                'add_buttons'
            ));
        }
    }

    // add more buttons to the html editor
    public function set_quicktags() {

        if (!wp_script_is('quicktags')) {
            return;
        }
        $post_type = get_post_type();

        echo '<script type="text/javascript">';
        foreach ($this->options['quicktags'] as $button_id => $button) {
            if (in_array('any', $button['post_type']) || in_array($post_type, $button['post_type'])) {
                $item_id = "wputinymce_" . $button_id;
                $callback_item = 'callback__' . $item_id;
                echo 'function ' . $callback_item . '(b,el){wputinymce_insertAtCursor(el,wputinymce_filter_vars("' . addslashes($button['html']) . '"));};';
                echo "QTags.addButton( '" . $item_id . "', '" . addslashes($button['title']) . "', " . $callback_item . ", '', '', '" . addslashes($button['title']) . "', 200 );\n";
            }
        }
        echo '</script>';
    }

    public function add_plugins($plugins = array()) {
        $plugins[$this->options['plugin-id']] = $this->up_url . '/cache.js?v=' . $this->get_buttons_version();
        return $plugins;
    }

    public function add_styles($styles = '') {
        $more_styles = array();
        if ($this->files_length['css'] > 0) {
            $more_styles[] = $this->up_url . '/cache.css?v=' . $this->get_buttons_version();
        }
        if ($this->files_length['admin'] > 0) {
            $more_styles[] = $this->up_url . '/admin-cache.css?v=' . $this->get_buttons_version();
        }
        $more_styles = implode(',', $more_styles);
        if (!empty($more_styles)) {
            $styles .= ',' . $more_styles;
        }
        return $styles;
    }

    public function add_css_front() {
        if ($this->files_length['css'] > 0) {
            wp_enqueue_style('wputinymce-style', $this->up_url . '/cache.css?v=' . $this->get_buttons_version());
        }
        if ($this->files_length['front'] > 0) {
            wp_enqueue_style('wputinymce-style-front', $this->up_url . '/front-cache.css?v=' . $this->get_buttons_version());
        }
    }

    public function add_buttons($buttons = array()) {
        $post_type = get_post_type();
        foreach ($this->options['buttons'] as $button_id => $button) {
            if (in_array('any', $button['post_type']) || in_array($post_type, $button['post_type'])) {
                $buttons[] = $button_id;
            }
        }
        return $buttons;
    }

    /* ----------------------------------------------------------
      Helpers
    ---------------------------------------------------------- */

    public function get_buttons_version() {
        return md5($this->plugin_version . serialize($this->buttons));
    }

}

new WPUTinyMCE();

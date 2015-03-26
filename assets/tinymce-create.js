
tinymce.create('tinymce.plugins.WPUTinyMCE', {
    init: function(ed, url) {
        'use strict';
        for (var i = 0, item, len = wpu_tinymce_items.length; i < len; i++) {
            wputinymce_addbutton(ed, wpu_tinymce_items[i]);
        }
    },
    createControl: function(n, cm) {
        return null;
    },
});
tinymce.PluginManager.add('wpu_tinymce', tinymce.plugins.WPUTinyMCE);
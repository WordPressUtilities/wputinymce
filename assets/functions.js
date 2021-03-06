/* ----------------------------------------------------------
  Filter inserted vars
---------------------------------------------------------- */

function wputinymce_filter_vars(html) {
    var regexConfirm = /({{[a-z0-9 ]+}})/g,
        replace,
        match,
        fullMatches = [],
        matches = html.match(regexConfirm);

    if (!matches) {
        return html;
    }

    // Detect {{ vars }} in tinymce content
    for (var i = 0, len = matches.length; i < len; i++) {
        match = matches[i].replace(/[\{\ \}]/g, '');

        // Check only one time per var
        if (fullMatches.indexOf(match) >= 0) {
            continue;
        }
        fullMatches.push(match);

        // Ask a new value
        replace = prompt('Value for "' + match + '" ?');

        // Replace all occurrences
        html = html.replace(new RegExp(matches[i], 'g'), replace);
    }
    return html;
}

/* ----------------------------------------------------------
  Remove tags
---------------------------------------------------------- */

function wputinycme_clear_html(html) {
    if (html == null) return "";
    var tmp = document.createElement("DIV");
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || "";
}

/* ----------------------------------------------------------
  Insert text at cursor position
---------------------------------------------------------- */
/* Thx to http://stackoverflow.com/a/11077016 */

function wputinymce_insertAtCursor(item, val) {
    if (document.selection) {
        item.focus();
        sel = document.selection.createRange();
        sel.text = val;
    }
    else if (item.selectionStart || item.selectionStart == '0') {
        var startPos = item.selectionStart,
            endPos = item.selectionEnd;
        item.value = item.value.substring(0, startPos) + val + item.value.substring(endPos, item.value.length);
    }
    else {
        item.value += val;
    }
}

/* ----------------------------------------------------------
  AddButton
---------------------------------------------------------- */

function wputinymce_addbutton(ed, item) {
    ed.addButton(item.id, {
        title: item.title,
        image: item.image,
        onclick: function() {
            var _sel = ed.selection.getContent();
            if (!_sel || !item.before_select || !item.after_select) {
                ed.selection.setContent(wputinymce_filter_vars(item.html));
            }
            else {
                ed.focus();
                ed.selection.setContent(item.before_select + wputinycme_clear_html(ed.selection.getContent()) + item.after_select);
            }
        }
    });
}

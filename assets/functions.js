function wputinymce_filter_vars(html) {
        var regexConfirm = /({{[a-z0-9 ]+}})/g,replace,
        match,
        fullMatches = [],
        matches = html.match(regexConfirm);

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

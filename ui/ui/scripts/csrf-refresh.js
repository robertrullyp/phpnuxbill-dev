(function ($) {
    if (typeof window.csrfEnabled !== 'undefined' && !window.csrfEnabled) {
        return;
    }

    $.ajaxSetup({
        beforeSend: function (xhr, settings) {
            const token = $('input[name="csrf_token"]').first().val();
            if (token && settings.type !== 'GET') {
                if (settings.data instanceof FormData) {
                    settings.data.append('csrf_token', token);
                } else if (typeof settings.data === 'string' || !settings.data) {
                    settings.data = (settings.data ? settings.data + '&' : '') +
                                    'csrf_token=' + encodeURIComponent(token);
                }

                if (settings.contentType && settings.contentType.indexOf('application/json') === 0) {
                    xhr.setRequestHeader('X-CSRF-Token', token);
                }
            }
        }
    });

    function refreshCsrfToken() {
        $.getJSON(appUrl + '/?_route=csrf-refresh')
            .done(function (data) {
                if (data && typeof data === 'object') {
                    if (data.csrf_token) {
                        $('input[name="csrf_token"]').val(data.csrf_token);
                    }

                    if (data.csrf_token_logout) {
                        $('input[name="csrf_token_logout"]').val(data.csrf_token_logout);
                    }
                }
            })
            .fail(function () {
                console.warn('CSRF refresh gagal');
            });
    }

    $(document).ajaxComplete(function (_e, _xhr, settings) {
        if (!settings.url.includes('csrf-refresh')) {
            refreshCsrfToken();
        }
    });

    // Refresh tokens periodically (every 20 minutes)
    setInterval(refreshCsrfToken, 20 * 60 * 1000);

    // Initial refresh in case the page was loaded via AJAX
    refreshCsrfToken();

    window.refreshCsrfToken = refreshCsrfToken;
})(jQuery);

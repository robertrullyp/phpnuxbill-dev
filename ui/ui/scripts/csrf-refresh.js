(function ($) {
    if (typeof window.csrfEnabled !== 'undefined' && !window.csrfEnabled) {
        return;
    }

    $.ajaxSetup({
        beforeSend: function(xhr, settings){
            const token = $('input[name="csrf_token"]').first().val();
            if (token && settings.type !== 'GET'){
                settings.data = (settings.data ? settings.data + '&' : '') + 'csrf_token=' + encodeURIComponent(token);
            }
        }
    });

    function refreshCsrfToken() {
        $.getJSON(appUrl + '/?_route=csrf-refresh')
            .done(function (data) {
                if (data && typeof data === 'object' && data.csrf_token) {
                    // Update general CSRF tokens but leave logout token untouched
                    $('input[name="csrf_token"]').not('[name="csrf_token_logout"]').val(data.csrf_token);
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

    // Refresh tokens periodically (every 10 minutes)
    setInterval(refreshCsrfToken, 10 * 60 * 1000);

    // Initial refresh in case the page was loaded via AJAX
    refreshCsrfToken();

    window.refreshCsrfToken = refreshCsrfToken;
})(jQuery);

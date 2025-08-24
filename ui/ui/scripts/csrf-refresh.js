(function ($) {
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

    $(document).ajaxComplete(function () {
        refreshCsrfToken();
    });

    // Initial refresh in case the page was loaded via AJAX
    refreshCsrfToken();

    window.refreshCsrfToken = refreshCsrfToken;
})(jQuery);

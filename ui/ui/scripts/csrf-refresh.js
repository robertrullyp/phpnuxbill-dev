(function ($) {
    function refreshCsrfToken() {
        $.getJSON(appUrl + '/?_route=csrf-refresh', function (data) {
            if (data && data.csrf_token) {
                $('input[name="csrf_token"]').val(data.csrf_token);
            }
        });
    }

    $(document).ajaxComplete(function () {
        refreshCsrfToken();
    });

    // Initial refresh in case the page was loaded via AJAX
    refreshCsrfToken();

    window.refreshCsrfToken = refreshCsrfToken;
})(jQuery);

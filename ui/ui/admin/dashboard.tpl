{include file="sections/header.tpl"}

{function showWidget pos=0}
    {foreach $widgets as $w}
        {if $w['position'] == $pos}
            {$w['content']}
        {/if}
    {/foreach}
{/function}

{assign dtipe value="dashboard_`$tipeUser`"}

{assign rows explode(".", $_c[$dtipe])}
{assign pos 1}
{foreach $rows as $cols}
    {if $cols == 12}
        <div class="row">
            <div class="col-md-12">
                {showWidget widgets=$widgets pos=$pos}
            </div>
        </div>
        {assign pos value=$pos+1}
    {else}
        {assign colss explode(",", $cols)}
        <div class="row">
            {foreach $colss as $c}
                <div class="col-md-{$c}">
                    {showWidget widgets=$widgets pos=$pos}
                </div>
                {assign pos value=$pos+1}
            {/foreach}
        </div>
    {/if}
{/foreach}

{if $_c['new_version_notify'] != 'disable'}
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            function latestFromUpdatesJson(updates) {
                try {
                    var keys = Object.keys(updates || {});
                    if (!keys.length) {
                        return null;
                    }
                    function weight(v) {
                        var p = (v + '').split('.').map(function (x) { return parseInt(x, 10) || 0; });
                        return (p[0] || 0) * 10000 + (p[1] || 0) * 100 + (p[2] || 0);
                    }
                    var best = keys[0];
                    var bestW = weight(best);
                    keys.forEach(function (k) {
                        var w = weight(k);
                        if (w > bestW) {
                            best = k;
                            bestW = w;
                        }
                    });
                    return best;
                } catch (e) {
                    return null;
                }
            }

            function fetchLatestRemoteVersion() {
                var verUrl = 'https://raw.githubusercontent.com/robertrullyp/phpnuxbill-dev/main/version.json?' + Math.random();
                var updUrl = 'https://raw.githubusercontent.com/robertrullyp/phpnuxbill-dev/main/system/updates.json?' + Math.random();
                return fetch(verUrl, { mode: 'cors' })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('bad status');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        if (data && data.version) {
                            return data.version;
                        }
                        throw new Error('no version');
                    })
                    .catch(function () {
                        return fetch(updUrl, { mode: 'cors' })
                            .then(function (response) {
                                if (!response.ok) {
                                    throw new Error('bad status');
                                }
                                return response.json();
                            })
                            .then(function (data) {
                                return latestFromUpdatesJson(data);
                            });
                    })
                    .then(function (version) {
                        return version || null;
                    })
                    .catch(function () {
                        return null;
                    });
            }

            var localVersion = null;
            fetch('./version.json?' + Math.random())
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('bad status');
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (data && data.version) {
                        localVersion = data.version;
                        $('#version').html('Version: ' + localVersion);
                    } else {
                        $('#version').html('Version: unknown');
                    }
                    if (!localVersion) {
                        return;
                    }
                    fetchLatestRemoteVersion().then(function (latestVersion) {
                        if (!latestVersion || localVersion === latestVersion) {
                            return;
                        }
                        $('#version').html('Latest Version: ' + latestVersion);
                        if (getCookie(latestVersion) != 'done') {
                            Swal.fire({
                                icon: 'info',
                                title: "New Version Available\nVersion: " + latestVersion,
                                toast: true,
                                position: 'bottom-right',
                                showConfirmButton: true,
                                showCloseButton: true,
                                timer: 30000,
                                confirmButtonText: '<a href="{Text::url('community')}#latestVersion" style="color: white;">Update Now</a>',
                                timerProgressBar: true,
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer)
                                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                                }
                            });
                            setCookie(latestVersion, 'done', 7);
                        }
                    });
                })
                .catch(function () {
                    $('#version').html('Version: unknown');
                });
        });
    </script>
{/if}

{include file="sections/footer.tpl"}

{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-6">
        <div class="box box-hovered mb20 box-primary">
            <div class="box-header">
                <h3 class="box-title">{Lang::T('Chat with Me — Bring Some Cup of Coffee')}</h3>
            </div>
            <div class="box-body">
                {Lang::T('Let\'s talk and ask your needs')}
            </div>
            <div class="box-footer">
                <a href="https://t.me/robertrullyp" target="_blank" class="btn btn-primary btn-sm btn-block">{Lang::T('Telegram')}</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="box box-hovered mb20 box-primary">
            <div class="box-header">
                <h3 class="box-title">{Lang::T('Discussion – Get Help from the Community')}</h3>
            </div>
            <div class="box-body">{Lang::T('Join the discussion to find solutions and support from a community ready to help.')}</div>
            <div class="box-footer">
                <div class="btn-group btn-group-justified" role="group" aria-label="...">
                    <a href="https://github.com/hotspotbilling/phpnuxbill/discussions" target="_blank"
                        class="btn btn-primary btn-sm btn-block"><i class="ion ion-chatboxes"></i> {Lang::T('Github
                        Discussions')}</a>
                    <a href="https://t.me/phpnuxbill" target="_blank" class="btn btn-primary btn-sm btn-block"><i
                            class="ion ion-chatboxes"></i> {Lang::T('Telegram Group')}</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-6">
        <div class="box box-hovered mb20 box-primary">
            <div class="box-header">
                <h3 class="box-title">{Lang::T('Chat with Me — Paid Support $50')}</h3>
            </div>
            <div class="box-body">
                {Lang::T('Confirm your donation to continue this paid support. Or, ask about alternative donations available to suit your needs.')}
            </div>
            <div class="box-footer">
                <a href="https://t.me/ibnux" target="_blank" class="btn btn-primary btn-sm btn-block">{Lang::T('Telegram')}</a>
            </div>
        </div>
        <div class="box box-primary box-hovered mb20 activities">
            <div class="box-header">
                <h3 class="box-title">{Lang::T('WhatsApp Gateway and Free Telegram Bot')}</h3>
            </div>
            <div class="box-body">
                {Lang::T('Connect your PHPNuxBill to WhatsApp efficiently using WhatsApp Gateway. Also, create Telegram bots easily and practically.')}
            </div>
            <div class="box-footer">
                <a href="https://wa.nux.my.id/login" target="_blank"
                    class="btn btn-primary btn-sm btn-block">wa.nux.my.id</a>
            </div>
        </div>
        <div class="box box-hovered mb20 box-primary">
    <div class="box-header">
        <h3 class="box-title">{Lang::T('Credits')}</h3>
    </div>
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>{Lang::T('Souce')}</th>
                            <th>{Lang::T('Details')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Bootstrap V3</td>
                            <td>
                                <a href="https://getbootstrap.com/docs/3.4/" target="_blank">
                                    <i class="glyphicon glyphicon-globe"></i> {Lang::T('Visit')}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>Admin LTE V3</td>
                            <td>
                                <a href="https://adminlte.io/themes/v3/" target="_blank">
                                    <i class="glyphicon glyphicon-globe"></i> {Lang::T('Visit')}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>Smarty Template V4</td>
                            <td>
                                <a href="https://www.smarty.net/" target="_blank">
                                    <i class="glyphicon glyphicon-globe"></i> {Lang::T('Visit')}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>PHP IdiORM</td>
                            <td>
                                <a href="https://idiorm.readthedocs.io/" target="_blank">
                                    <i class="glyphicon glyphicon-globe"></i> {Lang::T('Visit')}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>PHP mPDF</td>
                            <td>
                                <a href="https://mpdf.github.io/" target="_blank">
                                    <i class="glyphicon glyphicon-globe"></i> {Lang::T('Visit')}
                                </a>
                            </td>
                        </tr>
                    <tr>
                            <td>PHP QRCode</td>
                            <td>
                                <a href="http://phpqrcode.sourceforge.net/" target="_blank">
                                    <i class="glyphicon glyphicon-globe"></i> {Lang::T('Visit')}
                                </a>
                            </td>
                        </tr>
                    <tr>
                            <td>PHP Net_RouterOS</td>
                            <td>
                                <a href="https://github.com/pear2/Net_RouterOS" target="_blank">
                                    <i class="glyphicon glyphicon-globe"></i> {Lang::T('Visit')}
                                </a>
                            </td>
                        </tr>
                    <tr>
                            <td>Summernote</td>
                            <td>
                                <a href="https://summernote.org/" target="_blank">
                                    <i class="glyphicon glyphicon-globe"></i> {Lang::T('Visit')}
                                </a>
                            </td>
                        </tr>
                    <tr>
                            <td>PHP Mailer</td>
                            <td>
                                <a href="https://github.com/PHPMailer/PHPMailer/" target="_blank">
                                    <i class="glyphicon glyphicon-globe"></i> {Lang::T('Visit')}
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
    <div class="col-sm-6" id="update">
        <div class="box box-primary box-hovered mb20 activities">
            <div class="box-header">
                <h3 class="box-title">PHPNUXBILL</h3>
            </div>
            <div class="box-body">
                <b>PHPNuxBill</b>
                {Lang::T('is a Hotspot and PPPoE billing platform for Mikrotik developed using PHP. The application uses Mikrotik API to communicate with the router, ensuring efficient and easy integration. If you feel you get more benefits from this application, we would greatly appreciate your contribution through donation.')}<br>{Lang::T('Watch project –')} <a
                    href="https://github.com/robertrullyp/phpnuxbill-dev" target="_blank">{Lang::T('IN HERE')}</a>
            </div>
            <div class="box-footer" id="currentVersion">ver</div>
            <div class="box-footer" id="latestVersion">ver</div>
            <div class="box-footer">
                <div class="btn-group btn-group-justified" role="group" aria-label="...">
                    <a href="./update.php"
                        class="btn btn-success btn-sm btn-block">{Lang::T('Install Latest Version')}</a>
                    <a href="https://github.com/robertrullyp/phpnuxbill-dev/archive/refs/heads/main.zip" target="_blank"
                        class="btn btn-warning btn-sm btn-block text-black">{Lang::T('Download Latest Version')}</a>
                </div>
                <center><a href="{Text::url('community/rollback')}"
                        class="btn btn-link btn-sm btn-block">{Lang::T('Select Old Version')}</a>
                </center>
            </div>
            <div class="box-footer">
                <div class="btn-group btn-group-justified" role="group" aria-label="...">
                    <a href="./CHANGELOG.md" target="_blank"
                        class="btn btn-default btn-sm btn-block">{Lang::T('Current Changelog')}</a>
                    <a href="https://github.com/robertrullyp/phpnuxbill-dev/blob/main/CHANGELOG.md" target="_blank"
                        class="btn btn-default btn-sm btn-block">{Lang::T('Repo Changelog')}</a>
                </div>
            </div>
            <div class="box-footer">
                {Lang::T('If you download the update file manually, sometimes the update may change the database structure. After the file is successfully uploaded, click this button to update the database structure.')}
                <a href="./update.php?step=4" class="btn btn-default btn-sm btn-block">{Lang::T('Update Database')}</a>
            </div>
        </div>


</div>
<script>
    window.addEventListener('DOMContentLoaded', function () {
        function setText(sel, txt) {
            var el = document.querySelector(sel);
            if (el) el.textContent = txt;
        }
        // Current version (local)
        fetch('./version.json?' + Math.random())
            .then(function (r) { return r.json(); })
            .then(function (data) { setText('#currentVersion', 'Current Version: ' + data.version); })
            .catch(function () { setText('#currentVersion', 'Current Version: unknown'); });

        // Helpers
        function latestFromUpdatesJson(updates) {
            try {
                var keys = Object.keys(updates || {});
                if (!keys.length) return null;

                function toSegments(version) {
                    var normalized = (version == null) ? '' : String(version);
                    if (!normalized) return null;
                    var raw = normalized.split('.');
                    if (!raw.length) return null;
                    var segs = [];
                    for (var i = 0; i < raw.length; i++) {
                        var part = raw[i].trim();
                        if (!/^\d+$/.test(part)) return null;
                        segs.push(parseInt(part, 10));
                    }
                    return segs;
                }

                function compareSegments(a, b) {
                    var len = Math.max(a.length, b.length);
                    for (var i = 0; i < len; i++) {
                        var ai = typeof a[i] === 'number' ? a[i] : 0;
                        var bi = typeof b[i] === 'number' ? b[i] : 0;
                        if (ai > bi) return 1;
                        if (ai < bi) return -1;
                    }
                    return 0;
                }

                var bestKey = null;
                var bestSegs = null;

                keys.forEach(function (key) {
                    var segs = toSegments(key);
                    if (!segs) {
                        return;
                    }
                    if (!bestSegs || compareSegments(segs, bestSegs) > 0) {
                        bestSegs = segs;
                        bestKey = key;
                    }
                });

                return bestKey;
            } catch (e) {
                return null;
            }
        }

        function setLatestFromRemote() {
            var verUrl = 'https://raw.githubusercontent.com/robertrullyp/phpnuxbill-dev/main/version.json?' + Math.random();
            var updUrl = 'https://raw.githubusercontent.com/robertrullyp/phpnuxbill-dev/main/system/updates.json?' + Math.random();
            fetch(verUrl, { mode: 'cors' })
                .then(function (r) { if (!r.ok) throw new Error('bad status'); return r.json(); })
                .then(function (data) { if (data && data.version) { setText('#latestVersion', 'Latest Version: ' + data.version); } else { throw new Error('no version'); } })
                .catch(function () {
                    // Fallback: infer latest from updates.json
                    fetch(updUrl, { mode: 'cors' })
                        .then(function (r) { if (!r.ok) throw new Error('bad status'); return r.json(); })
                        .then(function (data) {
                            var v = latestFromUpdatesJson(data);
                            if (v) setText('#latestVersion', 'Latest Version: ' + v); else setText('#latestVersion', 'Latest Version: unavailable');
                        })
                        .catch(function () { setText('#latestVersion', 'Latest Version: unavailable'); });
                });
        }

        // Latest version (remote repo) with fallback
        setLatestFromRemote();
    });
    </script>
{include file="sections/footer.tpl"}

{if empty($_user)}
    {include file="customer/header-public.tpl"}
{else}
    {include file="customer/header.tpl"}
{/if}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>
<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <div class="panel panel-hovered panel-primary panel-stacked mb30">
            <div class="panel-heading">{$in['invoice']}</div>
            <div class="panel-body">
                {if !empty($logo)}
                    <center><img src="{$app_url}/{$logo|replace:'\\':'/'}" class="img-responsive"></center>
                {/if}
                <form class="form-horizontal" method="post" action="{Text::url('plan/print')}" target="_blank">
                    <pre id="content"
                        style="border: 0px; ;text-align: center; background-color: transparent; background-image: url('{$app_url}/system/uploads/paid.png');background-repeat:no-repeat;background-position: center">{$invoice}</pre>
                    <input type="hidden" name="id" value="{$in['id']}">
                    {if !empty($_user)}
                        <a href="{Text::url('voucher/list-activated')}" class="btn btn-default btn-sm"><i
                            class="ion-reply-all"></i>{Lang::T('Finish')}</a>
                    {/if}
                    <a href="javascript:download()" class="btn btn-success btn-sm text-black">
                        <i class="glyphicon glyphicon-share"></i> Download</a>
                    <a href="https://api.whatsapp.com/send/?text={$whatsapp}" class="btn btn-primary btn-sm">
                        <i class="glyphicon glyphicon-share"></i> WhatsApp</a>
                        <br><br>
                        <input type="text" class="form-control form-sm" style="border: 0px; padding: 1px; background-color: white;" readonly onclick="this.select()" value="{$public_url}">
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    const canvas = document.createElement("canvas");
    const ctx = canvas.getContext('2d');
    var fontSize = 16;
    var lineHeight = Math.round(fontSize * 1.25);
    var marginX = 20;
    var marginY = 20;
    ctx.font = fontSize + 'px Courier';
    var text = document.getElementById("content").textContent;
    var lines = text.split(/\r\n|\r|\n/);
    var cols = parseInt('{$_c.printer_cols|default:30}', 10) || 30;
    var maxLineChars = lines.reduce(function (max, line) {
        return Math.max(max, line.length);
    }, 0);
    var effectiveCols = Math.max(cols, maxLineChars);
    var charWidth = ctx.measureText("A").width || 9.6;
    var textWidth = Math.ceil(effectiveCols * charWidth);
    let width = Math.max(textWidth + (marginX * 2), 220);
    var height = Math.ceil((lineHeight * lines.length) + (marginY * 2));
    var paid = new Image();
    paid.src = '{$app_url}/system/uploads/paid.png';
    {if !empty($logo)}
        var img = new Image();
        img.src = '{$app_url}/{$logo|replace:'\\':'/'}?{time()}';
        var maxLogoWidth = Math.min(Math.round(width * 0.45), 240);
        var new_width = Math.min(textWidth, maxLogoWidth);
        var new_height = Math.ceil({$hlogo} * (new_width/{$wlogo}));
        height = height + new_height + 6;
    {/if}

    function download() {
        var doc = new jsPDF('p', 'px', [width, height]);
        {if !empty($logo)}
            try {
                doc.addImage(img, 'PNG', (width - new_width) / 2, marginY, new_width, new_height);
            } catch (err) {}
        {/if}
        try {
            doc.addImage(paid, 'PNG', (width - 200) / 2, (height - 145) / 2, 200, 145);
        } catch (err) {}
        doc.setFont("Courier");
        doc.setFontSize(fontSize);
        var textY = marginY + fontSize;
        {if !empty($logo)}
            textY = marginY + new_height + 6 + fontSize;
        {/if}
        for (var i = 0; i < lines.length; i++) {
            doc.text(lines[i], width / 2, textY + (i * lineHeight), 'center');
        }
        doc.save('{$in['invoice']}.pdf');
    }
</script>
{include file="customer/footer.tpl"}

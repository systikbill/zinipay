{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h3 style="margin: 0;">ZiniPay Website Debug Logs</h3>
            <div style="display: flex; gap: 10px;">
                <a href="{$_url}paymentgateway/zinipay" class="btn btn-default"><i class="fa fa-cog"></i> Gateway Settings</a>
                {if !empty($logs)}
                <form method="post" action="{$_url}paymentgateway/zinipay" style="margin: 0; display: inline-block;">
                    <button type="submit" name="clear" value="clear" class="btn btn-danger" onclick="return confirm('Clear debug log file?')"><i class="fa fa-trash"></i> Clear Logs</button>
                </form>
                {/if}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        {if empty($logs)}
            <div class="well text-center" style="padding: 40px;">
                <p style="margin: 0;">Log file is empty.</p>
            </div>
        {else}
            <ul class="nav nav-pills" style="margin-bottom: 20px;">
                <li class="active"><a href="#" onclick="filterLogs('ALL', event)">All</a></li>
                <li><a href="#" onclick="filterLogs('INFO', event)">Info</a></li>
                <li><a href="#" onclick="filterLogs('SENT', event)">Sent</a></li>
                <li><a href="#" onclick="filterLogs('RECEIVED', event)">Received</a></li>
                <li><a href="#" onclick="filterLogs('ERROR', event)">Error</a></li>
            </ul>

            <div class="list-group log-list">
                {foreach $logs as $item}
                    <div class="list-group-item log-item" data-type="{$item['type']}" style="margin-bottom: 10px; border-radius: 4px; cursor: pointer;">
                        <div onclick="toggleLog(this)" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                            <div style="display: flex; align-items: center; gap: 15px; flex-grow: 1; min-width: 0;">
                                <span class="text-muted" style="font-family: monospace; font-size: 12px; white-space: nowrap;">{$item['timestamp']}</span>
                                <span class="label {if $item['type'] == 'ERROR'}label-danger{elseif $item['type'] == 'SENT'}label-primary{elseif $item['type'] == 'RECEIVED'}label-success{else}label-info{/if}" style="min-width: 75px; display: inline-block; text-align: center;">
                                    {$item['type']}
                                </span>
                                <span class="log-message-text" style="font-size: 13px; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{$item['message']}</span>
                            </div>
                            <i class="fa fa-chevron-right log-arrow" style="transition: transform 0.2s ease; margin-left: 10px;"></i>
                        </div>
                        {if !empty($item['data'])}
                            <div class="log-details" style="display: none; margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ddd;">
                                <pre style="background: transparent; border: none; padding: 0; margin: 0; font-family: monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all;">{$item['data']}</pre>
                            </div>
                        {/if}
                    </div>
                {/foreach}
            </div>
        {/if}
    </div>
</div>

<script>
    function toggleLog(header) {
        const item = header.closest('.log-item');
        const details = item.querySelector('.log-details');
        const arrow = item.querySelector('.log-arrow');
        if (details) {
            if (details.style.display === 'none') {
                details.style.display = 'block';
                arrow.style.transform = 'rotate(90deg)';
            } else {
                details.style.display = 'none';
                arrow.style.transform = '';
            }
        }
    }

    function filterLogs(type, event) {
        event.preventDefault();
        const pills = document.querySelectorAll('.nav-pills li');
        pills.forEach(pill => pill.classList.remove('active'));
        event.currentTarget.parentElement.classList.add('active');

        const items = document.querySelectorAll('.log-item');
        items.forEach(item => {
            const itemType = item.getAttribute('data-type');
            if (type === 'ALL' || itemType === type) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
</script>

{include file="sections/footer.tpl"}

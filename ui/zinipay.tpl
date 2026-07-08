{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" autocomplete="off" role="form" action="{$_url}paymentgateway/zinipay">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">ZiniPay Payment Gateway Settings</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">API Key</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="zinipay_api_key" name="zinipay_api_key"
                                value="{$_c['zinipay_api_key']}" placeholder="Your ZiniPay API Key" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Environment</label>
                        <div class="col-md-6">
                            <select class="form-control" id="zinipay_env" name="zinipay_env">
                                <option value="Sandbox" {if $_c['zinipay_env'] == 'Sandbox'}selected{/if}>Sandbox</option>
                                <option value="Live" {if $_c['zinipay_env'] == 'Live'}selected{/if}>Live</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Debug Logging</label>
                        <div class="col-md-6">
                            <select class="form-control" id="zinipay_debug" name="zinipay_debug">
                                <option value="0" {if $_c['zinipay_debug'] == '0'}selected{/if}>Disable</option>
                                <option value="1" {if $_c['zinipay_debug'] == '1'}selected{/if}>Enable</option>
                            </select>
                            <small class="help-block">Log API requests/responses for troubleshooting.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Url Callback / Redirect</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" onclick="this.select()"
                                value="{$_url}order/view/{ldelim}id{rdelim}/check">
                            <small class="help-block">This redirect URL is automatically passed with the transaction request.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Save']}</button>
                            <a href="{$_url}paymentgateway/zinipay&view=logs" class="btn btn-info waves-effect waves-light" style="margin-left: 10px;"> View Debug Logs</a>
                        </div>
                    </div>
                    <hr/>
                    <h4>MikroTik Walled Garden configuration:</h4>
                    <pre>/ip hotspot walled-garden
add dst-host=zinipay.com
add dst-host=*.zinipay.com
add dst-host=api.zinipay.com</pre>
                </div>
            </div>

        </div>
    </div>
</form>
{include file="sections/footer.tpl"}

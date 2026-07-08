<?php

/**
 * Bismillahir Rahmanir Rahim
 * Plugin Name: ZiniPay payment gateway
 * Author: Mahmud
 * Contact: https://t.me/mahmud_3010
 */

function zinipay_gw_debug_log($msg, $data = null)
{
    global $config;
    if (empty($config['zinipay_debug']) || $config['zinipay_debug'] != '1') {
        return;
    }
    $log_file = __DIR__ . '/zinipay_debug_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_msg = "[$timestamp] $msg";
    if ($data !== null) {
        $log_msg .= " | " . (is_string($data) ? $data : json_encode($data));
    }
    file_put_contents($log_file, $log_msg . PHP_EOL, FILE_APPEND);
}

function zinipay_validate_config()
{
    global $config;
    if (empty($config['zinipay_api_key'])) {
        sendTelegram("ZiniPay payment gateway not configured");
        r2(U . 'order/package', 'w', Lang::T("Admin has not yet setup ZiniPay payment gateway, please tell admin"));
    }
}

function zinipay_show_config()
{
    global $ui, $config;
    
    if (_req('view') == 'logs') {
        $log_file = __DIR__ . '/zinipay_debug_log.txt';
        $logs = '';
        if (file_exists($log_file)) {
            $logs = file_get_contents($log_file);
        }

        $parsed_logs = [];
        if (!empty($logs)) {
            $lines = explode("\n", $logs);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                if (preg_match('/^\[([^\]]+)\]\s+([^|]+)(?:\s*\|\s*(.*))?$/', $line, $matches)) {
                    $timestamp = $matches[1];
                    $message = trim($matches[2]);
                    $data = isset($matches[3]) ? trim($matches[3]) : '';
                    
                    $type = 'INFO';
                    if (stripos($message, 'error') !== false || stripos($message, 'failed') !== false) {
                        $type = 'ERROR';
                    } elseif (stripos($message, 'Calling create') !== false || stripos($message, 'Calling verify') !== false) {
                        $type = 'SENT';
                    } elseif (stripos($message, 'response') !== false || stripos($message, 'verified') !== false || stripos($message, 'success') !== false) {
                        $type = 'RECEIVED';
                    }
                    
                    $parsed_logs[] = [
                        'timestamp' => $timestamp,
                        'type' => $type,
                        'message' => $message,
                        'data' => $data
                    ];
                } else {
                    $parsed_logs[] = [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'type' => 'INFO',
                        'message' => $line,
                        'data' => ''
                    ];
                }
            }
            $parsed_logs = array_reverse($parsed_logs);
        }

        $ui->assign('logs', $parsed_logs);
        $ui->assign('_title', 'ZiniPay Debug Logs');
        $ui->display('zinipay_logs.tpl');
    } else {
        $ui->assign('_title', 'ZiniPay Settings');
        $ui->display('zinipay.tpl');
    }
}

function zinipay_save_config()
{
    global $admin;
    
    if (_post('clear') == 'clear') {
        $log_file = __DIR__ . '/zinipay_debug_log.txt';
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
        }
        r2(U . 'paymentgateway/zinipay&view=logs', 's', 'Logs cleared successfully');
    }
    
    $settings = [
        'zinipay_api_key' => _post('zinipay_api_key'),
        'zinipay_env'     => _post('zinipay_env'),
        'zinipay_debug'   => _post('zinipay_debug'),
    ];

    foreach ($settings as $key => $value) {
        $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if ($d) {
            $d->value = $value;
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = $key;
            $d->value = $value;
            $d->save();
        }
    }

    _log('[' . $admin['username'] . ']: ZiniPay ' . Lang::T('Settings_Saved_Successfully'), 'Admin', $admin['id']);
    r2(U . 'paymentgateway/zinipay', 's', Lang::T('Settings_Saved_Successfully'));
}

function zinipay_create_transaction($trx, $user)
{
    global $config;

    zinipay_gw_debug_log("Payment initiation started for transaction ID: " . $trx['id'], $trx);

    $json = [
        'amount'       => floatval($trx['price']),
        'redirect_url' => U . 'order/view/' . $trx['id'] . '/check',
        'cancel_url'   => U . 'order/package',
        'cus_name'     => $user['fullname'] ?: 'Customer',
        'cus_email'    => $user['email'] ?: $user['username'] . '@demo.systik.net'
    ];

    $headers = [
        'zini-api-key: ' . $config['zinipay_api_key']
    ];

    zinipay_gw_debug_log("Calling create payment API: " . zinipay_gw_get_server() . 'payment/create', $json);
    $response = Http::postJsonData(zinipay_gw_get_server() . 'payment/create', $json, $headers);
    zinipay_gw_debug_log("Create payment API raw response", $response);

    if ($response === false) {
        zinipay_gw_debug_log("Error: HTTP request failed (no response)");
        sendTelegram("ZiniPay Payment Request Failed: Unable to connect.");
        r2(U . 'order/package', 'e', Lang::T("Failed to create transaction. Please try again later."));
    }

    $result = json_decode($response, true);

    if (!is_array($result) || !isset($result['status']) || ($result['status'] !== 'success' && $result['status'] !== true)) {
        zinipay_gw_debug_log("Error: API returned failure or invalid response", $result);
        sendTelegram("ZiniPay Payment Failed\n\n" . json_encode($result, JSON_PRETTY_PRINT));
        $err = isset($result['message']) ? $result['message'] : Lang::T("Failed to create transaction. Please try again later.");
        r2(U . 'order/package', 'e', $err);
    }

    // Extract invoice_id from the end of the payment_url
    $invoice_id = '';
    if (!empty($result['payment_url'])) {
        $url_parts = explode('/', rtrim($result['payment_url'], '/'));
        $invoice_id = end($url_parts);
    }

    if (empty($invoice_id)) {
        zinipay_gw_debug_log("Error: invoice_id could not be extracted from payment_url", $result);
        r2(U . 'order/package', 'e', Lang::T("Failed to retrieve transaction reference."));
    }

    zinipay_gw_debug_log("Payment session created successfully: Invoice ID = " . $invoice_id);

    $d = ORM::for_table('tbl_payment_gateway')
        ->where('username', $user['username'])
        ->where('status', 1)
        ->find_one();
    $d->gateway_trx_id = $invoice_id;
    $d->pg_url_payment = $result['payment_url'];
    $d->pg_request     = json_encode($result);
    $d->expired_date   = date('Y-m-d H:i:s', strtotime('+ 4 HOURS'));
    $d->save();

    zinipay_gw_debug_log("Transaction saved locally. Redirecting user to ZiniPay checkout: " . $result['payment_url']);
    header('Location: ' . $result['payment_url']);
    exit();
}

function zinipay_get_status($trx, $user)
{
    global $config;

    $reference = $trx['gateway_trx_id'];
    zinipay_gw_debug_log("Verify status check triggered for invoice_id: $reference");

    if ($trx['status'] == 2) {
        zinipay_gw_debug_log("Transaction was already marked paid.");
        r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Transaction has been paid.."));
    }

    $maxRetries = 3;
    $retryDelay = 5;
    $statusChecked = false;
    $headers = [
        'zini-api-key: ' . $config['zinipay_api_key']
    ];

    for ($i = 0; $i < $maxRetries; $i++) {
        zinipay_gw_debug_log("Calling verify API for invoice_id: $reference (Attempt " . ($i + 1) . ")");
        $response = Http::postJsonData(zinipay_gw_get_server() . 'payment/verify', [
            'invoice_id' => $reference
        ], $headers);
        zinipay_gw_debug_log("Verify API response (Attempt " . ($i + 1) . ")", $response);
        $result = json_decode($response, true);

        if (isset($result['status'])) {
            $statusChecked = true;
            break;
        }

        sleep($retryDelay);
    }

    if (!$statusChecked) {
        zinipay_gw_debug_log("Error: Failed to verify transaction after $maxRetries attempts.");
        sendTelegram("Failed to check ZiniPay transaction.\n\n" . json_encode($result, JSON_PRETTY_PRINT));
        r2(U . "order/view/" . $trx['id'], 'e', Lang::T("Failed to verify transaction. Please try again later or contact Admin."));
        exit;
    }

    $status = $result['status'] ?? '';
    $txid = $result['transaction_id'] ?? $reference;

    if ($status == 'COMPLETED') {
        zinipay_gw_debug_log("Verification success. Status is COMPLETED. Recharging user...");
        $invoice = Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'], 'ZiniPay');
        if (!$invoice) {
            zinipay_gw_debug_log("Error: Package rechargeUser failed.");
            r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Failed to activate your Package, try again later."));
        }
        $trx->trx_invoice      = $invoice;
        $trx->pg_paid_response = json_encode($result);
        $trx->payment_method   = 'ZiniPay';
        $trx->payment_channel  = 'ZiniPay - ' . ($result['payment_method'] ?? 'MFS');
        $trx->paid_date        = date('Y-m-d H:i:s');
        $trx->status           = 2;
        $trx->save();

        zinipay_gw_debug_log("Recharge successful. Redirecting user to order details.");
        r2(U . "order/view/" . $trx['id'], 's', Lang::T("Transaction has been paid."));
    } elseif (in_array($status, ['FAILED', 'EXPIRED', 'CANCELLED'])) {
        zinipay_gw_debug_log("Verification failed. Status is $status. Marking transaction as failed.");
        $trx->pg_paid_response = json_encode($result);
        $trx->status           = 3;
        $trx->save();
        r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Transaction expired/failed."));
    } else {
        zinipay_gw_debug_log("Transaction is still pending/unpaid.");
        r2(U . "order/view/" . $trx['id'], 'w', Lang::T("Transaction still unpaid."));
    }
}

function zinipay_payment_notification()
{
    // Webhook callback integration if needed
    header("Content-Type: application/json");
    die(json_encode(['status' => 'ok']));
}

function zinipay_gw_get_server()
{
    return 'https://api.zinipay.com/v1/';
}

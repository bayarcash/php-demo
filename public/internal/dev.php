<?php

/**
 * Internal test form for the dev API.
 *
 * The form the team uses to try features against the dev environment. Talks to
 * the API directly rather than through the SDK, so endpoints the SDK does not
 * model yet can still be exercised.
 *
 * For the sandbox/production API through the SDK, see public/api-console.php.
 */

require_once __DIR__ . '/guard.php';

$devCredentials = $config->credentials('dev');

// The rest of this file was written against a flat config shape, so map the
// new one onto it rather than rewriting working code.
$current_config = [
    'bayarcash_portal_key'     => $devCredentials['portal_key'] ?? '',
    'bayarcash_bearer_token'   => $devCredentials['bearer_token'] ?? '',
    'bayarcash_api_secret_key' => $devCredentials['secret_key'] ?? '',
];

if (!empty($_POST['override_bearer_token'])) {
    $current_config['bayarcash_bearer_token'] = $_POST['override_bearer_token'];
}

if (!empty($_POST['override_portal_key'])) {
    $current_config['bayarcash_portal_key'] = $_POST['override_portal_key'];
}

// Endpoints come from the configured base_uri, so the dev host is no longer
// hardcoded here. base_uri looks like https://<host>/api/v2/
$devBase  = rtrim($devCredentials['base_uri'] ?? '', '/');
$devHost  = preg_replace('#/api(/v\d+)?$#', '', $devBase);

$api_endpoint             = $devBase . '/payment-intents';
$mandates_endpoint        = $devBase . '/mandates';
$portals_endpoint         = $devBase . '/portals';
$manual_transfer_endpoint = $devHost . '/api/manual-bank-transfer';
$api_version              = 'v2';

// The body and view below expect $config to be the old flat array, so swap it
// after reading what they need from the Config object. 'environment' gates the
// API Configuration panel and the Override modal in _form.php.
$devReturnUrl = $config->url('internal/return_dev.php');
$config = [
    'return_url'  => $devReturnUrl,
    'environment' => 'dev',
];

$payment_gateways = [
    1 => 'FPX Online Banking (CASA)',
    2 => 'Manual Bank Transfer',
    4 => 'FPX Line of Credit (Credit Card)',
    5 => 'DuitNow Online Banking/Wallets',
    6 => 'DuitNow QR',
    7 => 'SPayLater (BNPL from Shopee)',
    8 => 'Boost PayFlex (BNPL from Boost)',
    9 => 'QRIS Indonesia Online Banking',
    10 => 'QRIS Indonesia eWallet',
    11 => 'NETS Singapore',
    12 => 'Credit Card',
    13 => 'Alipay',
    14 => 'WeChat Pay',
    15 => 'PromptPay',
    16 => 'Touch n Go',
    17 => 'Boost Wallet',
    18 => 'GrabPay',
    19 => 'GrabPL',
    20 => 'ShopBack',
    21 => 'Shopee Pay',
    22 => 'JCB'
];

$emandate_option = ['emandate' => 'eMandate Enrollment (Direct Debit)'];

$merchant_banks = [
    'Affin Bank Berhad' => ['account' => '11212005986', 'holder' => 'Web Impian Sdn. Bhd.'],
    'Maybank' => ['account' => '114161439608', 'holder' => 'Web Impian Sdn. Bhd.'],
    'CIMB Bank' => ['account' => '8600123456', 'holder' => 'Web Impian Sdn. Bhd.']
];

$error_message = '';
$errors = [];
$merchant_info = null;

function logRawResponse($response, $request_data = null, $endpoint = null, $status_code = null) {
    $log_dir = dirname(__DIR__, 2) . '/storage/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'status_code' => $status_code,
        'request_data' => $request_data,
        'response' => $response
    ];

    file_put_contents($log_dir . '/payment_intent_raw.log', json_encode($log_entry, JSON_PRETTY_PRINT) . "\n" . str_repeat('-', 80) . "\n", FILE_APPEND);
}


function generateRandomName(): string {
    $firstNames = ['Ahmad', 'Mohd', 'Mohammed', 'Muhammad', 'Nur', 'Nurul', 'Siti', 'Amir', 'Aina', 'Azizah', 'Fatimah', 'Ismail', 'Zulkifli', 'Noraini', 'Hashim', 'Zainab'];
    $lastNames = ['Abdullah', 'Rahman', 'Hassan', 'Ibrahim', 'Othman', 'Ismail', 'Yusof', 'Ahmad', 'Ali', 'Aziz', 'Hamzah', 'Zainal', 'Razak', 'Kadir', 'Mahmud', 'Mustafa'];

    return $firstNames[array_rand($firstNames)] . ' bin ' . $lastNames[array_rand($lastNames)];
}

function generateRandomEmail($name, $maxLength = null): string {
    $domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'example.com'];
    $domain = $domains[array_rand($domains)];
    $username = implode('.', explode(' ', strtolower($name)));

    if ($maxLength !== null) {
        $domainLength = strlen($domain) + 1;
        $availableUsernameLength = $maxLength - $domainLength;

        if (strlen($username) > $availableUsernameLength - 3) {
            $username = substr($username, 0, $availableUsernameLength - 3);
        }

        $randomNumber = mt_rand(10, 99);
        $email = $username . $randomNumber . '@' . $domain;

        if (strlen($email) > $maxLength) {
            $username = substr($username, 0, $maxLength - strlen($randomNumber . '@' . $domain));
            $email = $username . $randomNumber . '@' . $domain;
        }

        return $email;
    }

    return $username . mt_rand(1, 999) . '@' . $domain;
}

function generateRandomMyKadNumber(): string {
    $year = mt_rand(70, 99);
    $month = str_pad(mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
    $day = str_pad(mt_rand(1, 28), 2, '0', STR_PAD_LEFT);
    $placeCodes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16'];
    $placeCode = $placeCodes[array_rand($placeCodes)];
    $sequential = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    $gender = mt_rand(0, 9);

    return $year . $month . $day . $placeCode . $sequential . $gender;
}

function generateDefaultOrderData(): array {
    $buyer_name = generateRandomName();

    return [
        'order_no' => 'DEV' . str_pad(mt_rand(1, 9999), 6, '0', STR_PAD_RIGHT),
        'order_amount' => '100.00',
        'buyer_name' => $buyer_name,
        'buyer_email' => generateRandomEmail($buyer_name),
        'buyer_tel' => '0196788044',
        'order_description' => 'Development Test Order',
        'payer_id_type' => 1,
        'payer_id' => generateRandomMyKadNumber(),
        'frequency_mode' => 'MT',
    ];
}

function generateRandomProofOfPayment(): string {
    $uploads_dir = dirname(__DIR__, 2) . '/storage/uploads';
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
    }

    $filename = 'proof_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.png';
    $filepath = $uploads_dir . '/' . $filename;

    $image = imagecreate(400, 200);
    imagecolorallocate($image, 255, 255, 255);
    $textColor = imagecolorallocate($image, 0, 0, 0);
    imagestring($image, 5, 50, 90, 'Proof of Payment - ' . date('Y-m-d H:i:s'), $textColor);
    imagepng($image, $filepath);
    imagedestroy($image);

    return $filepath;
}

function makeApiRequest($url, $data = null, $custom_bearer_token = null): array {
    global $current_config;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    $bearer_token = $custom_bearer_token ?? $current_config['bayarcash_bearer_token'];

    if ($bearer_token) {
        $headers[] = 'Authorization: Bearer ' . $bearer_token;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $decoded_response = json_decode($response, true);
    logRawResponse($decoded_response, $data, $url, $http_code);


    return [
        'status_code' => $http_code,
        'response' => $decoded_response,
        'error' => $curl_error,
        'raw_response' => $response
    ];
}

function fetchMerchantInfo($custom_bearer_token = null): array {
    global $portals_endpoint;

    $portals_result = makeApiRequest($portals_endpoint, null, $custom_bearer_token);

    if ($portals_result['status_code'] == 200 && isset($portals_result['response']['meta']['merchant'])) {
        return ['success' => true, 'merchant' => $portals_result['response']['meta']['merchant']];
    }

    $error_msg = 'Unable to fetch merchant information';
    if (isset($portals_result['response']['message'])) {
        $error_msg = $portals_result['response']['message'];
    } elseif (!empty($portals_result['error'])) {
        $error_msg = 'API Communication Error: ' . $portals_result['error'];
    } else {
        $error_msg .= ' (Status code: ' . $portals_result['status_code'] . ')';
    }

    return ['success' => false, 'message' => $error_msg];
}

function validateAmount($amount): bool {
    return !empty($amount) && is_numeric($amount) && (float)$amount > 0;
}

function validateEmailForEMandate($email, $buyer_name, $order_no): string {
    if (strlen($email) > 27) {
        $new_email = generateRandomEmail($buyer_name, 27);
        return $new_email;
    }
    return $email;
}

function buildEMandateRequest($form_data): array {
    global $current_config, $config;

    $defaults = generateDefaultOrderData();
    $order_data = array_merge($defaults, $form_data);
    $order_data['application_reason'] = $form_data['application_reason'] ?? ('Enrollment for ' . $order_data['order_no']);
    $order_data['buyer_email'] = validateEmailForEMandate($order_data['buyer_email'], $order_data['buyer_name'], $order_data['order_no']);

    return [
        'portal_key' => $current_config['bayarcash_portal_key'],
        'order_number' => $order_data['order_no'],
        'amount' => (float)$order_data['order_amount'],
        'payer_id_type' => (int)$order_data['payer_id_type'],
        'payer_id' => $order_data['payer_id'],
        'payer_name' => $order_data['buyer_name'],
        'payer_email' => $order_data['buyer_email'],
        'payer_telephone_number' => $order_data['buyer_tel'],
        'frequency_mode' => $order_data['frequency_mode'],
        'application_reason' => $order_data['application_reason'],
        'return_url' => $config['return_url'],
        'success_url' => $config['return_url'],
        'failed_url' => $config['return_url']
    ];
}

function buildPaymentIntentRequest($form_data): array {
    global $current_config, $config;

    $defaults = generateDefaultOrderData();
    $order_data = array_merge($defaults, $form_data);

    $request = [
        'portal_key' => $current_config['bayarcash_portal_key'],
        'order_number' => $order_data['order_no'],
        'amount' => (float)$order_data['order_amount'],
        'payer_name' => $order_data['buyer_name'],
        'payer_email' => $order_data['buyer_email'],
        'payer_telephone_number' => $order_data['buyer_tel'],
        'return_url' => $config['return_url']
    ];

    // Handle payment_channel modes:
    // - 'all' or empty: omit payment_channel (let API show all available)
    // - comma-separated: send as array (multi-channel selection)
    // - single number: send as integer (direct to specific channel)
    $payment_method = $form_data['payment_method'] ?? '';

    if ($payment_method !== '' && $payment_method !== 'all') {
        if (strpos($payment_method, ',') !== false) {
            // Multi-channel: convert "1,5,6" to [1, 5, 6]
            $channels = array_map('intval', array_filter(explode(',', $payment_method)));
            if (!empty($channels)) {
                $request['payment_channel'] = $channels;
            }
        } else {
            // Single channel
            $request['payment_channel'] = (int)$payment_method;
        }
    }
    // If 'all' or empty, payment_channel is omitted entirely

    // Add splits if provided
    if (!empty($form_data['splits'])) {
        $splits = json_decode($form_data['splits'], true);
        if (is_array($splits) && count($splits) > 0 && count($splits) <= 6) {
            $request['splits'] = $splits;
        }
    }

    return $request;
}

function processEMandateEnrollment($form_data): array {
    global $mandates_endpoint;

    $request_data = buildEMandateRequest($form_data);

    return makeApiRequest($mandates_endpoint, $request_data);
}

function processPaymentIntent($form_data): array {
    global $api_endpoint;

    $request_data = buildPaymentIntentRequest($form_data);

    return makeApiRequest($api_endpoint, $request_data);
}

function processManualBankTransfer($form_data): array {
    global $manual_transfer_endpoint, $current_config, $config, $merchant_banks;

    $bank_name = array_key_first($merchant_banks);
    $bank_info = $merchant_banks[$bank_name];
    $defaults = generateDefaultOrderData();
    $order_data = array_merge($defaults, $form_data);
    $transfer_date = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $proof_file = generateRandomProofOfPayment();

    $raw_website = [
        'currency' => 'MYR',
        'total_discount' => '0.00',
        'total_tax' => '0.00',
        'total' => $order_data['order_amount'],
        'items' => [[
            'description' => $order_data['order_description'] ?? 'Development Test Order',
            'quantity' => 1,
            'total' => $order_data['order_amount']
        ]]
    ];

    $request_data = [
        'portal_key' => $current_config['bayarcash_portal_key'],
        'buyer_name' => $order_data['buyer_name'],
        'buyer_email' => $order_data['buyer_email'],
        'buyer_tel_no' => $order_data['buyer_tel'],
        'order_amount' => $order_data['order_amount'],
        'order_no' => $order_data['order_no'],
        'return_url' => $config['return_url'],
        'payment_gateway' => 2,
        'merchant_bank_name' => $bank_name,
        'merchant_bank_account' => $bank_info['account'],
        'merchant_bank_account_holder' => $bank_info['holder'],
        'bank_transfer_type' => 'Online Transfer',
        'bank_transfer_date' => $transfer_date,
        'bank_transfer_notes' => 'Payment for order ' . $order_data['order_no'],
        'raw_website' => json_encode($raw_website)
    ];

    $ch = curl_init($manual_transfer_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);

    $post_fields = $request_data;
    if (file_exists($proof_file)) {
        $post_fields['proof_of_payment'] = new CURLFile($proof_file, 'image/png', basename($proof_file));
    }

    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $current_config['bayarcash_bearer_token']
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    logRawResponse($response, $request_data, $manual_transfer_endpoint, $http_code);

    $decoded_response = strpos($response, '<form') !== false
        ? ['html_form' => $response, 'is_html' => true]
        : json_decode($response, true);

    if (file_exists($proof_file)) {
        unlink($proof_file);
    }

    return [
        'status_code' => $http_code,
        'response' => $decoded_response,
        'error' => $curl_error,
        'raw_response' => $response
    ];
}

function parseApiErrors($api_result): array {
    $errors = [];

    if (isset($api_result['response']['errors']) && is_array($api_result['response']['errors'])) {
        foreach ($api_result['response']['errors'] as $field => $error_msgs) {
            if (is_array($error_msgs)) {
                foreach ($error_msgs as $msg) {
                    $errors[] = "$field: " . (is_string($msg) ? $msg : json_encode($msg));
                }
            } else {
                $errors[] = "$field: $error_msgs";
            }
        }
    } elseif (isset($api_result['response']['error'])) {
        if (is_array($api_result['response']['error'])) {
            $first_key = array_key_first($api_result['response']['error']);
            $is_field_based = !is_numeric($first_key) && is_array($api_result['response']['error'][$first_key]);

            if ($is_field_based) {
                foreach ($api_result['response']['error'] as $field => $error_msgs) {
                    if (is_array($error_msgs)) {
                        foreach ($error_msgs as $msg) {
                            $errors[] = "$field: " . (is_string($msg) ? $msg : json_encode($msg));
                        }
                    } else {
                        $errors[] = "$field: $error_msgs";
                    }
                }
            } else {
                foreach ($api_result['response']['error'] as $key => $error) {
                    if (is_array($error)) {
                        $errors[] = is_numeric($key) ? json_encode($error) : "$key: " . json_encode($error);
                    } else {
                        $errors[] = is_numeric($key) ? (string)$error : "$key: $error";
                    }
                }
            }
        } else {
            $errors[] = (string)$api_result['response']['error'];
        }
    }

    return $errors;
}

function generateErrorMessage($api_result): string {
    if (isset($api_result['response']['message'])) {
        return is_string($api_result['response']['message'])
            ? $api_result['response']['message']
            : json_encode($api_result['response']['message']);
    }

    if (isset($api_result['response']['error'])) {
        if (is_array($api_result['response']['error'])) {
            $first_key = array_key_first($api_result['response']['error']);
            $is_field_based = !is_numeric($first_key) && is_array($api_result['response']['error'][$first_key]);

            if ($is_field_based) {
                $all_messages = [];
                foreach ($api_result['response']['error'] as $field => $messages) {
                    if (is_array($messages)) {
                        foreach ($messages as $msg) {
                            $all_messages[] = is_string($msg) ? $msg : json_encode($msg);
                        }
                    } else {
                        $all_messages[] = (string)$messages;
                    }
                }
                return implode(' ', $all_messages);
            }

            $errors = [];
            foreach ($api_result['response']['error'] as $error) {
                if (is_array($error)) {
                    foreach ($error as $msg) {
                        $errors[] = is_string($msg) ? $msg : json_encode($msg);
                    }
                } else {
                    $errors[] = (string)$error;
                }
            }
            return implode(' ', $errors);
        }

        return (string)$api_result['response']['error'];
    }

    if (!empty($api_result['error'])) {
        return 'API Communication Error: ' . $api_result['error'];
    }

    return 'Unknown error occurred. Status code: ' . $api_result['status_code'];
}


// AJAX: Fetch merchant info
if (isset($_POST['action']) && $_POST['action'] === 'fetch_merchant_info') {
    header('Content-Type: application/json');
    $custom_bearer_token = !empty($_POST['bearer_token']) ? $_POST['bearer_token'] : null;
    echo json_encode(fetchMerchantInfo($custom_bearer_token));
    exit();
}

// Generate default order data
$default_data = generateDefaultOrderData();
$order_no = $default_data['order_no'];
$order_amount = $default_data['order_amount'];
$buyer_name = $default_data['buyer_name'];
$buyer_email = $default_data['buyer_email'];
$buyer_tel = $default_data['buyer_tel'];
$order_description = $default_data['order_description'];
$payer_id_type = $default_data['payer_id_type'];
$payer_id = $default_data['payer_id'];
$frequency_mode = $default_data['frequency_mode'];
$application_reason = 'Enrollment for ' . $order_no;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $payment_method = $_POST['payment_method'] ?? '';
    $order_amount = $_POST['order_amount'] ?? $order_amount;
    $buyer_name = $_POST['buyer_name'] ?? $buyer_name;
    $buyer_email = $_POST['buyer_email'] ?? $buyer_email;
    $buyer_tel = $_POST['buyer_tel'] ?? $buyer_tel;
    $order_description = $_POST['order_description'] ?? $order_description;

    if (!validateAmount($order_amount)) {
        $error_message = 'Please enter a valid amount greater than 0';
    } else {
        try {
            if ($payment_method === 'emandate') {
                $api_result = processEMandateEnrollment($_POST);
                $expected_status_code = 201;
            } elseif ($payment_method == 2) {
                $api_result = processManualBankTransfer($_POST);
                $expected_status_code = 200;

                if ($api_result['status_code'] == $expected_status_code && isset($api_result['response']['is_html']) && $api_result['response']['is_html']) {
                    echo $api_result['response']['html_form'];
                    exit();
                }
            } else {
                $api_result = processPaymentIntent($_POST);
                $expected_status_code = 201;
            }

            if ($api_result['status_code'] == $expected_status_code && isset($api_result['response']['url'])) {
                header("Location: " . $api_result['response']['url']);
                exit();
            }

            $error_message = generateErrorMessage($api_result);
            $errors = parseApiErrors($api_result);
        } catch (Exception $exception) {
            $error_message = 'An unexpected error occurred: ' . $exception->getMessage();
            $errors = [];
        }
    }
}


include __DIR__ . '/_form.php';

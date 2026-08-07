<?php

/**
 * Where dev payments land after checkout.
 *
 * Read-only for payment status -- callback_dev.php is what verifies callbacks.
 * The one thing this page does write is a manual bank transfer status, which
 * is a deliberate human decision rather than something the gateway reports.
 */

require_once __DIR__ . '/guard.php';

use BayarcashDemo\Log;
use BayarcashDemo\PaymentStatus;

$devCredentials = $config->credentials('dev');
$bearerToken    = $devCredentials['bearer_token'] ?? '';

$devBase = rtrim($devCredentials['base_uri'] ?? '', '/');
$devHost = preg_replace('#/api(/v\d+)?$#', '', $devBase);
$manual_transfer_status_endpoint = $devHost . '/api/manual-bank-transfer/update-status';

$baseUrl = $config->baseUrl();

$response      = ['status' => 'error', 'message' => 'Nothing received yet'];
$callbackData  = [];
$displayData   = [];
$updateMessage = '';
$updateSuccess = false;

function updateManualTransferStatus($ref_no, $status, $amount, $bearer_token, $endpoint) {
    $data = [
        'ref_no' => $ref_no,
        'status' => $status,
        'amount' => $amount,
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $bearer_token,
        'Content-Type: application/x-www-form-urlencoded',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    Log::write('[dev] Manual transfer status update: ' . $response);

    if (!empty($curl_error)) {
        return ['success' => false, 'error' => $curl_error];
    }

    $decoded = json_decode($response, true);

    if ($http_code >= 200 && $http_code < 300) {
        return ['success' => true, 'response' => $decoded];
    }

    return ['success' => false, 'response' => $decoded, 'http_code' => $http_code];
}

function getManualTransferStatusOptions(): array {
    return [
        (string) PaymentStatus::UNSUCCESSFUL => 'Failed',
        (string) PaymentStatus::SUCCESSFUL   => 'Success',
        (string) PaymentStatus::CANCELLED    => 'Cancelled',
        (string) PaymentStatus::EXPIRED      => 'Expired',
    ];
}

session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_manual_transfer'])) {
        $callbackData = $_SESSION['dev_callback_data'] ?? [];
        $displayData  = $callbackData;

        $result = updateManualTransferStatus(
            $_POST['ref_no'] ?? '',
            $_POST['status'] ?? '',
            $_POST['amount'] ?? '',
            $bearerToken,
            $manual_transfer_status_endpoint
        );

        $updateSuccess = $result['success'];
        $updateMessage = $updateSuccess
            ? 'Status updated successfully'
            : ($result['error'] ?? json_encode($result['response'] ?? [], JSON_PRETTY_PRINT));

        $displayData = $result['response'] ?? $displayData;
    } elseif (!empty($_POST)) {
        $callbackData = $_POST;
        $displayData  = $_POST;
        $_SESSION['dev_callback_data'] = $callbackData;
        $response = ['status' => 'success', 'message' => 'Data received'];
    } elseif (!empty($_GET)) {
        $callbackData = $_GET;
        $displayData  = $_GET;
        $_SESSION['dev_callback_data'] = $callbackData;
        $response = ['status' => 'success', 'message' => 'Data received'];
    }
} catch (Throwable $e) {
    $response = ['status' => 'error', 'message' => 'EXCEPTION: ' . $e->getMessage()];
}

$statusCode = $callbackData['transaction_status'] ?? $callbackData['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bayarcash Dev - Payment Response</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/desktop.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/v4-shims.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
<div id="container" class="container col-4 mt-3 mb-4 container-width">

    <div class="mb-3">
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> <strong>Dev Environment</strong> - Direct API calls (no SDK)
        </div>
        <div>
            <a target="_blank" href="https://github.com/bayarcash/php-demo">
                Reference from GitHub repo &raquo;
            </a>
        </div>
        <div class="mt-1">
            <a target="_blank" href="https://api.webimpian.support/bayarcash">
                Bayarcash API documentation &raquo;
            </a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header">
            <i class="fa fa-code"></i> Payment Response (Dev)
        </div>
        <div class="card-body">

            <?php if (!empty($updateMessage)): ?>
                <div class="alert <?php echo $updateSuccess ? 'alert-success' : 'alert-danger'; ?>">
                    <strong><?php echo $updateSuccess ? 'Success:' : 'Error:'; ?></strong>
                    <?php echo htmlspecialchars((string) $updateMessage); ?>
                </div>
            <?php else: ?>
                <div class="alert alert-<?php echo ($response['status'] === 'success') ? 'success' : 'danger'; ?>">
                    <strong>System Status:</strong>
                    <?php echo ($response['status'] === 'success') ? 'Data received successfully' : htmlspecialchars($response['message']); ?>
                </div>
            <?php endif; ?>

            <?php if ($statusCode !== ''): ?>
                <h5 class="font-weight-bold mb-3">Payment Status</h5>
                <div class="alert <?php echo PaymentStatus::isPaid($statusCode) ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo htmlspecialchars(PaymentStatus::label($statusCode)); ?>
                </div>
            <?php endif; ?>

            <?php if (($callbackData['transaction_channel'] ?? '') === 'ManualBankTransfer' && isset($callbackData['order_ref_no'])): ?>
                <hr class="mt-4 mb-4">

                <div class="manual-transfer-update">
                    <h5 class="font-weight-bold mb-3">
                        Update Manual Transfer Status
                        <span class="badge badge-info ml-2">Direct API</span>
                    </h5>

                    <form method="post" action="">
                        <input type="hidden" name="update_manual_transfer" value="1">
                        <input type="hidden" name="ref_no" value="<?php echo htmlspecialchars($callbackData['order_ref_no']); ?>">
                        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($callbackData['order_amount'] ?? ''); ?>">

                        <div class="form-group">
                            <label for="status">New Status:</label>
                            <select class="form-control" id="status" name="status">
                                <?php foreach (getManualTransferStatusOptions() as $code => $description): ?>
                                    <option value="<?php echo $code; ?>"
                                        <?php echo ((string) $statusCode === (string) $code) ? 'selected' : ''; ?>>
                                        <?php echo $description; ?> (<?php echo $code; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-sync"></i> Update Status
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <hr class="mt-4 mb-4">

            <div>
                <h5 class="font-weight-bold mb-3">
                    <?php echo !empty($updateMessage) ? 'Update Response' : 'Callback Data'; ?>
                </h5>
                <pre><?php echo htmlspecialchars(json_encode($displayData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}'); ?></pre>
            </div>

            <?php if (isset($displayData['exchange_reference_number'])): ?>
                <p><strong>Exchange Reference Number:</strong>
                    <?php echo htmlspecialchars($displayData['exchange_reference_number']); ?></p>
                <p>Please save this exchange reference number for future reference.</p>
            <?php endif; ?>

            <hr class="mt-4 mb-4">

            <div class="navigation-buttons">
                <h5 class="font-weight-bold mb-3">Navigation</h5>
                <div class="d-flex flex-wrap justify-content-between">
                    <a href="<?php echo $baseUrl; ?>index.php" class="btn btn-primary mb-2 mr-2">
                        <i class="fa fa-home"></i> Main Page (SDK)
                    </a>
                    <a href="<?php echo $baseUrl; ?>internal/dev.php" class="btn btn-info mb-2 mr-2">
                        <i class="fa fa-code"></i> Dev Testing
                    </a>
                    <a href="<?php echo $baseUrl; ?>api-console.php" class="btn btn-warning mb-2">
                        <i class="fa fa-terminal"></i> API Console
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script type="text/javascript" src="../assets/js/jquery-3.2.0.min.js"></script>
<script type="text/javascript" src="../assets/js/bootstrap.min.js"></script>
</body>
</html>

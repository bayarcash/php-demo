<?php

declare(strict_types=1);

/**
 * API CONSOLE -- call the API through the SDK and read the raw response.
 *
 * The operations below mirror the SDK's own methods, so what you send here is
 * what your code would send. Each maps to a verb and a path:
 *
 *     $bayarcash->getPortals();                      GET  portals
 *     $bayarcash->createPaymentIntent($data);        POST payment-intents
 *     $bayarcash->cancelPaymentIntent($id);          DELETE payment-intents/{id}
 *
 * Those helpers decode the JSON and throw typed exceptions. This page drives
 * the client underneath them, $bayarcash->guzzle, so it can show the exact
 * status code and unparsed body -- including for errors, which the typed
 * exceptions would otherwise reduce to a message.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/_layout.php';

use Bayarcash\Bayarcash;
use BayarcashDemo\BayarcashFactory;

$bayarcash   = BayarcashFactory::make($config);
$credentials = $config->credentials();
$portalKey   = $credentials['portal_key'] ?? '';

$samplePayment = json_encode([
    'portal_key'             => $portalKey,
    'order_number'           => 'CONSOLE' . date('ymdHis'),
    'amount'                 => '10.00',
    'description'            => 'API console test',
    'payer_name'             => 'John Doe',
    'payer_email'            => 'john.doe@example.com',
    'payer_telephone_number' => '60123456789',
], JSON_PRETTY_PRINT);

// createDuitNowQrPaymentIntent() sets generate_qr itself; it is spelled out here
// because this page posts the body as typed rather than calling the method.
$sampleDuitNowQr = json_encode([
    'portal_key'             => $portalKey,
    'order_number'           => 'QR' . date('ymdHis'),
    'amount'                 => '10.00',
    'description'            => 'DuitNow QR console test',
    'payer_name'             => 'John Doe',
    'payer_email'            => 'john.doe@example.com',
    'payer_telephone_number' => '60123456789',
    'payment_channel'        => Bayarcash::DUITNOW_QR,
    'generate_qr'            => true,
], JSON_PRETTY_PRINT);

/**
 * The SDK's operations. Each names its own parameter, because they are not
 * all ids -- some take a status, an email, a channel. `body` marks the ones
 * sending a payload. The form shows only the inputs an operation uses.
 */
$operations = [
    'portals' => ['GET', 'portals', 'getPortals()'],
    'banks'   => ['GET', 'banks',   'fpxBanksList()'],

    'portal' => ['GET', 'portals/{p}', 'getPortal($portalId)', 'v3' => true,
        'param' => 'Portal ID', 'hint' => 'an id from getPortals()'],

    'dobw-banks' => ['GET', 'duitnow/dobw/banks', 'duitNowDobwBanksList()', 'v3' => true],

    'server-status' => ['GET', 'up', 'getServerStatus()', 'v3' => true],

    'transactions' => ['GET', 'transactions', 'getAllTransactions()'],

    'transaction' => ['GET', 'transactions/{p}', 'getTransaction($id)',
        'param' => 'Transaction ID', 'hint' => 'trx_…'],

    'by-order' => ['GET', 'transactions?order_number={p}', 'getTransactionByOrderNumber($orderNumber)',
        'param' => 'Order number', 'hint' => 'DEMO260807…'],

    'by-status' => ['GET', 'transactions?status={p}', 'getTransactionsByStatus($status)',
        'param' => 'Status code', 'hint' => '3 for Successful'],

    'by-email' => ['GET', 'transactions?payer_email={p}', 'getTransactionsByPayerEmail($email)',
        'param' => 'Payer email', 'hint' => 'john.doe@example.com'],

    'by-channel' => ['GET', 'transactions?payment_channel={p}', 'getTransactionsByPaymentChannel($channel)',
        'param' => 'Channel ID', 'hint' => '1 for FPX'],

    'by-reference' => ['GET', 'transactions?exchange_reference_number={p}', 'getTransactionByReferenceNumber($ref)',
        'param' => 'Exchange reference', 'hint' => '2026080712345678'],

    'payment-intent' => ['GET', 'payment-intents/{p}', 'getPaymentIntent($id)',
        'param' => 'Payment intent ID', 'hint' => 'pi_…'],

    'create-payment' => ['POST', 'payment-intents', 'createPaymentIntent($data)', 'body' => true],

    'create-duitnow-qr' => ['POST', 'payment-intents', 'createDuitNowQrPaymentIntent($data)',
        'body' => true, 'v3' => true, 'sample' => 'duitnow_qr'],

    'regenerate-qr' => ['POST', 'payment-intents/{p}/duitnow-qr', 'regenerateDuitNowQr($id)',
        'v3' => true, 'param' => 'Payment intent ID', 'hint' => 'pi_…'],

    'qr-status' => ['GET', 'transactions/{p}/duitnow-qr/status', 'getDuitNowQrStatus($transactionId)',
        'v3' => true, 'param' => 'Transaction ID', 'hint' => 'trx_…'],

    'cancel-payment' => ['DELETE', 'payment-intents/{p}', 'cancelPaymentIntent($id)',
        'param' => 'Payment intent ID', 'hint' => 'pi_…'],

    'mandates' => ['GET', 'mandates', 'getAllFpxDirectDebits()', 'v3' => true],

    'mandate-txns' => ['GET', 'mandates/transactions', 'getAllFpxDirectDebitTransactions()', 'v3' => true],

    'mandate' => ['GET', 'mandates/{p}', 'getFpxDirectDebit($id)',
        'param' => 'Mandate ID', 'hint' => 'mdt_…'],

    'mandate-txn' => ['GET', 'mandates/transactions/{p}', 'getFpxDirectDebitTransaction($id)',
        'param' => 'Mandate transaction ID', 'hint' => 'mdt_trx_…'],

    'create-mandate' => ['POST', 'mandates', 'createFpxDirectDebitEnrollment($data)', 'body' => true],

    'update-mandate' => ['PUT', 'mandates/{p}', 'createFpxDirectDebitMaintenance($id, $data)',
        'param' => 'Mandate ID', 'hint' => 'mdt_…', 'body' => true],

    'terminate-mandate' => ['DELETE', 'mandates/{p}', 'createFpxDirectDebitTermination($id, $data)',
        'param' => 'Mandate ID', 'hint' => 'mdt_…', 'body' => true],

    'activate-mandate' => ['PATCH', 'mandates/{p}/activate', 'activateFpxDirectDebit($id)',
        'v3' => true, 'param' => 'Mandate ID', 'hint' => 'mdt_…'],

    'deactivate-mandate' => ['PATCH', 'mandates/{p}/deactivate', 'deactivateFpxDirectDebit($id)',
        'v3' => true, 'param' => 'Mandate ID', 'hint' => 'mdt_…'],
];

// Sample bodies by name, so an operation can ask for the one that fits it.
$samples = [
    'default'    => $samplePayment,
    'duitnow_qr' => $sampleDuitNowQr,
];

// GET prefills let other pages deep-link into a specific lookup.
$selected = $_POST['operation'] ?? $_GET['operation'] ?? 'portals';
$selected = isset($operations[$selected]) ? $selected : 'portals';

$resourceId = trim((string) ($_POST['resource_id'] ?? $_GET['id'] ?? ''));
$payload    = $_POST['payload'] ?? '';

$status   = null;
$rawBody  = '';
$error    = '';
$sentBody = '';
$sentLine = '';
$elapsed  = 0.0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $op           = $operations[$selected];
    $verb         = $op[0];
    $pathTemplate = $op[1];
    $paramLabel   = $op['param'] ?? null;

    try {
        if (! empty($op['v3']) && $config->apiVersion() !== 'v3') {
            throw new RuntimeException(
                $op[2] . ' is only available on API v3. Set api_version to v3 in config.php.'
            );
        }

        if ($paramLabel !== null && $resourceId === '') {
            throw new RuntimeException($paramLabel . ' is required for this operation.');
        }

        $path    = str_replace('{p}', rawurlencode($resourceId), $pathTemplate);
        $options = [];

        if (! empty($op['body'])) {
            $decoded = json_decode($payload ?: '{}', true);

            if (! is_array($decoded)) {
                throw new RuntimeException('Body is not valid JSON.');
            }

            $options['json'] = $decoded;
            $sentBody = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        $startedAt = microtime(true);
        $response  = $bayarcash->guzzle->request($verb, $path, $options);
        $elapsed   = (microtime(true) - $startedAt) * 1000;

        $status   = $response->getStatusCode();
        $rawBody  = (string) $response->getBody();
        $sentLine = $verb . ' ' . $path;
    } catch (Throwable $e) {
        $error = get_class($e) . ': ' . $e->getMessage();
    }
}

layout_head('API console', $config, 'api-console.php');
?>

<div class="notice">
    Requests go through the SDK, so authentication and errors are handled for you.
    Each operation below is a real SDK method on the
    <strong><?= e($config->environment()) ?></strong> API.
</div>

<form method="POST" class="panel">
    <div class="panel-head">
        <span>Request</span>
        <?php env_badge($config); ?>
    </div>
    <div class="panel-body">

        <div class="field">
            <label for="operation">Operation</label>
            <select class="input" id="operation" name="operation">
                <?php foreach ($operations as $key => $op): ?>
                    <option value="<?= e($key) ?>"
                            data-call="<?= e($op[0] . ' ' . $op[1]) ?>"
                            data-param="<?= e($op['param'] ?? '') ?>"
                            data-hint="<?= e($op['hint'] ?? '') ?>"
                            data-body="<?= empty($op['body']) ? '' : '1' ?>"
                            data-sample="<?= e($samples[$op['sample'] ?? 'default']) ?>"
                        <?= $selected === $key ? 'selected' : '' ?>
                        <?= ! empty($op['v3']) && $config->apiVersion() !== 'v3' ? 'disabled' : '' ?>>
                        <?= e($op[2]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="endpoint">Endpoint</label>
            <input class="input mono" id="endpoint" value="" readonly tabindex="-1"
                   aria-label="Resolved endpoint for the selected operation">
        </div>

        <div class="field" id="field-param" hidden>
            <label for="resource_id" id="param-label">Parameter</label>
            <input class="input mono" id="resource_id" name="resource_id"
                   value="<?= e($resourceId) ?>">
        </div>

        <div class="field" id="field-body" hidden>
            <label for="payload">Request body</label>
            <textarea class="input" id="payload" name="payload" rows="9"
                      placeholder="{}"><?= e($payload !== '' ? $payload : $samples[$operations[$selected]['sample'] ?? 'default']) ?></textarea>
        </div>

        <button class="btn btn-primary" type="submit" name="send" value="1">Send →</button>
    </div>
</form>

<?php if ($error !== ''): ?>
    <div class="notice" data-tone="danger"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($status !== null): ?>
    <div class="panel">
        <div class="panel-head">
            <span>Response</span>
            <span class="status" data-tone="<?= $status < 400 ? 'success' : 'danger' ?>">
                HTTP <?= e($status) ?> · <?= e(number_format($elapsed)) ?>ms
            </span>
        </div>
        <div class="panel-body">
            <pre class="code"><?php
                $decoded = json_decode($rawBody, true);
                echo e(
                    is_array($decoded)
                        ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                        : ($rawBody === '' ? '(empty body)' : $rawBody)
                );
            ?></pre>
        </div>
    </div>

    <?php if ($sentBody !== ''): ?>
        <div class="panel">
            <div class="panel-head"><span>Body sent</span><span class="meta"><?= e($sentLine) ?></span></div>
            <div class="panel-body"><pre class="code"><?= e($sentBody) ?></pre></div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
    // Each operation names its own parameter, so the field relabels itself
    // rather than calling everything a "resource ID".
    const op        = document.getElementById('operation');
    const paramWrap = document.getElementById('field-param');
    const paramLbl  = document.getElementById('param-label');
    const paramIn   = document.getElementById('resource_id');
    const bodyWrap  = document.getElementById('field-body');
    const payload   = document.getElementById('payload');
    const endpoint  = document.getElementById('endpoint');

    function syncFields() {
        const opt   = op.selectedOptions[0];
        const param = opt.dataset.param || '';

        paramWrap.hidden = param === '';
        bodyWrap.hidden  = opt.dataset.body !== '1';

        if (param) {
            paramLbl.textContent  = param;
            paramIn.placeholder   = opt.dataset.hint || '';
        }

        // Show the call as it will be sent, with the value filled in as typed.
        const token = param ? '{' + param.toLowerCase().replace(/ /g, '_') + '}' : '';
        endpoint.value = opt.dataset.call.replace('{p}', paramIn.value.trim() || token);
    }

    // Switching operation clears the old value -- a payment intent id is
    // meaningless in a "Channel ID" field. Submitted values still survive
    // the round trip, because this only fires on user input.
    op.addEventListener('change', () => {
        paramIn.value = '';

        // A DuitNow QR body is not an FPX body, so the sample follows the operation.
        const sample = op.selectedOptions[0].dataset.sample || '';
        if (sample) {
            payload.value = sample;
        }

        syncFields();
    });

    paramIn.addEventListener('input', syncFields);
    syncFields();
</script>

<?php layout_foot($config); ?>

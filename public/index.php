<?php

declare(strict_types=1);

/**
 * CHECKOUT -- creating a payment intent and sending the payer to Bayarcash.
 *
 * The flow is always these four steps:
 *
 *   1. Build the request array (portal key, order, amount, payer details).
 *   2. Sign it with createPaymentIntenChecksumValue() and attach the result
 *      as `checksum`. Sign the finished array -- adding or changing a field
 *      afterwards invalidates the signature.
 *   3. createPaymentIntent() returns an object with a `url`.
 *   4. Redirect the payer to that URL. Bayarcash takes over from there and
 *      reports back to your callback_url.
 *
 * Nothing is stored here. The order is not a payment until a callback says so.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/_layout.php';

use Bayarcash\Exceptions\ValidationException;
use BayarcashDemo\BayarcashFactory;
use BayarcashDemo\Log;

$bayarcash   = BayarcashFactory::make($config);
$credentials = $config->credentials();

$errorMessage = '';
$fieldErrors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'portal_key'             => $credentials['portal_key'] ?? '',
        'order_number'           => trim((string) ($_POST['order_number'] ?? '')),
        'amount'                 => trim((string) ($_POST['amount'] ?? '')),
        'description'            => trim((string) ($_POST['description'] ?? '')),
        'payer_name'             => trim((string) ($_POST['payer_name'] ?? '')),
        'payer_email'            => trim((string) ($_POST['payer_email'] ?? '')),
        'payer_telephone_number' => trim((string) ($_POST['payer_telephone_number'] ?? '')),

        // payment_channel is deliberately omitted.
        //
        // On v3 you may leave it out, and Bayarcash then offers the payer every
        // channel the portal has enabled. Set it only to force one -- e.g. a
        // DuitNow QR-only checkout -- or pass an array of ids for a subset.
        //
        // Omitting it means your checkout can never drift out of sync with the
        // channels the portal actually accepts.

        // Two different URLs, and the difference matters:
        //
        //   callback_url  server-to-server. Authoritative. Always fires.
        //   return_url    where the payer's browser lands. May never be reached.
        //
        // Point both at the same script and you can no longer tell them apart.
        // That is how integrations end up trusting a status the browser carried.
        'callback_url'           => $config->url('callback.php'),
        'return_url'             => $config->url('return.php'),
    ];

    try {
        // Sign the completed array:
        //
        //   - the SDK sorts the fields, then HMACs them with your secret key
        //   - Bayarcash recomputes it and rejects the request if they differ
        //   - the secret key never leaves your server, only the checksum does
        //
        // Sign last. Any field changed after this line invalidates it.
        $data['checksum'] = $bayarcash->createPaymentIntenChecksumValue(
            $credentials['secret_key'] ?? '',
            $data
        );

        $response = $bayarcash->createPaymentIntent($data);

        Log::write('Payment intent created', [
            'order_number'      => $data['order_number'],
            'payment_intent_id' => $response->id ?? null,
            'url'               => $response->url ?? null,
        ]);

        if (! empty($response->url)) {
            header('Location: ' . $response->url);
            exit;
        }

        $errorMessage = 'Bayarcash did not return a payment URL.';
    } catch (ValidationException $e) {
        // Thrown when Bayarcash rejects the request body -- a malformed phone
        // number, or an amount below the portal minimum. errors() carries the
        // per-field detail worth showing back.
        $details      = $e->errors();
        $errorMessage = is_array($details['message'] ?? null)
            ? implode(' ', $details['message'])
            : (string) ($details['message'] ?? 'Validation failed.');

        foreach ($details['errors'] ?? [] as $error) {
            $fieldErrors[] = is_array($error) ? implode(' ', $error) : (string) $error;
        }

        Log::write('Payment intent rejected: ' . $errorMessage);
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
        Log::write('Payment intent failed: ' . $errorMessage);
    }
}

$orderNumber = $_POST['order_number'] ?? ('DEMO' . date('ymdHis'));
$amount      = $_POST['amount'] ?? '10.00';

layout_head('Checkout', $config, 'index.php');
?>

<?php if ($errorMessage !== ''): ?>
    <div class="notice" data-tone="danger">
        <strong>Payment not created.</strong> <?= e($errorMessage) ?>
        <?php if ($fieldErrors !== []): ?>
            <ul>
                <?php foreach ($fieldErrors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<form method="POST" class="panel">
    <div class="panel-head">
        <span>New payment</span>
        <?php env_badge($config); ?>
    </div>
    <div class="panel-body">

        <div class="row-2">
            <div class="field">
                <label for="order_number">Order number</label>
                <input class="input mono" id="order_number" name="order_number"
                       value="<?= e($orderNumber) ?>" required>
            </div>
            <div class="field">
                <label for="amount">Amount (MYR)</label>
                <input class="input mono" id="amount" name="amount" type="number"
                       inputmode="decimal" step="0.01" min="1" value="<?= e($amount) ?>" required>
            </div>
        </div>

        <div class="field">
            <label for="description">Description</label>
            <input class="input" id="description" name="description"
                   value="<?= e($_POST['description'] ?? 'Demo payment') ?>" required>
        </div>

        <div class="field">
            <label for="payer_name">Payer name</label>
            <input class="input" id="payer_name" name="payer_name"
                   autocomplete="name" value="<?= e($_POST['payer_name'] ?? 'John Doe') ?>" required>
        </div>

        <div class="row-2">
            <div class="field">
                <label for="payer_email">Email</label>
                <input class="input" id="payer_email" name="payer_email" type="email"
                       inputmode="email" autocomplete="email"
                       value="<?= e($_POST['payer_email'] ?? 'john.doe@example.com') ?>" required>
            </div>
            <div class="field">
                <label for="payer_telephone_number">Telephone</label>
                <input class="input mono" id="payer_telephone_number" name="payer_telephone_number"
                       type="tel" inputmode="tel" autocomplete="tel"
                       value="<?= e($_POST['payer_telephone_number'] ?? '60123456789') ?>" required>
            </div>
        </div>

        <button class="btn btn-primary btn-block" type="submit">Continue to payment →</button>

    </div>
</form>

<div class="notice">
    HMAC-signed. Status is recorded by <code>callback.php</code> or
    <code>return.php</code> — whichever verifies first.
</div>

<?php layout_foot($config); ?>

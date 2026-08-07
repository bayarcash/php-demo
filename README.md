# Bayarcash PHP Demo

A working payment integration in plain PHP — no framework — built on
[`bayarcash/php-sdk`](https://github.com/bayarcash/php-sdk).

**Live demo: <https://php.bayarcash-demo.com>**

| | |
|---|---|
| **`guide/`** | Short scripts to read. One SDK call each. |
| **Checkout** | A payment you can run end to end. |
| **API console** | Call any SDK operation, see the raw response. |

## Quick start

```bash
git clone https://github.com/bayarcash/php-demo.git
cd php-demo
composer install
cp config.sample.php config.php     # add your portal key, token, secret key
php -S localhost:8000 -t public
```

Open <http://localhost:8000>. Storage is SQLite and the file is created on
first run — nothing else to set up.

Sandbox credentials come from the
[Bayarcash console](https://console.bayarcash-sandbox.com).

> **Callbacks cannot reach `localhost`.** They are sent from Bayarcash's
> servers, so use a tunnel (ngrok, Expose, Herd share) and set `base_url` to
> the public address while testing.

## The payment flow

```
  index.php            createPaymentIntent()  →  redirect payer
      │
      ▼
  Bayarcash            payer picks a bank and pays
      │
      ├──▶ callback.php    server to server. Always fires, even if the payer
      │                    closes the browser. Retried until you answer 200.
      │
      └──▶ return.php      the payer's browser lands here. Instant, but never
                           fires if they close the tab at the bank.
```

Both record the result, covering each other: the callback survives a closed
browser, the return URL survives a callback that is delayed or blocked.

They share one write path, so whichever arrives second is ignored rather than
overwriting the first — and neither writes anything until the checksum
verifies.

### Status codes

| | | |
|---|---|---|
| `0` | New | |
| `1` | Pending | |
| `2` | Unsuccessful | final |
| `3` | **Successful** | final — you have been paid |
| `4` | Cancelled | final |
| `5` | Expired | final |

Defined once in `src/PaymentStatus.php`. A final status never changes.

## The guide

```bash
php guide/01-create-payment-intent.php
```

| | |
|---|---|
| `01-create-payment-intent.php` | Building, signing and sending a payment |
| `02-payment-channels.php` | Channel IDs, portals, FPX bank list |
| `03-verify-callback.php` | **Checksum verification and safe recording** |
| `04-query-transaction.php` | Looking up transactions afterwards |
| `05-manual-transfer.php` | Payer-uploaded proof of transfer |
| `06-direct-debit.php` | Recurring collections and mandates |

## Layout

```
guide/            read these to learn the SDK
public/           everything served to the browser
  index.php         checkout
  callback.php      webhook
  return.php        browser landing
  orders.php        what has been recorded, plus the log
  api-console.php   call any SDK operation
  internal/         team tooling — talks to the API directly, no SDK
src/              Config, Db, BayarcashFactory, PaymentStatus,
                  TransactionRepository, Log
storage/          SQLite file and log (gitignored)
config.php        your credentials (gitignored)
```

`internal/` only exists where a `dev` environment is configured.

## Database

SQLite by default. For MySQL, change one word in `config.php`:

```php
'db' => [
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'dbname'   => 'bayarcash_demo',
    'username' => 'root',
    'password' => '',
],
```

Tables are created automatically either way.

## Deployment

Serve the site from `public/`. Set the document root to it — Apache
`DocumentRoot`, nginx `root`, or RunCloud's **Public Path** — then delete the
root `.htaccess`, which exists only for hosts that cannot be changed.

`base_url` is always the bare domain, **never** `/public/`:

```php
'base_url' => 'https://your-domain.com/',
```

Get it wrong and callbacks post to a 404 while checkout still appears to work.
Check after deploying:

```bash
curl -o /dev/null -w "%{http_code}\n" https://your-domain.com/callback.php
# 405 = correct (POST only)   404 = base_url is wrong
```

Old links keep working via `public/.htaccess` — `/v2/…` and `/v1/…` redirect to
their new homes, including `/v2/dev.php` → `/internal/dev.php`.

> On native nginx there is no `.htaccess`; add those redirects as `location`
> blocks.

## Links

- [PHP SDK](https://github.com/bayarcash/php-sdk)
- [Platform docs](https://docs.bayarcash.com)
- [API reference](https://api.webimpian.support/bayarcash)

> The old v1 demo is on the `archive/legacy-v1-v2` branch. The v1 API no longer
> accepts new transactions and the SDK does not support it.

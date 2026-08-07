# Bayarcash PHP Demo

A vanilla PHP demo of the [Bayarcash](https://bayarcash.com) payment gateway, built on
[`bayarcash/php-sdk`](https://github.com/bayarcash/php-sdk). No framework.

Three ways in, depending on what you need:

| | |
|---|---|
| **`guide/`** | Short scripts you **read**. One SDK call each, heavily commented. |
| **Checkout** | A working payment you **run** end to end. |
| **API console** | Call any SDK operation and read the raw response. |

## Getting started

```bash
git clone https://github.com/bayarcash/php-demo.git
cd php-demo
composer install
cp config.sample.php config.php     # add your portal key, token and secret key
php -S localhost:8000 -t public
```

Open <http://localhost:8000>. Storage is SQLite and the file is created on first
run, so there is nothing else to set up.

Get sandbox credentials from the [Bayarcash console](https://console.bayarcash-sandbox.com).

> Callbacks are sent from Bayarcash's servers to your `base_url`, so `localhost`
> will not receive them. Use a tunnel (ngrok, Expose, Herd share) and set
> `base_url` to the public address while testing.

## The payment flow

This is the whole integration, and the demo is organised around it:

```
  index.php                 createPaymentIntent()  ->  redirect payer
      |
      v
  Bayarcash checkout        payer picks a bank and pays
      |
      +---> callback.php    server to server. Always fires, even if the
      |                     payer closes the browser. Retried until you
      |                     answer 200.
      |
      +---> return.php      the payer's browser lands here. Instant, but
                            never fires if they close the tab at the bank.
```

Both record the result, covering each other: the callback survives a closed
browser, the return URL survives a callback that is delayed or blocked.

They share one write path, so whichever arrives second is ignored rather than
overwriting the first — and neither writes anything until the checksum
verifies. Each stores the payload it received, so you can compare them.

### Status codes

| Code | Meaning | |
|---|---|---|
| 0 | New | |
| 1 | Pending | |
| 2 | Unsuccessful | final |
| 3 | **Successful** | final — you have been paid |
| 4 | Cancelled | final |
| 5 | Expired | final |

Defined once in `src/PaymentStatus.php`. A final status never changes.

## The guide

Run any of them from the project root:

```bash
php guide/01-create-payment-intent.php
```

| File | Covers |
|---|---|
| `01-create-payment-intent.php` | Building, signing, and sending a payment |
| `02-payment-channels.php` | Channel IDs, portals, FPX bank list |
| `03-verify-callback.php` | **Checksum verification and safe recording** |
| `04-query-transaction.php` | Looking up transactions after the fact |
| `05-manual-transfer.php` | Payer-uploaded proof of transfer |
| `06-direct-debit.php` | Recurring collections and mandates |

## Layout

```
guide/                 read these to learn the SDK
public/                everything served to the browser
  index.php              checkout
  callback.php           webhook — server to server
  return.php             browser landing, with both raw payloads
  orders.php             what has been recorded, plus the log
  api-console.php        call any SDK operation, see the raw response
  assets/                css and images
  internal/              team tooling, talks to the API directly (no SDK)
    dev.php                test form
    return_dev.php         dev browser landing
    callback_dev.php       dev webhook — not gated, verified by checksum
src/                   Config, Db, BayarcashFactory, PaymentStatus,
                       TransactionRepository, Log
storage/               SQLite file and demo.log (gitignored)
config.php             your credentials (gitignored)
```

`internal/` only exists where a `dev` environment is configured.

## Database

SQLite by default, with nothing to install. To use MySQL, change one word in
`config.php`:

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

The app is served from `public/`. Two ways to do that:

**Set the document root to `public/`** — cleanest. Apache `DocumentRoot`,
nginx `root`, or RunCloud's **Public Path**:

```
Root Path:    /home/you/webapps/app
Public Path:  /home/you/webapps/app/public
```

Then delete the root `.htaccess`; it exists only for the other case.

**Or leave the document root at the repository root** and let the bundled
root `.htaccess` rewrite into `public/`.

Nothing appears in the URL either way. Old links keep working under both,
because those redirects live in `public/.htaccess`:

| Old URL | Goes to |
|---|---|
| `/v2/` | `/` |
| `/v2/index.php` | `/index.php` |
| `/v2/return.php` | `/return.php` |
| `/v2/listorder.php` | `/orders.php` |
| `/v2/check.php` | `/api-console.php` |
| `/v2/dev.php` | `/internal/dev.php` |
| `/v2/return_dev.php` | `/internal/return_dev.php` |
| `/v1/…` | `/` |

All 301s, so bookmarks and search engines follow permanently.

Either way `base_url` in `config.php` is the bare domain — **not** `/public/`:

```php
'base_url' => 'https://your-domain.com/',
```

Get that wrong and Bayarcash posts callbacks to a 404 while checkout still
appears to work. After deploying, check it:

```bash
curl -o /dev/null -w "%{http_code}\n" https://your-domain.com/callback.php
# 405 = reachable and correct (it only accepts POST).  404 = base_url is wrong.
```

> On nginx there is no `.htaccess`. Set the root to `public/` and add the
> redirects as `location` blocks.

## Links

- [PHP SDK](https://github.com/bayarcash/php-sdk)
- [Platform docs](https://docs.bayarcash.com)
- [API reference](https://api.webimpian.support/bayarcash)

> Looking for the old v1 demo? It is on the `archive/legacy-v1-v2` branch. The
> v1 API no longer accepts new transactions and the SDK does not support it.

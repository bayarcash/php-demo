# Bayarcash PHP Demo

A vanilla PHP demo of the [Bayarcash](https://bayarcash.com) payment gateway, built on
[`bayarcash/php-sdk`](https://github.com/bayarcash/php-sdk). No framework.

Two ways in, depending on what you need:

| | |
|---|---|
| **`guide/`** | Short scripts you **read**. One SDK call each, heavily commented. |
| **`public/`** | A working checkout you **run** end to end. |

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
  index.php                  createPaymentIntent()  ->  redirect payer
      |
      v
  Bayarcash checkout         payer picks a bank and pays
      |
      +---> callback.php     server-to-server. Authoritative. Always fires.
      |                      Verify checksum -> record status -> answer 200.
      |
      +---> return.php       payer's browser lands here. Cosmetic.
                             Reads what the callback recorded. Never writes.
```

**The one rule worth internalising:** payment status comes from `callback.php`,
never from `return.php`. The payer can close their browser, lose signal, or
never come back — the callback still arrives. Anything that depends on the
browser returning will eventually lose a payment.

### Status codes

| Code | Meaning | |
|---|---|---|
| 0 | New | |
| 1 | Pending | |
| 2 | Unsuccessful | final |
| 3 | **Successful** | final — you have been paid |
| 4 | Cancelled | final |
| 5 | Expired | final |

Defined once in `src/PaymentStatus.php`. Once a transaction reaches a final
status, later callbacks must not move it.

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
guide/       read these to learn the SDK
public/      the runnable demo
  index.php      checkout
  callback.php   webhook -- the only writer of status
  return.php     browser landing -- read only
  orders.php     what the callbacks recorded
  internal/      direct-API tooling
src/         Config, Db, BayarcashFactory, PaymentStatus, TransactionRepository
config.php   your credentials (gitignored)
```

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

Tables are created automatically either way. The two engines differ in exactly
two places, both isolated in `src/Db.php`.

## Links

- [PHP SDK](https://github.com/bayarcash/php-sdk)
- [Platform docs](https://docs.bayarcash.com)
- [API reference](https://api.webimpian.support/bayarcash)

> Looking for the old v1 demo? It is on the `archive/legacy-v1-v2` branch. The
> v1 API no longer accepts new transactions and the SDK does not support it.

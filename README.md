⚡ Thunder
==========

![CI](https://github.com/renoki-co/thunder/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/renoki-co/thunder/branch/master/graph/badge.svg)](https://codecov.io/gh/renoki-co/thunder/branch/master)
[![StyleCI](https://github.styleci.io/repos/425555174/shield?branch=master)](https://github.styleci.io/repos/425555174)
[![Latest Stable Version](https://poser.pugx.org/renoki-co/thunder/v/stable)](https://packagist.org/packages/renoki-co/thunder)
[![Total Downloads](https://poser.pugx.org/renoki-co/thunder/downloads)](https://packagist.org/packages/renoki-co/thunder)
[![Monthly Downloads](https://poser.pugx.org/renoki-co/thunder/d/monthly)](https://packagist.org/packages/renoki-co/thunder)
[![License](https://poser.pugx.org/renoki-co/thunder/license)](https://packagist.org/packages/renoki-co/thunder)

Thunder is an advanced Laravel tool to track user consumption using Cashier's Metered Billing for Stripe. ⚡

## 🤝 Supporting

**If you are using one or more Renoki Co. open-source packages in your production apps, in presentation demos, hobby projects, school projects or so, sponsor our work with [Github Sponsors](https://github.com/sponsors/rennokki). 📦**

[<img src="https://github-content.s3.fr-par.scw.cloud/static/45.jpg" height="210" width="418" />](https://github-content.renoki.org/github-repo/45)

## 🚀 Installation

You can install the package via composer:

```bash
composer require renoki-co/thunder
```

This project comes with Cashier, and it's really important [to install it](https://laravel.com/docs/8.x/billing#installation) before diving in the documentation.

## 🙌 Usage

Thunder tracks resources quotas for your users via Stripe Metered Billing.

You may only define plans with certain features that are connected to Stripe by IDs, declaring them only once throughout your code.

Reporting the usages is done with the IDs you define instead of unique pricing IDs that are different for each environment your deploy your app in, making it consistent throughout the code.

This way, you can call usage reports just like [Cashier's Metered Billing](https://laravel.com/docs/8.x/billing#metered-billing), but instead of using the Stripe IDs, you are using the defined IDs:

```php
use RenokiCo\Thunder\Thunder;

Thunder::plan('Unlimited Plan', 'stripe_product_id', [
    Thunder::meteredFeature('Build Minutes', 'build.minutes', 'stripe_build_minutes_price_id'),
    Thunder::meteredFeature('Seats', 'seats', 'stripe_seats_price_id'),
]);

$subscription = $user->subscription('main');

Thunder::reportUsageFor('build.minutes', $subscription, 50);
Thunder::reportUsageFor('build.minutes', $subscription, 100);

Thunder::usage('build.minutes', $subscription); // 150
```

## 🐛 Testing

``` bash
vendor/bin/phpunit
```

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒  Security

If you discover any security related issues, please email alex@renoki.org instead of using the issue tracker.

## 🎉 Credits

- [Alex Renoki](https://github.com/rennokki)
- [All Contributors](../../contributors)

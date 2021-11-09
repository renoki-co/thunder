<?php

namespace RenokiCo\Thunder\Test;

use RenokiCo\Thunder\Test\Models\User;
use RenokiCo\Thunder\Thunder;
use Stripe\ApiResource;
use Stripe\Exception\InvalidRequestException;
use Stripe\Plan;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class MeteredBillingTest extends TestCase
{
    protected static $buildMinutesPrice;
    protected static $seatsPrice;
    protected static $product;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        Stripe::setApiKey(getenv('STRIPE_SECRET') ?: env('STRIPE_SECRET'));

        static::$product = Product::create([
            'name' => 'Test Plan',
            'type' => 'service',
        ]);

        static::$buildMinutesPrice = Price::create([
            'nickname' => 'Test Build Minutes',
            'currency' => 'USD',
            'recurring' => [
                'interval' => 'month',
                'interval_count' => 1,
                'aggregate_usage' => 'sum',
                'usage_type' => 'metered',
            ],
            'tiers' => [
                ['up_to' => 1000, 'unit_amount_decimal' => 0, 'flat_amount_decimal' => 0],
                ['up_to' => 'inf', 'unit_amount_decimal' => 5, 'flat_amount_decimal' => 0],
            ],
            'tiers_mode' => 'volume',
            'billing_scheme' => 'tiered',
            'expand' => ['tiers'],
            'product' => static::$product->id,
        ]);

        static::$seatsPrice = Price::create([
            'nickname' => 'Test Seats',
            'currency' => 'USD',
            'recurring' => [
                'interval' => 'month',
                'interval_count' => 1,
                'aggregate_usage' => 'last_ever',
                'usage_type' => 'metered',
            ],
            'tiers' => [
                ['up_to' => 5, 'unit_amount_decimal' => 0, 'flat_amount_decimal' => 0],
                ['up_to' => 'inf', 'unit_amount_decimal' => 100, 'flat_amount_decimal' => 0],
            ],
            'tiers_mode' => 'volume',
            'billing_scheme' => 'tiered',
            'expand' => ['tiers'],
            'product' => static::$product->id,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        Thunder::plan('Basic Plan', static::$product->id, [
            Thunder::feature('VIP Access', 'vip.access', true),
            Thunder::feature('Extra Gold', 'extra.gold', 100),
            Thunder::meteredFeature('Build Minutes', 'build.minutes', static::$buildMinutesPrice->id),
            Thunder::meteredFeature('Seats', 'seats', static::$seatsPrice->id),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        static::deleteStripeResource(new Plan(static::$buildMinutesPrice->id));
        static::deleteStripeResource(new Plan(static::$seatsPrice->id));
        static::deleteStripeResource(static::$product);
    }

    /**
     * Delete the given Stripe resource.
     *
     * @param  \Stripe\ApiResource  $resource
     * @return void
     */
    protected static function deleteStripeResource(ApiResource $resource)
    {
        try {
            $resource->delete();
        } catch (InvalidRequestException $e) {
            //
        }
    }

    public function test_metering_billing()
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $subscription = Thunder::subscription(
            $user->newSubscription('main'),
            Thunder::plan(id: static::$product->id)
        )->create('pm_card_visa');

        // Make the sync feature report a 150 of total build minutes.
        Thunder::autoReportUsage('build.minutes', function ($feature, $subscription) {
            return 150;
        });

        $this->assertEquals(0, Thunder::usage('build.minutes', $user->subscription('main')));

        Thunder::updateUsageReports($user->subscription('main'));

        $this->assertEquals(150, Thunder::usage('build.minutes', $user->subscription('main')));

        Thunder::reportUsageFor('build.minutes', $user->subscription('main'), 50);

        $this->assertEquals(200, Thunder::usage('build.minutes', $user->subscription('main')));

        $this->assertTrue(Thunder::hasFeature('vip.access', $user->subscription('main')));
        $this->assertFalse(Thunder::hasFeature('extra.silver', $user->subscription('main')));
        $this->assertEquals(100, Thunder::featureValue('extra.gold', $user->subscription('main')));
    }
}

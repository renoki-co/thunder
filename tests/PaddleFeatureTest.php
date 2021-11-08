<?php

namespace RenokiCo\Thunder\Test;

use Carbon\Carbon;
use Laravel\Paddle\Subscription;
use RenokiCo\Thunder\Feature;
use RenokiCo\Thunder\Test\Models\User;
use RenokiCo\Thunder\Thunder;

class PaddleFeatureTest extends TestCase
{
    protected static $paddleMonthlyPlanId;
    protected static $paddleFreePlanId;
    protected static $paddleYearlyPlanId;

    public function test_paddle_record_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Thunder::getPlan('user', static::$paddleMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            50, $subscription->getUsedQuota('build.minutes')
        );

        $this->assertEquals(
            2950, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_paddle_set_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Thunder::getPlan('user', static::$paddleMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $subscription->setFeatureUsage('build.minutes', 20);

        $this->assertEquals(
            20, $subscription->getUsedQuota('build.minutes')
        );

        $this->assertEquals(
            2980, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_paddle_reduce_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Thunder::getPlan('user', static::$paddleMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            50, $subscription->getUsedQuota('build.minutes')
        );

        $subscription->decrementFeatureUsage('build.minutes', 55);

        $this->assertEquals(
            3000, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_paddle_reduce_feature_usage_without_usage()
    {
        $user = factory(User::class)->create();

        $plan = Thunder::getPlan('user', static::$paddleMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->decrementFeatureUsage('build.minutes', 55);

        $this->assertEquals(
            3000, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_paddle_feature_usage_on_reset()
    {
        $user = factory(User::class)->create();

        $plan = Thunder::getPlan('user', static::$paddleMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            50, $subscription->getUsedQuota('build.minutes')
        );

        $subscription->resetQuotas();

        $this->assertEquals(
            0, $subscription->getUsedQuota('build.minutes')
        );

        $this->assertEquals(
            3000, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_paddle_feature_usage_on_resetting_not_resettable()
    {
        $user = factory(User::class)->create();

        $plan = Thunder::getPlan('user', static::$paddleMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('teams', 1);

        $this->assertEquals(
            1, $subscription->getUsedQuota('teams')
        );

        $subscription->resetQuotas();

        $this->assertEquals(
            1, $subscription->getUsedQuota('teams')
        );

        $this->assertEquals(
            9, $subscription->getRemainingQuota('teams')
        );
    }

    public function test_paddle_record_inexistent_feature_usage()
    {
        $user = factory(User::class)->create();

        $plan = Thunder::getPlan('user', static::$paddleMonthlyPlanId)
            ->features([]);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('build.minutes', 50);

        $this->assertEquals(
            0, $subscription->getUsedQuota('build.minutes')
        );

        $this->assertEquals(
            0, $subscription->getRemainingQuota('build.minutes')
        );
    }

    public function test_paddle_plan_with_feature_to_array()
    {
        $user = factory(User::class)->create();

        $plan = Thunder::getPlan('user', static::$paddleMonthlyPlanId);

        $this->assertTrue(
            is_array($plan->toArray())
        );

        $this->assertTrue(
            is_array($plan->toArray()['features'])
        );
    }

    public function test_paddle_feature_usage_not_resettable()
    {
        $user = factory(User::class)->create();

        $plan = Thunder::getPlan('user', static::$paddleMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('teams', 5);

        $this->assertEquals(
            5, $subscription->getUsedQuota('teams')
        );

        Carbon::setTestNow(now()->addMonths(1));

        $this->assertEquals(
            5, $subscription->getUsedQuota('teams')
        );

        $this->assertEquals(
            5, $subscription->getRemainingQuota('teams')
        );
    }

    public function test_paddle_feature_usage_over_the_amount()
    {
        $user = factory(User::class)->create();

        $plan = Thunder::getPlan('user', static::$paddleMonthlyPlanId);

        $subscription = $this->createSubscription($user, $plan);

        $overQuota = 'not_set';

        $subscription->recordFeatureUsage('teams', 11, true, function ($feature, $valueOverQuota, $subscription) use (&$overQuota) {
            $overQuota = $valueOverQuota;
        });

        $this->assertEquals(1, $overQuota);
    }

    public function test_paddle_feature_usage_on_unlimited()
    {
        $user = factory(User::class)->create();

        $plan = Thunder::getPlan('user', static::$paddleMonthlyPlanId)
            ->features([
                Thunder::feature('Seats', 'teams')->unlimited()->notResettable(),
            ]);

        $subscription = $this->createSubscription($user, $plan);

        $overQuota = 0;

        $subscription->recordFeatureUsage('teams', 100, true, function ($feature, $valueOverQuota, $subscription) use (&$overQuota) {
            $overQuota = 'set';
        });

        $this->assertEquals(
            100, $subscription->getUsedQuota('teams')
        );

        Carbon::setTestNow(now()->addMonths(1));

        $this->assertEquals(
            100, $subscription->getUsedQuota('teams')
        );

        $this->assertEquals(
            -1, $subscription->getRemainingQuota('teams')
        );

        $this->assertEquals(0, $overQuota);
    }

    public function test_paddle_downgrading_plan()
    {
        $user = factory(User::class)->create();

        $freePlan = Thunder::getPlan('user', static::$paddleFreePlanId);

        $paidPlan = Thunder::getPlan('user', static::$paddleMonthlyPlanId);

        $subscription = $this->createSubscription($user, $paidPlan);

        $subscription->recordFeatureUsage('teams', 10);

        $overQuotaFeatures = $subscription->featuresOverQuotaWhenSwapping($freePlan);

        $this->assertCount(
            1, $overQuotaFeatures
        );

        $this->assertEquals(
            'teams', $overQuotaFeatures->first()->id
        );
    }

    public function test_paddle_sync_manually_the_feature_values()
    {
        Thunder::syncFeatureUsage('teams', function ($subscription, Feature $feature) {
            $this->assertInstanceOf(Subscription::class, $subscription);
            $this->assertInstanceOf(Feature::class, $feature);

            return 5;
        });

        $user = factory(User::class)->create();

        $plan = Thunder::getPlan('user', static::$paddleMonthlyPlanId)
            ->features([
                Thunder::feature('Seats', 'teams', 100)->notResettable(),
            ]);

        $subscription = $this->createSubscription($user, $plan);

        $subscription->recordFeatureUsage('teams', 5);

        $this->assertEquals(10, $subscription->getUsedQuota('teams'));
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$paddleMonthlyPlanId = getenv('PADDLE_TEST_PLAN') ?: env('PADDLE_TEST_PLAN');
        static::$paddleYearlyPlanId = getenv('PADDLE_YEARLY_TEST_PLAN') ?: env('PADDLE_YEARLY_TEST_PLAN');
        static::$paddleFreePlanId = getenv('PADDLE_TEST_FREE_PLAN') ?: env('PADDLE_TEST_FREE_PLAN');
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\Laravel\Paddle\Cashier::class)) {
            $this->markTestSkipped('Paddle is not installed.');
        }

        $freePaddlePlan = Thunder::plan('user', 'Free Plan', static::$paddleFreePlanId)
            ->monthly(static::$paddleFreePlanId)
            ->features([
                Thunder::feature('Build Minutes', 'build.minutes', 10),
                Thunder::feature('Seats', 'teams', 5)->notResettable(),
            ]);

        Thunder::plan('user', 'Monthly $20', static::$paddleMonthlyPlanId)
            ->incentive(static::$paddleMonthlyPlanId, static::$paddleYearlyPlanId)
            ->inheritFeaturesFromPlan($freePaddlePlan, [
                Thunder::feature('Build Minutes', 'build.minutes', 3000),
                Thunder::feature('Seats', 'teams', 10)->notResettable(),
            ]);
    }

    /**
     * Create a new subscription.
     *
     * @param  \RenokiCo\Thunder\Test\Models\User  $user
     * @param  \RenokiCo\Thunder\Plan  $plan
     * @return \RenokiCo\Thunder\Models\Paddle\Subscription
     */
    protected function createSubscription($user, $plan)
    {
        return $user->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 1,
            'paddle_plan' => $plan->id,
            'paddle_status' => 'active',
            'quantity' => 1,
        ]);
    }
}

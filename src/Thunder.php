<?php

namespace RenokiCo\Thunder;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \RenokiCo\Thunder\Plan plan(string $name = null, string $id, $features = [])
 * @method static \Laravel\Cashier\SubscriptionBuilder subscription($subscriptionBuilder, $plan)
 * @method static void hasFeature(string $id, $subscription)
 * @method static void autoReportUsage(string $id, \Closure $callback)
 * @method static \Stripe\UsageRecord reportUsageFor(string $featureId, $subscription, $quantity = 1, $timestamp = null)
 * @method static void updateUsageReports($subscription)
 * @method static void updateUsageReport(string $featureID, $subscription)
 * @method static int|float usage(string $featureId, $subscription)
 * @method static \RenokiCo\Thunder\Feature feature(string $name, string $id)
 * @method static \RenokiCo\Thunder\MeteredFeature meteredFeature(string $name, string $id, string $stripePriceId)
 * @method static void cleanReportUsageCallbacks()
 * @method static void clearPlans()
 *
 * @see \RenokiCo\Thunder\ThunderManager
 */
class Thunder extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'thunder.manager';
    }
}

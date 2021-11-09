<?php

namespace RenokiCo\Thunder;

use Closure;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionBuilder;

class ThunderManager
{
    /**
     * The callback to call when syncing the current usage.
     *
     * @var array[Closure]
     */
    protected $reportUsageCallbacks = [];

    /**
     * Initialize a new plan.
     *
     * @param  string|null  $name
     * @param  string  $id
     * @param  \RenokiCo\Thunder\Feature[]  $features
     * @return \RenokiCo\Thunder\Plan
     */
    public function plan(string $name = null, string $id, $features = [])
    {
        if ($plan = $this->plans[$id] ?? null) {
            return $plan;
        }

        $this->plans[$id] = $plan = new Plan($name, $id, $features);

        return $plan;
    }

    /**
     * Create a new subscription builder instance by attaching metered
     * features prices from the given plan.
     *
     * @param  \Laravel\Cashier\SubscriptionBuilder  $subscription
     * @param  \RenokiCo\Thunder\Plan  $plan
     * @return \Laravel\Cashier\SubscriptionBuilder
     */
    public function subscription(SubscriptionBuilder $subscription, Plan $plan)
    {
        foreach ($plan->meteredFeatures() as $feature) {
            $subscription->meteredPrice($feature->stripePriceId);
        }

        return $subscription;
    }

    /**
     * Check if the subscription has the given feature.
     *
     * @param  string  $id
     * @param  \Laravel\Cashier\Subscription  $subscription
     * @return bool
     */
    public function hasFeature(string $id, Subscription $subscription)
    {
        $plan = $this->getPlanFromSubscription($subscription);

        if (! $plan) {
            return false;
        }

        return ! is_null($plan->feature($id));
    }

    /**
     * Retrieved the declared feature value.
     *
     * @param  string  $id
     * @param  \Laravel\Cashier\Subscription  $subscription
     * @return mixed
     */
    public function featureValue(string $id, Subscription $subscription)
    {
        $plan = $this->getPlanFromSubscription($subscription);

        if (! $plan) {
            return;
        }

        $feature = $plan->feature($id);

        return $feature ? $feature->value : null;
    }

    /**
     * Add a callback to sync the feature usage automatically.
     *
     * @param  string  $id
     * @param  Closure  $callback
     * @return void
     */
    public function autoReportUsage(string $id, Closure $callback)
    {
        $this->reportUsageCallbacks[$id] = $callback;
    }

    /**
     * Report the usage for the given feature.
     *
     * @param  string  $featureId
     * @param  \Laravel\Cashier\Subscription  $subscription
     * @param  int  $quantity
     * @param  \DateTimeInterface|int|null  $timestamp
     * @return \Stripe\UsageRecord
     */
    public function reportUsageFor(
        string $featureId,
        Subscription $subscription,
        $quantity = 1,
        $timestamp = null,
    ) {
        if (! $plan = $this->getPlanFromSubscription($subscription)) {
            return;
        }

        $feature = $plan->feature($featureId);

        return $subscription->reportUsageFor($feature->stripePriceId, $quantity, $timestamp);
    }

    /**
     * Automatically update the usage reports for all the prices
     * within the subscriptions, according to the autoReportUsage() declarations.
     *
     * @param  \Laravel\Cashier\Subscription  $subscription
     * @return void
     */
    public function updateUsageReports(Subscription $subscription)
    {
        if (! $plan = $this->getPlanFromSubscription($subscription)) {
            return;
        }

        foreach ($plan->meteredFeatures() as $feature) {
            $this->updateUsageReport($feature, $subscription);
        }
    }

    /**
     * Automatically update the usage reports for the given feature prices
     * within the subscription, according to the autoReportUsage() declarations.
     *
     * @param  string|\RenokiCo\Thunder\MeteredFeature  $feature
     * @param  \Laravel\Cashier\Subscription  $subscription
     * @return void
     */
    public function updateUsageReport($feature, Subscription $subscription)
    {
        if (! $plan = $this->getPlanFromSubscription($subscription)) {
            return;
        }

        $feature = $feature instanceof MeteredFeature ? $feature : $plan->feature($feature);
        $featureUsage = $this->calculateFeatureUsage($feature, $subscription);

        if (! $feature instanceof MeteredFeature) {
            return;
        }

        if (! is_null($featureUsage)) {
            $subscription->reportUsageFor($feature->stripePriceId, $featureUsage, now());
        }
    }

    /**
     * Get the usage for the feature.
     *
     * @param  string  $featureId
     * @param  \Laravel\Cashier\Subscription  $subscription
     * @return int|float
     */
    public function usage(string $featureId, Subscription $subscription)
    {
        if (! $plan = $this->getPlanFromSubscription($subscription)) {
            return;
        }

        $feature = $plan->feature($featureId);

        if (! $feature instanceof MeteredFeature) {
            return;
        }

        $records = $subscription->usageRecordsFor($feature->stripePriceId);

        return $records->isNotEmpty() ? $records[0]->total_usage : 0;
    }

    /**
     * Start creating a new feature.
     *
     * @param  string  $name
     * @param  string  $id
     * @param  mixed  $value
     * @return \RenokiCo\Thunder\Feature
     */
    public function feature(string $name, string $id, mixed $value = null)
    {
        return new Feature($name, $id, $value);
    }

    /**
     * Start creating a new metered feature.
     *
     * @param  string  $name
     * @param  string  $id
     * @param  string  $stripePriceId
     * @return \RenokiCo\Thunder\MeteredFeature
     */
    public function meteredFeature(string $name, string $id, string $stripePriceId)
    {
        return new MeteredFeature($name, $id, $stripePriceId);
    }

    /**
     * Clear the sync usage callbacks.
     *
     * @return void
     */
    public function cleanReportUsageCallbacks(): void
    {
        $this->reportUsageCallbacks = [];
    }

    /**
     * Clear the plans.
     *
     * @return void
     */
    public function clearPlans(): void
    {
        $this->plans = [];
    }

    /**
     * Calculate the feature usage for a given feature, if possible.
     *
     * @param  \RenokiCo\Thunder\Feature  $feature
     * @param  \Laravel\Cashier\Subscription  $subscription
     * @return int|float|null
     */
    protected function calculateFeatureUsage(Feature $feature, Subscription $subscription)
    {
        if (! $callback = $this->reportUsageCallbacks[$feature->id] ?? null) {
            return null;
        }

        return call_user_func($callback, $feature, $subscription);
    }

    /**
     * Get the plan from the given subscription.
     *
     * @param  \Laravel\Cashier\Subscription  $subscription
     * @return \RenokiCo\Thunder\Plan|null
     */
    protected function getPlanFromSubscription(Subscription $subscription)
    {
        return $this->plan(id: $subscription->stripe_price ?: $subscription->items[0]->stripe_product);
    }
}

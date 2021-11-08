<?php

namespace RenokiCo\Thunder;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Spark\SparkManager;

class ThunderManager extends SparkManager
{
    /**
     * The callback to call when syncing the current usage.
     *
     * @var array[Closure]
     */
    protected $syncUsageCallbacks = [];

    /**
     * {@inheritdoc}
     */
    public function plan($billableType, $name, $id)
    {
        $this->plans[$billableType][] = $plan = new Plan($name, $id);

        return $plan;
    }

    /**
     * Get a plan instance by billable and ID.
     *
     * @param  string  $billableType
     * @param  string|int  $id
     * @return \RenokiCo\Thunder\Plan|null
     */
    public function getPlan($billableType, $id)
    {
        return $this->plans($billableType)->first(function (Plan $plan) use ($id) {
            return $plan->id == $id;
        });
    }

    /**
     * Start creating a new feature.
     *
     * @param  string  $name
     * @param  string|int  $id
     * @param  int|float  $value
     * @return \RenokiCo\Thunder\Feature
     */
    public function feature($name, $id, $value = 0)
    {
        return new Feature($name, $id, $value);
    }

    /**
     * Start creating a new metered feature.
     *
     * @param  string  $name
     * @param  string|int  $id
     * @param  int|float  $value
     * @return \RenokiCo\Thunder\MeteredFeature
     */
    public function meteredFeature($name, $id, $value = 0)
    {
        return new MeteredFeature($name, $id, $value);
    }

    /**
     * Add a callback to sync the feature usage.
     *
     * @param  string|int  $id
     * @param  Closure  $callback
     * @return void
     */
    public function syncFeatureUsage($id, Closure $callback)
    {
        $this->syncUsageCallbacks[$id] = $callback;
    }

    /**
     * Apply the feature usage sync via callback.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $subscription
     * @param  \RenokiCo\Thunder\Feature  $feature
     * @return int|float|null
     */
    public function applyFeatureUsageSync(Model $subscription, Feature $feature)
    {
        if ($callback = $this->syncUsageCallbacks[$feature->id] ?? null) {
            return call_user_func($callback, $subscription, $feature);
        }
    }

    /**
     * Clear the sync usage callbacks.
     *
     * @return void
     */
    public function cleanSyncUsageCallbacks(): void
    {
        $this->syncUsageCallbacks = [];
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
}

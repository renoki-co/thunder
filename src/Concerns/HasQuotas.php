<?php

namespace RenokiCo\Thunder\Concerns;

use Closure;
use RenokiCo\Thunder\Feature;
use RenokiCo\Thunder\MeteredFeature;
use RenokiCo\Thunder\Models\Usage;
use RenokiCo\Thunder\Plan;
use RenokiCo\Thunder\Thunder;

trait HasQuotas
{
    /**
     * Get the plan this instance belongs to.
     *
     * @param  string  $billableType
     * @return \RenokiCo\Thunder\Plan
     */
    public function getAttachedPlan()
    {
        return Thunder::getPlan(
            $this->user->sparkConfiguration('type'),
            $this->planIdentifier()
        );
    }

    /**
     * Get the service plan identifier for the resource.
     *
     * @return mixed
     */
    abstract public function planIdentifier();

    /**
     * Get the feature usages.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usage()
    {
        return $this->hasMany(Usage::class, 'subscription_id');
    }

    /**
     * Increment the feature usage.
     *
     * @param  \RenokiCo\Thunder\Feature|string|int  $feature
     * @param  int|float  $value
     * @param  bool  $incremental
     * @param  Closure|null  $exceedHandler
     * @return \RenokiCo\Thunder\Models\Usage|null
     */
    public function recordFeatureUsage(
        $feature,
        $value = 1,
        bool $incremental = true,
        Closure $exceedHandler = null,
    ) {
        $plan = $this->getAttachedPlan();

        if (! $plan) {
            return;
        }

        $feature = $plan->getFeature($feature);

        if (! $feature) {
            return;
        }

        /** @var \RenokiCo\Thunder\Models\Usage $usage */
        $usage = $this->usage()->firstOrNew([
            'subscription_id' => $this->getKey(),
            'feature_id' => $feature->id,
        ]);

        // Try to recalculate the usage based on user-defined callbacks.
        $usage->recalculate($this, $feature);

        $usage->fill([
            'used' => $incremental ? $usage->used + $value : $value,
            'used_total' => $incremental ? $usage->used_total + $value : $value,
        ]);

        $featureOverQuota = $this->featureOverQuotaFor($feature, $usage, $plan);

        if (! $feature->isUnlimited() && $featureOverQuota) {
            $remainingQuota = $this->getRemainingQuotaFor($feature, $usage, $plan);

            $valueOverQuota = value(function () use ($value, $remainingQuota) {
                return $remainingQuota < 0
                    ? -1 * $remainingQuota
                    : $value;
            });

            if ($feature instanceof MeteredFeature && method_exists($this, 'reportUsageFor')) {
                /** @var \RenokiCo\Thunder\MeteredFeature $feature */
                /** @var \Laravel\Cashier\Subscription $this */

                // If the user has for example 5 minutes left and the pipeline
                // ended and 10 minutes were consumed, update the feature usage to
                // feature value (meaning everything got consumed) and report
                // the usage based on the difference for the remaining difference,
                // but with positive value.
                $this->reportUsageFor($feature->meteredId, $valueOverQuota);
            }

            /** @var \RenokiCo\Thunder\Feature $feature */

            // Fill the usage later since the getRemainingQuotaFor() uses the $usage
            // object that was updated with the current requested feature usage recording.
            // This way, the next time the customer uses again the feature, it will jump straight up
            // to billing using metering instead of calculating the difference.
            $usage->fill([
                'used' => $this->getFeatureQuota($feature, $plan),
            ]);

            if ($exceedHandler) {
                $exceedHandler($feature, $valueOverQuota, $this);
            }
        }

        $usage->save();

        return $usage;
    }

    /**
     * Reduce the usage amount.
     *
     * @param  \RenokiCo\Thunder\Feature|string|int  $id
     * @param  int|float  $uses
     * @return null|\RenokiCo\Thunder\Models\Usage
     */
    public function reduceFeatureUsage($feature, $uses = 1, bool $incremental = true)
    {
        /** @var \RenokiCo\Thunder\Models\Usage|null $usage */
        $feature = $this->getAttachedPlan()->getFeature($feature);

        $usage = $this->usage()
            ->whereFeatureId($feature)
            ->first();

        if (is_null($usage)) {
            return;
        }

        // Try to recalculate the usage based on user-defined callbacks.
        $usage->recalculate($this, $feature);

        $used = max($incremental ? $usage->used - $uses : $uses, 0);

        $usage->fill([
            'used' => $used,
            'used_total' => $used,
        ]);

        $usage->save();

        return $usage;
    }

    /**
     * Reduce the usage amount.
     *
     * @param  \RenokiCo\Thunder\Feature|string|int  $feature
     * @param  int|float  $uses
     * @return null|\RenokiCo\Thunder\Models\Usage
     */
    public function decrementFeatureUsage($feature, $uses = 1, bool $incremental = true)
    {
        return $this->reduceFeatureUsage($feature, $uses, $incremental);
    }

    /**
     * Set the feature usage to a specific value.
     *
     * @param  \RenokiCo\Thunder\Feature|string|int  $feature
     * @param  int|float  $value
     * @return \RenokiCo\Thunder\Models\Usage|null
     */
    public function setFeatureUsage($feature, $value)
    {
        return $this->recordFeatureUsage($feature, $value, false);
    }

    /**
     * Get the feature used quota.
     *
     * @param  \RenokiCo\Thunder\Feature|string|int  $feature
     * @return int|float
     */
    public function getUsedQuota($feature)
    {
        /** @var \RenokiCo\Thunder\Models\Usage|null $usage */
        $usage = $this->usage()
            ->whereFeatureId($feature)
            ->first();

        return $usage ? $usage->used : 0;
    }

    /**
     * Get the feature used total quota.
     *
     * @param  \RenokiCo\Thunder\Feature|string|int  $feature
     * @return int|float
     */
    public function getTotalUsedQuota($feature)
    {
        /** @var \RenokiCo\Thunder\Models\Usage|null $usage */
        $usage = $this->usage()
            ->whereFeatureId($feature)
            ->first();

        return $usage ? $usage->used_total : 0;
    }

    /**
     * Get the feature quota remaining.
     *
     * @param  \RenokiCo\Thunder\Feature|string|int  $feature
     * @return int|float
     */
    public function getRemainingQuota($feature)
    {
        $featureValue = $this->getFeatureQuota($feature);

        if ($featureValue < 0) {
            return -1;
        }

        return $featureValue - $this->getUsedQuota($feature);
    }

    /**
     * Get the feature quota remaining.
     *
     * @param  \RenokiCo\Thunder\Feature|string|int  $feature
     * @param  \RenokiCo\Thunder\Models\Usage  $usage
     * @return int|float
     */
    public function getRemainingQuotaFor($feature, $usage)
    {
        $featureValue = $this->getFeatureQuota($feature);

        if ($featureValue < 0) {
            return -1;
        }

        return $featureValue - $usage->used;
    }

    /**
     * Get the feature quota.
     *
     * @param  \RenokiCo\Thunder\Feature|string|int  $feature
     * @return int|float
     */
    public function getFeatureQuota($feature)
    {
        $feature = $this->getAttachedPlan()->getFeature($feature);

        if (! $feature) {
            return 0;
        }

        return $feature->value;
    }

    /**
     * Check if the feature is over the assigned quota.
     *
     * @param  \RenokiCo\Thunder\Feature|string|int  $feature
     * @return bool
     */
    public function featureOverQuota($feature): bool
    {
        $plan = $this->getAttachedPlan();
        $feature = $plan->getFeature($feature);

        if ($feature->isUnlimited()) {
            return false;
        }

        return $this->getRemainingQuota($feature, $plan) < 0;
    }

    /**
     * Check if the feature is over the assigned quota.
     *
     * @param  \RenokiCo\Thunder\Feature|string|int  $feature
     * @param  \RenokiCo\Thunder\Models\Usage  $usage
     * @return bool
     */
    public function featureOverQuotaFor($feature, $usage): bool
    {
        $plan = $this->getAttachedPlan();
        $feature = $plan->getFeature($feature);

        if ($feature->isUnlimited()) {
            return false;
        }

        return $this->getRemainingQuotaFor($feature, $usage, $plan) < 0;
    }

    /**
     * Check if there are features over quota
     * if the current subscription would be swapped
     * to a new one.
     *
     * @param  \RenokiCo\Thunder\Plan|string|int|null  $plan
     * @return \Illuminate\Support\Collection
     */
    public function featuresOverQuotaWhenSwapping($plan)
    {
        $plan = $plan instanceof Plan
            ? $plan
            : Thunder::getPlan($this->user->sparkConfiguration('type'), $plan);

        return $plan->features
            ->reject->resettable
            ->reject->isUnlimited()
            ->filter(function (Feature $feature) use ($plan) {
                $remainingQuota = $this->getRemainingQuota($feature, $plan);

                return $remainingQuota <= 0;
            });
    }

    /**
     * Reset the quotas of this subscription.
     *
     * @return void
     */
    public function resetQuotas()
    {
        $plan = $this->getAttachedPlan();

        $this->usage()
            ->get()
            ->each(function (Usage $usage) use ($plan) {
                $feature = $plan->getFeature($usage->feature_id);

                if ($feature->resettable) {
                    $usage->delete();
                }
            });
    }
}

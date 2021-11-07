<?php

namespace RenokiCo\Thunder\Models;

use Illuminate\Database\Eloquent\Model;
use RenokiCo\Thunder\Feature;
use RenokiCo\Thunder\Thunder;

class Usage extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'subscription_usages';

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * Recalculate the usage values based on the user-defined callbacks.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $subscription
     * @param  \RenokiCo\Thunder\Feature  $feature
     * @return self
     */
    public function recalculate(Model $subscription, Feature $feature)
    {
        $usageValue = Thunder::applyFeatureUsageSync($subscription, $feature);

        // If no callback was defined just return the same instance.
        if (is_null($usageValue)) {
            return $this;
        }

        return $this->fill([
            'used' => $usageValue,
            'used_total' => $usageValue,
        ]);
    }
}

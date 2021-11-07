<?php

namespace RenokiCo\Thunder\Models;

use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription as CashierSubscription;
use RenokiCo\Thunder\Concerns\HasQuotas;

class StripeSubscription extends CashierSubscription
{
    use HasQuotas;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subscriptions';

    /**
     * Get the service plan identifier for the resource.
     *
     * @return mixed
     */
    public function planIdentifier()
    {
        return $this->stripe_plan;
    }

    /**
     * Get the subscription items related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(Cashier::$subscriptionItemModel, 'subscription_id');
    }
}

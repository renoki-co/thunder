<?php

namespace RenokiCo\Thunder\Models;

use Laravel\Paddle\Subscription as CashierSubscription;
use RenokiCo\Thunder\Concerns\HasQuotas;

class PaddleSubscription extends CashierSubscription
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
        return $this->paddle_plan;
    }

    /**
     * Alias for ->billable().
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function user()
    {
        return $this->billable();
    }
}

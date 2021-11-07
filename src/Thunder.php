<?php

namespace RenokiCo\Thunder;

use Spark\Spark;

/**
 * @method static \RenokiCo\Thunder\Plan|null getPlan($billableType, $id)
 * @method static \RenokiCo\Thunder\Feature feature($name, $id, $value = 0)
 * @method static \RenokiCo\Thunder\MeteredFeature meteredFeature($name, $id, $value = 0)
 * @method static void syncFeatureUsage($id, $callback)
 * @method static int|float|null applyFeatureUsageSync($subscription, $feature)
 * @method static void cleanSyncUsageCallbacks()
 * @method static void clearPlans()
 *
 * @see \RenokiCo\Thunder\ThunderManager
 */
class Thunder extends Spark
{
    //
}

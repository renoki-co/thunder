<?php

namespace RenokiCo\Thunder;

use Spark\Plan as SparkPlan;

class Plan extends SparkPlan
{
    /**
     * The features list for the instance.
     *
     * @var \Illuminate\Support\Collection
     */
    public $features;

    /**
     * {@inheritdoc}
     */
    public function __construct($name, $id)
    {
        parent::__construct($name, $id);

        $this->features([]);
    }

    /**
     * Attach features to the instance.
     *
     * @param  \RenokiCo\Thunder\Feature[]  $features
     * @return self
     */
    public function features(array $features)
    {
        $this->features = collect($features)->unique(function (Feature $feature) {
            return $feature->id;
        });

        return $this;
    }

    /**
     * Inherit features from another plan.
     *
     * @param  \RenokiCo\Thunder\Plan  $plan
     * @return self
     */
    public function inheritFeaturesFromPlan(Plan $plan, array $features = [])
    {
        $this->features = collect($features)
            ->merge($plan->features)
            ->merge($this->features)
            ->unique(function (Feature $feature) {
                return $feature->id;
            });

        return $this;
    }

    /**
     * Get the metered features.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMeteredFeatures()
    {
        return $this->features->filter(function ($feature) {
            return $feature instanceof MeteredFeature;
        });
    }

    /**
     * Get a specific feature by id.
     *
     * @param  \RenokiCo\Thunder\Feature|string|int  $feature
     * @return \RenokiCo\Thunder\Feature|null
     */
    public function getFeature($feature)
    {
        if ($feature instanceof Feature) {
            $feature = $feature->id;
        }

        return $this->features->first(function (Feature $f) use ($feature) {
            return $f->id == $feature;
        });
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'features' => $this->features->all(),
        ]);
    }

    /**
     * Get the plan ID.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->id;
    }
}

<?php

namespace RenokiCo\Thunder;

use Illuminate\Support\Collection;

class Plan
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        public string $name,
        public string $id,
        public Collection|array $features = [],
    ) {
        $this->features($features);
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
    public function meteredFeatures()
    {
        return $this->features->filter(function ($feature) {
            return $feature instanceof MeteredFeature;
        });
    }

    /**
     * Get a specific feature by id.
     *
     * @param  \RenokiCo\Thunder\Feature|string  $feature
     * @return \RenokiCo\Thunder\Feature|null
     */
    public function feature($feature)
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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'features' => $this->features->all(),
        ];
    }
}

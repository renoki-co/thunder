<?php

namespace RenokiCo\Thunder;

use Illuminate\Support\Collection;

class Plan
{
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
     * @param  string  $featureId
     * @return \RenokiCo\Thunder\Feature|null
     */
    public function feature($featureId)
    {
        return $this->features->first(function (Feature $f) use ($featureId) {
            return $f->id == $featureId;
        });
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'features' => $this->features->all(),
        ];
    }
}

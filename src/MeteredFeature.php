<?php

namespace RenokiCo\Thunder;

class MeteredFeature extends Feature
{
    /**
     * The metered plan ID, in case
     * the feature has a quota for metered plan.
     *
     * @var string|int|null
     */
    public $meteredId;

    /**
     * The metered price per unit, in case
     * the feature has a quota for metered plan.
     *
     * @var float
     */
    public $meteredPrice = 0.00;

    /**
     * The metered unit name.
     *
     * @var string
     */
    public $meteredUnitName;

    /**
     * Set the metered plan.
     *
     * @param  string|int  $id
     * @param  float  $price
     * @param  string|null  $unitName
     * @return self
     */
    public function meteredPlan($id, float $price, string $unitName = null)
    {
        $this->meteredId = $id;
        $this->meteredPrice = $price;
        $this->meteredUnitName = $unitName;

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'metered_id' => $this->meteredId,
            'metered_price' => $this->meteredPrice,
            'metered_unit_name' => $this->meteredUnitName,
        ]);
    }
}

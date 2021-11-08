<?php

namespace RenokiCo\Thunder;

use Illuminate\Contracts\Support\Arrayable;

class Feature implements Arrayable
{
    /**
     * The feature's ID.
     *
     * @var string|int
     */
    public $id;

    /**
     * The feature's displayable name.
     *
     * @var string
     */
    public $name;

    /**
     * The feature's description.
     *
     * @var string
     */
    public $description;

    /**
     * The feature's value.
     *
     * @var int|float
     */
    public $value;

    /**
     * Wether the feature should reset
     * after each billing cycle.
     *
     * @var bool
     */
    public $resettable = true;

    /**
     * Initialize the Feature.
     *
     * @param  string  $name
     * @param  string|int  $id
     * @param  int|float  $value
     * @return void
     */
    public function __construct(string $name, $id, $value)
    {
        $this->name = $name;
        $this->id = $id;
        $this->value = $value;
    }

    /**
     * Set a description for the feature.
     *
     * @param  string  $description
     * @return $this
     */
    public function description(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set a new value for the usability.
     *
     * @param  int|float  $value
     * @return $this
     */
    public function value($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Set the feature as unlimited value.
     *
     * @return $this
     */
    public function unlimited()
    {
        $this->value = -1;

        return $this;
    }

    /**
     * Mark the feature as not resettable.
     *
     * @return $this
     */
    public function notResettable()
    {
        $this->resettable = false;

        return $this;
    }

    /**
     * Check if the feature has unlimited uses.
     *
     * @return bool
     */
    public function isUnlimited(): bool
    {
        return $this->value < 0;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'value' => $this->value,
            'unlimited' => $this->isUnlimited(),
            'resettable' => $this->resettable,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the feature ID.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->id;
    }
}

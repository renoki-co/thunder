<?php

namespace RenokiCo\Thunder;

use Illuminate\Contracts\Support\Arrayable;

class Feature implements Arrayable
{
    /**
     * Initialize the Feature.
     *
     * @param  string  $name
     * @param  string  $id
     * @param  string|null  $stripePriceId
     * @return void
     */
    public function __construct(
        public string $name,
        public string $id,
        public string|null $stripePriceId = null,
    ) {
        //
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
            'stripe_price_id' => $this->stripePriceId,
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
}

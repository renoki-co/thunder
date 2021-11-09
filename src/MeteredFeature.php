<?php

namespace RenokiCo\Thunder;

class MeteredFeature extends Feature
{
    /**
     * Initialize the MeteredFeature.
     *
     * @param  string  $name
     * @param  string  $id
     * @param  string  $stripePriceId
     * @return void
     */
    public function __construct(
        public string $name,
        public string $id,
        public string $stripePriceId,
    ) {
        parent::__construct($name, $id, null);
    }
}

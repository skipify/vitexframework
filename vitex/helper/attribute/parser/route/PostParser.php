<?php


namespace vitex\helper\attribute\parser\route;

use vitex\constant\RequestMethod;

/**
 * POST
 * @package vitex\helper\attribute\parser\route
 */
class PostParser extends RouteParser
{
    public function doFinal(array $attributes)
    {
        $this->data->setMethod(RequestMethod::POST);
        return parent::doFinal($attributes);
    }
}
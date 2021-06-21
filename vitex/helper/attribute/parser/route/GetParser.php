<?php


namespace vitex\helper\attribute\parser\route;

use vitex\constant\RequestMethod;

/**
 * GET注解
 * @package vitex\helper\attribute\parser\route
 */
class GetParser extends RouteParser
{
    public function doFinal(array $attributes)
    {
        $this->data->setMethod(RequestMethod::GET);
        return parent::doFinal($attributes);
    }
}
<?php


namespace vitex\helper\attribute\parser\route;

use vitex\constant\RequestMethod;

/**
 * PUT方法
 * @package vitex\helper\attribute\parser\route
 */
class PutParser extends RouteParser
{
    public function doFinal(array $attributes)
    {
        $this->data->setMethod(RequestMethod::PUT);
        return parent::doFinal($attributes);
    }
}
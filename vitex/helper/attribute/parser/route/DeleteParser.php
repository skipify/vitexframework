<?php


namespace vitex\helper\attribute\parser\route;

use vitex\constant\RequestMethod;

/**
 * 删除
 * @package vitex\helper\attribute\parser\route
 */
class DeleteParser extends RouteParser
{

    public function doFinal(array $attributes)
    {
        $this->data->setMethod(RequestMethod::DELETE);
        return parent::doFinal($attributes);
    }
}
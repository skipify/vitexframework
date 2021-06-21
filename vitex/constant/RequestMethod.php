<?php

namespace vitex\constant;

/**
 * Class HttpMethod
 * @package vitex\constant
 */
class RequestMethod
{
    const GET = "GET";

    const POST = "POST";

    const PUT = "PUT";

    const DELETE = "DELETE";

    const OPTIONS = "OPTIONS";

    const TRACE = "TRACE";

    const PATCH = "PATCH";

    const HEAD = "HEAD";

    const ALL = [
        self::GET,
        self::POST,
        self::PUT,
        self::DELETE,
        self::OPTIONS,
        self::TRACE,
        self::PATCH,
        self::HEAD,
    ];
}
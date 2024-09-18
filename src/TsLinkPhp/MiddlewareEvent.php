<?php

namespace Murdej\TsLinkPhp;

class MiddlewareEvent
{
    public function next() {
        $this->tsLink->callNextMiddleware($this);
    }

    public function __construct(
        public TsLink $tsLink,
        public Request $request,
        public Response $response,
    )
    {
    }
}
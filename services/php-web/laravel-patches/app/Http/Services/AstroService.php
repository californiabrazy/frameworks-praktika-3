<?php

namespace App\Http\Services;

use App\Http\Clients\AstroClient;

class AstroService
{
    protected AstroClient $astroClient;

    public function __construct(AstroClient $astroClient)
    {
        $this->astroClient = $astroClient;
    }

    public function getAstroEvents(array $params): array
    {
        return $this->astroClient->getAstroEvents($params);
    }
}

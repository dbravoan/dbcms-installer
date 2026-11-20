<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

final class DeploymentHealthController
{
    public function __invoke(): Response
    {
        $identity = base_path('DEPLOYMENT_ID');
        if (! is_file($identity)) {
            return response('Sin despliegue identificado.', 503);
        }

        return response(trim((string) file_get_contents($identity)), 200, ['Cache-Control' => 'no-store', 'Content-Type' => 'text/plain']);
    }
}

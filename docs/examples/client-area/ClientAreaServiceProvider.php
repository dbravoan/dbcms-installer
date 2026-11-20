<?php

declare(strict_types=1);

namespace DavidBravo\ClientArea;

use DavidBravo\ClientArea\Models\Player;
use DbCms\Core\Modules\BaseModuleServiceProvider;

final class ClientAreaServiceProvider extends BaseModuleServiceProvider
{
    protected string $identifier = 'client_area';

    protected string $name = 'Área privada de juegos';

    protected bool $isPageable = true;

    public function register(): void
    {
        $this->registerModuleAuth('client_area_players', Player::class, 'client_area');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'client-area');
        parent::boot();
    }

    public function registerPageableRoutes(): void
    {
        require __DIR__.'/Routes/web.php';
    }
}

<?php

namespace App\Providers;

use App\Repository\Interfaces\StatusTransacoesPendentesRepositoryInterface;
use App\Repository\SaldoRepository;
use App\Repository\StatusTransacoesPendentesRepository;
use Illuminate\Support\Facades\Route;
use App\Repository\TransacaoRepository;
use Illuminate\Support\ServiceProvider;
use App\Repository\Interfaces\SaldoRepositoryInterface;
use App\Repository\Interfaces\TransacaoRepositoryInterface;
use App\Adapters\RabbitMQ\ReprocessamentoComprasCartaoRabbitMQAdapter;
use App\Adapters\RabbitMQ\Interfaces\ReprocessamentoComprasCartaoRabbitMQAdapterInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TransacaoRepositoryInterface::class,
            TransacaoRepository::class
        );

        $this->app->bind(
            SaldoRepositoryInterface::class,
            SaldoRepository::class
        );

        $this->app->bind(
            ReprocessamentoComprasCartaoRabbitMQAdapterInterface::class,
            ReprocessamentoComprasCartaoRabbitMQAdapter::class
        );

        $this->app->bind(
            StatusTransacoesPendentesRepositoryInterface::class,
            StatusTransacoesPendentesRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar rotas de API manualmente, caso o arquivo exista
        if (file_exists(base_path('routes/api.php'))) {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        }

        // Registrar rotas web automaticamente
        if (file_exists(base_path('routes/web.php'))) {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        }
    }
}

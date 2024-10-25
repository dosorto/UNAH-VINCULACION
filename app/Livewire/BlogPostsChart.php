<?php

namespace App\Livewire;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Proyecto\Proyecto;

use Illuminate\Support\Facades\DB;
use App\Models\UnidadAcademica\FacultadCentro;


class BlogPostsChart extends BaseWidget
{
    protected function getStats(): array
    {
        $usuariosPorMes = User::select(DB::raw('MONTH(created_at) as month, COUNT(*) as count'))
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->pluck('count')
            ->toArray();

        return [
            Stat::make('Usuarios', User::count())
                ->description('Usuarios registrados')
                ->chart($usuariosPorMes)
                ->descriptionIcon('heroicon-m-arrow-trending-up'),
            Stat::make('Proyectos', Proyecto::count())
                ->description('Proyectos registrados')
                ->descriptionIcon('heroicon-m-arrow-trending-up'),
            Stat::make('Centros y Facultades', FacultadCentro::count())
                ->description('Centros y Facultades registrados')
                ->descriptionIcon('heroicon-m-arrow-trending-up'),
                



        ];
    }
}

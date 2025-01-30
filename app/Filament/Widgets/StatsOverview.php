<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Ambil total profit bulan ini
        $totalProfitThisMonth = DB::table('pesanans')
            ->join('projects', 'pesanans.project_id', '=', 'projects.id')
            ->join('produks', 'projects.id_produk', '=', 'produks.id_produk')
            ->whereMonth('pesanans.created_at', now()->month)
            ->whereYear('pesanans.created_at', now()->year)
            ->selectRaw('SUM(pesanans.harga_jual - produks.harga) as total_profit')
            ->value('total_profit') ?? 0;

        // Ambil total profit bulan lalu
        $totalProfitLastMonth = DB::table('pesanans')
            ->join('projects', 'pesanans.project_id', '=', 'projects.id')
            ->join('produks', 'projects.id_produk', '=', 'produks.id_produk')
            ->whereMonth('pesanans.created_at', now()->subMonth()->month)
            ->whereYear('pesanans.created_at', now()->subMonth()->year)
            ->selectRaw('SUM(pesanans.harga_jual - produks.harga) as total_profit')
            ->value('total_profit') ?? 0;

        // Hitung selisih keuntungan
        $profitDifference = $totalProfitThisMonth - $totalProfitLastMonth;

        // Hitung persentase perubahan keuntungan
        if ($totalProfitLastMonth > 0) {
            $profitPercentage = ($profitDifference / $totalProfitLastMonth) * 100;
        } else {
            $profitPercentage = $totalProfitThisMonth > 0 ? 100 : 0;
        }

        // Tentukan apakah profit meningkat atau menurun
        $profitStatus = $profitDifference > 0 ? 'increase' : 'decrease';

        // Format angka dengan IDR
        $formattedProfit = 'IDR ' . number_format($totalProfitThisMonth, 0, ',', '.');
        $formattedDiff = number_format(abs($profitPercentage), 2, ',', '.') . '%';
        return [
            Stat::make('Keuntungan', $formattedProfit)
                ->description(($profitDifference >= 0 ? 'Increase ' : 'Decrease ') . $formattedDiff)
                ->descriptionIcon($profitDifference >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->icon('heroicon-o-currency-dollar')
                ->color($profitDifference >= 0 ? 'success' : 'danger'),
            Stat::make('Active User', User::where('is_online', '1')->count())
                ->Icon('heroicon-o-users')
                ->description('Data pengguna yang online.')
                ->color('warning'),
            Stat::make('Total Pesanan', Pesanan::count())
                ->icon('heroicon-o-shopping-cart')
                ->description('Total Pesanan.')
                ->color('primary'),
        ];
    }
}

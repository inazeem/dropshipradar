<?php

namespace App\Http\Controllers;

use App\Helpers\CurrencyHelper;
use App\Models\Order;

class DashboardController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $isAdmin = $user->isAdmin();

        $base = $isAdmin ? Order::query() : $user->orders();

        $totalOrders   = (clone $base)->count();
        $totalRevenue  = (float) (clone $base)->sum('ebay_receipts');
        $totalCost     = (float) (clone $base)->sum('amazon_cost');
        $totalProfit   = (float) (clone $base)->sum('profit');
        $averageRoi    = (float) (clone $base)->avg('roi');
        $winningOrders = (clone $base)->where('profit', '>', 0)->count();
        $losingOrders  = (clone $base)->where('profit', '<', 0)->count();

        $trend = (clone $base)
            ->orderBy('order_date')
            ->get(['order_date', 'profit'])
            ->groupBy(fn ($o) => $o->order_date->format('M Y'))
            ->map(fn ($group) => round((float) $group->sum('profit'), 2))
            ->take(-6);

        $recentOrders = (clone $base)
            ->orderByDesc('order_date')
            ->limit(6)
            ->get();

        $currency = $user->currency;
        $currencySymbol = CurrencyHelper::getSymbol($currency);

        return view('dashboard', compact(
            'totalOrders', 'totalRevenue', 'totalCost', 'totalProfit',
            'averageRoi', 'winningOrders', 'losingOrders', 'trend', 'recentOrders',
            'currency', 'currencySymbol'
        ));
    }
}

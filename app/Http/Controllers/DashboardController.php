<?php

namespace App\Http\Controllers;

use App\Helpers\CurrencyHelper;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $isAdmin = $user->isAdmin();
        $filterUserId = $request->get('user_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $base = $isAdmin ? Order::query() : $user->orders();

        if ($isAdmin && $filterUserId) {
            $base->where('user_id', $filterUserId);
        }

        if ($dateFrom) {
            $base->where('order_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $base->where('order_date', '<=', $dateTo);
        }

        $totalOrders   = (clone $base)->count();
        $totalRevenue  = (float) (clone $base)->sum('ebay_receipts');
        $totalCost     = (float) (clone $base)->sum('amazon_cost');
        $totalProfit   = (float) (clone $base)->sum('profit');
        $averageRoi    = (float) (clone $base)->avg('roi');
        $bestOrderProfit = (float) ((clone $base)->max('profit') ?? 0);
        $worstOrderProfit = (float) ((clone $base)->min('profit') ?? 0);
        $winningOrders = (clone $base)->where('profit', '>', 0)->count();
        $losingOrders  = (clone $base)->where('profit', '<', 0)->count();

        $trend = (clone $base)
            ->orderBy('order_date')
            ->get(['order_date', 'profit'])
            ->groupBy(fn ($o) => $o->order_date->format('M Y'))
            ->map(fn ($group) => round((float) $group->sum('profit'), 2))
            ->take(-12);

        $orders = ($isAdmin ? Order::with('user') : $user->orders())
            ->when($isAdmin && $filterUserId, fn ($query) => $query->where('user_id', $filterUserId))
            ->when($dateFrom, fn ($query) => $query->where('order_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->where('order_date', '<=', $dateTo))
            ->orderByDesc('order_date')
            ->paginate(25)
            ->withQueryString();

        $clients = $isAdmin ? User::where('role', 'client')->orderBy('name')->get(['id', 'name']) : collect();

        $currency = $user->currency;
        $currencySymbol = CurrencyHelper::getSymbol($currency);

        return view('dashboard', compact(
            'totalOrders', 'totalRevenue', 'totalCost', 'totalProfit',
            'averageRoi', 'bestOrderProfit', 'worstOrderProfit', 'winningOrders', 'losingOrders',
            'trend', 'orders', 'clients', 'isAdmin', 'currency', 'currencySymbol'
        ));
    }
}

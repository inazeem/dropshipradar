<?php

namespace App\Http\Controllers;

use App\Helpers\CurrencyHelper;
use App\Models\Order;
use Illuminate\Http\Request;

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $isAdmin = $user->isAdmin();

        $filterUserId = $request->get('user_id');
        $dateFrom     = $request->get('date_from');
        $dateTo       = $request->get('date_to');

        $baseQuery = $isAdmin
            ? Order::with('user')->orderBy('order_date', 'desc')
            : $user->orders()->orderBy('order_date', 'desc');

        if ($isAdmin && $filterUserId) {
            $baseQuery->where('user_id', $filterUserId);
        }
        if ($dateFrom) {
            $baseQuery->where('order_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $baseQuery->where('order_date', '<=', $dateTo);
        }

        $orders = $baseQuery->paginate(25)->withQueryString();

        $totalsQuery = $isAdmin
            ? ($filterUserId ? Order::where('user_id', $filterUserId) : Order::query())
            : $user->orders();

        if ($dateFrom) {
            $totalsQuery->where('order_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $totalsQuery->where('order_date', '<=', $dateTo);
        }

        $totals = [
            'revenue'    => (float) (clone $totalsQuery)->sum('ebay_receipts'),
            'cost'       => (float) (clone $totalsQuery)->sum('amazon_cost'),
            'profit'     => (float) (clone $totalsQuery)->sum('profit'),
            'avg_roi'    => (float) (clone $totalsQuery)->avg('roi'),
            'max_profit' => (float) ((clone $totalsQuery)->max('profit') ?? 0),
            'max_loss'   => (float) ((clone $totalsQuery)->min('profit') ?? 0),
            'count'      => (clone $totalsQuery)->count(),
        ];

        $monthlyQuery = $isAdmin
            ? ($filterUserId ? Order::where('user_id', $filterUserId) : Order::query())
            : $user->orders();

        if ($dateFrom) {
            $monthlyQuery->where('order_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $monthlyQuery->where('order_date', '<=', $dateTo);
        }

        $monthlyProfit = $monthlyQuery
            ->orderBy('order_date')
            ->get(['order_date', 'profit'])
            ->groupBy(fn ($o) => $o->order_date->format('M Y'))
            ->map(fn ($group) => round((float) $group->sum('profit'), 2))
            ->take(-12);

        $clients = $isAdmin ? \App\Models\User::orderBy('name')->get(['id', 'name']) : collect();

        $currency = $user->currency;
        $currencySymbol = CurrencyHelper::getSymbol($currency);

        return view('profit-loss.index', compact('totals', 'monthlyProfit', 'orders', 'isAdmin', 'clients', 'currency', 'currencySymbol'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\CurrencyHelper;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $isAdmin = $user->isAdmin();
        $canManageOrders = $user->canManageOrders();

        // Admins can filter by client; default to all orders
        $filterUserId = $request->get('user_id');

        $baseQuery = $this->buildFilteredOrdersQuery($request, $user, $isAdmin, $filterUserId);

        $orders = $baseQuery->paginate(25)->withQueryString();

        $totalsQuery = $isAdmin
            ? ($filterUserId ? Order::where('user_id', $filterUserId) : Order::query())
            : $user->orders();

        if ($dateFrom = $request->get('date_from')) {
            $totalsQuery->where('order_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $totalsQuery->where('order_date', '<=', $dateTo);
        }

        $totals = [
            'revenue' => (float) (clone $totalsQuery)->sum('ebay_receipts'),
            'cost'    => (float) (clone $totalsQuery)->sum('amazon_cost'),
            'profit'  => (float) (clone $totalsQuery)->sum('profit'),
            'count'   => (clone $totalsQuery)->count(),
        ];

        $clients = $isAdmin ? User::where('role', 'client')->orderBy('name')->get(['id', 'name']) : collect();

        $currency = $user->currency;
        $currencySymbol = CurrencyHelper::getSymbol($currency);

        return view('orders.index', compact('orders', 'totals', 'isAdmin', 'clients', 'currency', 'currencySymbol', 'canManageOrders'));
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $filterUserId = $request->get('user_id');

        $orders = $this->buildFilteredOrdersQuery($request, $user, $isAdmin, $filterUserId)->get();

        $filename = 'orders-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($orders, $isAdmin) {
            $handle = fopen('php://output', 'w');

            $headers = [
                'Order Date',
                'Buyer Name',
                'eBay Order No',
                'Amazon Order No',
                'Status',
                'Amazon Cost',
                'eBay Receipts',
                'Profit',
                'ROI',
                'Note',
            ];

            if ($isAdmin) {
                array_splice($headers, 1, 0, ['User']);
            }

            fputcsv($handle, $headers);

            foreach ($orders as $order) {
                $row = [
                    optional($order->order_date)->format('Y-m-d'),
                    $order->buyer_name,
                    $order->ebay_order_no,
                    $order->amazon_order_no,
                    $order->status,
                    number_format((float) $order->amazon_cost, 2, '.', ''),
                    number_format((float) $order->ebay_receipts, 2, '.', ''),
                    number_format((float) $order->profit, 2, '.', ''),
                    number_format((float) $order->roi, 2, '.', ''),
                    $order->note,
                ];

                if ($isAdmin) {
                    array_splice($row, 1, 0, [$order->user->name ?? '']);
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create()
    {
        $this->authorize('create', Order::class);

        $user = auth()->user();

        return view('orders.create', [
            'isAdmin' => $user->isAdmin(),
            'clients' => $user->isAdmin()
                ? User::where('role', 'client')->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Order::class);

        $data = $this->validateOrder($request);

        $data['profit'] = $data['ebay_receipts'] - $data['amazon_cost'];
        $data['roi']    = $data['amazon_cost'] > 0
            ? round(($data['profit'] / $data['amazon_cost']) * 100, 2)
            : 0;

        $targetUser = auth()->user();

        if (auth()->user()->isAdmin() && $request->filled('user_id')) {
            $targetUser = User::findOrFail($request->integer('user_id'));
        }

        $targetUser->orders()->create($data);

        return redirect()->to($this->resolveReturnTo($request))->with('success', 'Order added successfully.');
    }

    public function edit(Order $order)
    {
        $this->authorize('update', $order);

        $user = auth()->user();

        return view('orders.edit', [
            'order' => $order,
            'isAdmin' => $user->isAdmin(),
            'clients' => $user->isAdmin()
                ? User::where('role', 'client')->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $data = $this->validateOrder($request, $order);

        $data['profit'] = $data['ebay_receipts'] - $data['amazon_cost'];
        $data['roi']    = $data['amazon_cost'] > 0
            ? round(($data['profit'] / $data['amazon_cost']) * 100, 2)
            : 0;

        $order->update($data);

        return redirect()->to($this->resolveReturnTo($request))->with('success', 'Order updated.');
    }

    public function destroy(Order $order)
    {
        $this->authorize('delete', $order);

        $order->delete();

        return redirect()->to($this->resolveReturnTo(request()))->with('success', 'Order deleted.');
    }

    public function import(Request $request)
    {
        abort_unless(auth()->user()->canManageOrders(), 403);

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'user_id'  => ['nullable', 'exists:users,id'],
        ]);

        $user = auth()->user();

        // Admins can import for any user; non-admins import for themselves
        if ($user->isAdmin() && $request->filled('user_id')) {
            $targetUser = User::findOrFail($request->integer('user_id'));
        } else {

            if (auth()->user()->isAdmin() && $request->filled('user_id')) {
                $data['user_id'] = User::findOrFail($request->integer('user_id'))->id;
            } else {
                unset($data['user_id']);
            }
            $targetUser = $user;
        }

        $path   = $request->file('csv_file')->getRealPath();
        $handle = fopen($path, 'r');

        fgetcsv($handle); // header row 1
        fgetcsv($handle); // header row 2 (sub-labels)

        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 6) {
                continue;
            }

            $date     = trim($row[0] ?? '');
            $buyer    = trim($row[1] ?? '');
            $ebayNo   = preg_replace('/\s+/', '', trim($row[2] ?? ''));
            $amazonNo = trim($row[3] ?? '');
            $note     = trim($row[4] ?? '');
            $status   = trim($row[5] ?? '');
            $cost     = (float) trim($row[6] ?? 0);
            $receipts = (float) trim($row[7] ?? 0);

            if (empty($date) || empty($buyer) || ! strtotime($date)) {
                continue;
            }

            $status = match (strtolower($status)) {
                'delivered'    => 'Delivered',
                'order placed' => 'Order Placed',
                'refunded'     => 'Refunded',
                'out of stock' => 'Out of Stock',
                default        => 'Order Placed',
            };

            $profit   = $receipts - $cost;
            $roi      = $cost > 0 ? round(($profit / $cost) * 100, 2) : 0;
            $amazonNo = strlen($amazonNo) > 500 ? substr($amazonNo, 0, 500) : $amazonNo;

            Order::create([
                'user_id'         => $targetUser->id,
                'order_date'      => date('Y-m-d', strtotime($date)),
                'buyer_name'      => $buyer,
                'ebay_order_no'   => $ebayNo ?: null,
                'amazon_order_no' => $amazonNo ?: null,
                'note'            => $note ?: null,
                'status'          => $status,
                'amazon_cost'     => $cost,
                'ebay_receipts'   => $receipts,
                'profit'          => $profit,
                'roi'             => $roi,
            ]);

            $imported++;
        }

        fclose($handle);

        return redirect()->route('orders.index')->with('success', "Imported {$imported} orders for {$targetUser->name}.");
    }

    private function validateOrder(Request $request, ?Order $order = null): array
    {
        try {
            return $request->validate([
                'user_id'         => 'nullable|exists:users,id',
                'order_date'      => 'required|date',
                'buyer_name'      => 'required|string|max:255',
                'ebay_order_no'   => 'nullable|string|max:255',
                'amazon_order_no' => 'nullable|string|max:500',
                'note'            => 'nullable|string',
                'status'          => 'required|string',
                'amazon_cost'     => 'required|numeric|min:0',
                'ebay_receipts'   => 'required|numeric|min:0',
            ]);
        } catch (ValidationException $exception) {
            $bag = $order ? 'orderUpdate'.$order->id : 'orderStore';

            throw $exception->errorBag($bag)->redirectTo(url()->previous());
        }
    }

    private function resolveReturnTo(Request $request): string
    {
        return $request->string('return_to')->toString() ?: route('orders.index');
    }

    private function buildFilteredOrdersQuery(Request $request, User $user, bool $isAdmin, mixed $filterUserId)
    {
        $query = $isAdmin
            ? Order::with('user')->orderBy('order_date', 'desc')
            : $user->orders()->orderBy('order_date', 'desc');

        if ($isAdmin && $filterUserId) {
            $query->where('user_id', $filterUserId);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('buyer_name', 'like', "%{$search}%")
                    ->orWhere('ebay_order_no', 'like', "%{$search}%")
                    ->orWhere('amazon_order_no', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->where('order_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->where('order_date', '<=', $dateTo);
        }

        return $query;
    }
}

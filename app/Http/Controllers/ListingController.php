<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Models\Listing;
use App\Models\User;

class ListingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $search = request('search');
        $status = request('status');
        $user = auth()->user();

        $listings = $user->listings()
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($subQuery) use ($searchTerm) {
                    $subQuery
                        ->where('ebay_url', 'like', "%{$searchTerm}%")
                        ->orWhere('amazon_url', 'like', "%{$searchTerm}%")
                        ->orWhere('notes', 'like', "%{$searchTerm}%");
                });
            })
            ->when($status, fn ($query, $selectedStatus) => $query->where('status', $selectedStatus))
            ->latest()
            ->paginate(14)
            ->withQueryString();

        $statuses = $user->listings()->select('status')->distinct()->orderBy('status')->pluck('status');

        return view('listings.index', [
            'listings' => $listings,
            'statuses' => $statuses,
            'search' => $search,
            'status' => $status,
            'clients' => $user->isAdmin() ? User::orderBy('name')->get(['id', 'name', 'email']) : collect(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('listings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreListingRequest $request)
    {
        auth()->user()->listings()->create($this->payloadWithMetrics($request->validated()));

        return redirect()->route('listings.index')->with('success', 'Listing created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Listing $listing)
    {
        return redirect()->route('listings.edit', $listing);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Listing $listing)
    {
        abort_unless($listing->user_id === auth()->id(), 403);

        return view('listings.edit', [
            'listing' => $listing,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateListingRequest $request, Listing $listing)
    {
        abort_unless($listing->user_id === auth()->id(), 403);

        $listing->update($this->payloadWithMetrics($request->validated()));

        return redirect()->route('listings.index')->with('success', 'Listing updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Listing $listing)
    {
        abort_unless($listing->user_id === auth()->id(), 403);

        $listing->delete();

        return redirect()->route('listings.index')->with('success', 'Listing removed.');
    }

    private function payloadWithMetrics(array $payload): array
    {
        $ebayPrice = (float) ($payload['ebay_price'] ?? 0);
        $amazonPrice = (float) ($payload['amazon_price'] ?? 0);
        $ebayFee = (float) ($payload['ebay_fee'] ?? 0);

        $profit = $ebayPrice - $amazonPrice - $ebayFee;
        $roi = $amazonPrice > 0 ? ($profit / $amazonPrice) * 100 : 0;

        $payload['ebay_fee'] = number_format($ebayFee, 2, '.', '');
        $payload['profit'] = number_format($profit, 2, '.', '');
        $payload['roi'] = number_format($roi, 2, '.', '');

        return $payload;
    }
}

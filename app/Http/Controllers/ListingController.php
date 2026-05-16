<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ListingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $user = auth()->user();
        $perPage = $this->resolvePerPage($request);

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
            ->paginate($perPage)
            ->withQueryString();

        $statuses = $user->listings()->select('status')->distinct()->orderBy('status')->pluck('status');

        return view('listings.index', [
            'listings' => $listings,
            'statuses' => $statuses,
            'search' => $search,
            'status' => $status,
            'perPage' => $perPage,
            'clients' => $user->isAdmin() ? User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email']) : collect(),
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
    public function store(Request $request)
    {
        $validated = $this->validateListing($request);

        auth()->user()->listings()->create($this->payloadWithMetrics($validated));

        return redirect()->to($this->resolveReturnTo($request))->with('success', 'Listing created.');
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
    public function update(Request $request, Listing $listing)
    {
        abort_unless($listing->user_id === auth()->id(), 403);

        $validated = $this->validateListing($request, $listing);

        $listing->update($this->payloadWithMetrics($validated));

        return redirect()->to($this->resolveReturnTo($request))->with('success', 'Listing updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Listing $listing)
    {
        abort_unless($listing->user_id === auth()->id(), 403);

        $listing->delete();

        return redirect()->to($this->resolveReturnTo(request()))->with('success', 'Listing removed.');
    }

    public function bulkPriceUpdate(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'listing_ids' => ['required', 'array', 'min:1'],
            'listing_ids.*' => ['integer', Rule::exists('listings', 'id')->where('user_id', $user->id)],
            'percentage' => ['required', 'numeric', 'between:-100,1000'],
        ]);

        $user->listings()
            ->whereIn('id', $validated['listing_ids'])
            ->get()
            ->each(fn (Listing $listing) => $this->applyPriceAdjustment($listing, (float) $validated['percentage']));

        return redirect()->to($this->resolveReturnTo($request))
            ->with('success', 'Selected listings updated.');
    }

    public function adjustPrice(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'percentage' => ['required', 'numeric', 'between:-100,1000'],
        ]);

        $this->applyPriceAdjustment($listing, (float) $validated['percentage']);

        return redirect()->to($this->resolveReturnTo($request))
            ->with('success', 'Listing price updated.');
    }

    private function validateListing(Request $request, ?Listing $listing = null): array
    {
        try {
            return $request->validate($this->rulesFor($request, $listing));
        } catch (ValidationException $exception) {
            $bag = $listing ? 'listingUpdate'.$listing->id : 'listingStore';

            throw $exception->errorBag($bag)->redirectTo(url()->previous());
        }
    }

    private function rulesFor(Request $request, ?Listing $listing = null): array
    {
        return [
            'image_url' => ['nullable', 'url', 'max:2048'],
            'ebay_url' => [
                'required',
                'url',
                'max:2048',
                Rule::unique('listings', 'ebay_url')
                    ->where('user_id', $request->user()->id)
                    ->ignore($listing?->id),
            ],
            'amazon_url' => ['nullable', 'url', 'max:2048'],
            'ebay_price' => ['required', 'numeric', 'min:0'],
            'amazon_price' => ['required', 'numeric', 'min:0'],
            'ebay_fee' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:40'],
            'listed_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 25);

        return in_array($perPage, [25, 50, 75, 100], true) ? $perPage : 25;
    }

    private function resolveReturnTo(Request $request): string
    {
        return $request->string('return_to')->toString() ?: route('listings.index');
    }

    private function applyPriceAdjustment(Listing $listing, float $percentage): void
    {
        $multiplier = 1 + ($percentage / 100);

        $payload = [
            'image_url' => $listing->image_url,
            'adjustment_percentage' => $percentage,
            'ebay_url' => $listing->ebay_url,
            'amazon_url' => $listing->amazon_url,
            'ebay_price' => round(((float) $listing->ebay_price) * $multiplier, 2),
            'amazon_price' => (float) $listing->amazon_price,
            'ebay_fee' => (float) $listing->ebay_fee,
            'status' => $listing->status,
            'listed_on' => optional($listing->listed_on)->format('Y-m-d'),
            'notes' => $listing->notes,
        ];

        $listing->update($this->payloadWithMetrics($payload));
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

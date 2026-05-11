<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\Request;
use SplFileObject;

class ImportController extends Controller
{
    public function create()
    {
        $clients = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.import.create', ['clients' => $clients]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $user = User::findOrFail($request->integer('user_id'));

        $path = $request->file('csv_file')->getRealPath();
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $isFirst = true;
        $imported = 0;
        $skipped = 0;

        foreach ($file as $row) {
            if (! is_array($row) || empty(array_filter($row, fn ($cell) => $cell !== null && $cell !== ''))) {
                continue;
            }

            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            $ebayUrl = trim((string) ($row[0] ?? ''));
            if ($ebayUrl === '') {
                $skipped++;
                continue;
            }

            $amazonUrl  = trim((string) ($row[1] ?? ''));
            $ebayPrice  = $this->toNumber($row[2] ?? 0) ?? 0;
            $amazonPrice = $this->toNumber($row[3] ?? 0) ?? 0;
            $ebayFee    = $this->toNumber($row[4] ?? 0) ?? 0;
            $csvProfit  = $this->toNumber($row[5] ?? null);
            $csvRoi     = $this->toNumber($row[6] ?? null);
            $statusRaw  = trim((string) ($row[7] ?? ''));

            $profit = $csvProfit ?? ($ebayPrice - $amazonPrice - $ebayFee);
            $roi    = $csvRoi ?? ($amazonPrice > 0 ? ($profit / $amazonPrice) * 100 : 0);

            Listing::updateOrCreate(
                ['user_id' => $user->id, 'ebay_url' => $ebayUrl],
                [
                    'amazon_url'   => $amazonUrl !== '' ? $amazonUrl : null,
                    'ebay_price'   => $ebayPrice,
                    'amazon_price' => $amazonPrice,
                    'ebay_fee'     => round($ebayFee, 2),
                    'profit'       => round($profit, 2),
                    'roi'          => round($roi, 2),
                    'status'       => $statusRaw !== '' && $statusRaw !== '-' ? $statusRaw : 'research',
                ]
            );

            $imported++;
        }

        return redirect()->route('admin.import.create')
            ->with('success', "Imported {$imported} listings to {$user->name}" . ($skipped ? " ({$skipped} rows skipped)." : '.'));
    }

    public function copyToUsers(Request $request, Listing $listing)
    {
        $request->validate([
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $copied  = 0;
        $updated = 0;

        foreach ($request->input('user_ids') as $userId) {
            // Don't copy to the listing's own owner (already exists)
            if ((int) $userId === $listing->user_id) {
                continue;
            }

            $exists = Listing::where('user_id', $userId)
                ->where('ebay_url', $listing->ebay_url)
                ->exists();

            Listing::updateOrCreate(
                ['user_id' => $userId, 'ebay_url' => $listing->ebay_url],
                [
                    'amazon_url'   => $listing->amazon_url,
                    'ebay_price'   => $listing->ebay_price,
                    'amazon_price' => $listing->amazon_price,
                    'ebay_fee'     => $listing->ebay_fee,
                    'profit'       => $listing->profit,
                    'roi'          => $listing->roi,
                    'status'       => $listing->status,
                    'listed_on'    => $listing->listed_on,
                    'notes'        => $listing->notes,
                ]
            );

            $exists ? $updated++ : $copied++;
        }

        return redirect()->route('listings.index')
            ->with('success', "Listing copied to {$copied} user(s)" . ($updated ? ", updated for {$updated} existing." : '.'));
    }

    private function toNumber(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        $value = trim((string) $raw);
        if ($value === '' || $value === '-') {
            return null;
        }

        $normalized = str_replace(['$', '£', '%', ','], '', $value);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}

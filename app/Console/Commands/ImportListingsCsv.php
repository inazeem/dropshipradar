<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use SplFileObject;

#[Signature('listings:import {path : Absolute path to csv file} {--user= : User ID or email to assign imported listings to}')]
#[Description('Import listing rows from a CSV file into the listings table')]
class ImportListingsCsv extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        // Resolve user
        $userOption = $this->option('user');
        if ($userOption) {
            $user = is_numeric($userOption)
                ? User::find((int) $userOption)
                : User::where('email', $userOption)->first();

            if (! $user) {
                $this->error("User not found: {$userOption}");

                return self::FAILURE;
            }
        } else {
            $user = User::where('role', 'admin')->first() ?? User::first();
            if (! $user) {
                $this->error('No users exist. Create a user first.');

                return self::FAILURE;
            }
            $this->info("No --user supplied; assigning listings to {$user->email} (id={$user->id}).");
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $isFirst = true;
        $imported = 0;

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
                continue;
            }

            $amazonUrl = trim((string) ($row[1] ?? ''));
            $ebayPrice = $this->toNumber($row[2] ?? 0) ?? 0;
            $amazonPrice = $this->toNumber($row[3] ?? 0) ?? 0;
            $ebayFee = $this->toNumber($row[4] ?? 0) ?? 0;
            $csvProfit = $this->toNumber($row[5] ?? null);
            $csvRoi = $this->toNumber($row[6] ?? null);
            $statusRaw = trim((string) ($row[7] ?? ''));

            $profit = $csvProfit ?? ($ebayPrice - $amazonPrice - $ebayFee);
            $roi = $csvRoi ?? ($amazonPrice > 0 ? ($profit / $amazonPrice) * 100 : 0);

            Listing::updateOrCreate(
                ['user_id' => $user->id, 'ebay_url' => $ebayUrl],
                [
                    'amazon_url' => $amazonUrl !== '' ? $amazonUrl : null,
                    'ebay_price' => $ebayPrice,
                    'amazon_price' => $amazonPrice,
                    'ebay_fee' => $ebayFee,
                    'profit' => round($profit, 2),
                    'roi' => round($roi, 2),
                    'status' => $statusRaw !== '' && $statusRaw !== '-' ? $statusRaw : 'research',
                ]
            );

            $imported++;
        }

        $this->info("Imported {$imported} listing rows.");

        return self::SUCCESS;
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

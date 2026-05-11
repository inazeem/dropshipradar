<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;

class ImportOrders extends Command
{
    protected $signature = 'orders:import {file : Absolute path to the CSV file} {--user= : User ID to assign orders to}';

    protected $description = 'Import orders from the dropshipping tracking CSV';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $userId = $this->option('user');
        if ($userId) {
            $user = User::findOrFail($userId);
        } else {
            $user = User::where('role', '!=', 'admin')->first() ?? User::first();
        }

        if (! $user) {
            $this->error('No user found. Create a user first.');
            return self::FAILURE;
        }

        $this->info("Importing orders for user: {$user->name} (ID {$user->id})");

        $handle   = fopen($path, 'r');
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
                'user_id'         => $user->id,
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

        $this->info("Done. Imported {$imported} orders.");
        return self::SUCCESS;
    }
}

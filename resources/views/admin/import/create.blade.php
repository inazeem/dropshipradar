<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-rose-300/80">Admin</p>
                <h2 class="font-display text-2xl leading-tight text-white">Import Listings</h2>
            </div>
            <a href="{{ route('admin.users.index') }}"
               class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">
                ← Users
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-5">

            @if (session('success'))
                <div class="rounded-lg border border-emerald-300/25 bg-emerald-400/15 px-4 py-3 text-emerald-100 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-rose-300/25 bg-rose-400/15 px-4 py-3 text-rose-100 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="glass-card p-6 space-y-6">
                <div class="space-y-1">
                    <h3 class="font-display text-lg text-white">Upload Listings CSV</h3>
                    <p class="text-sm text-slate-400">
                        Upload a CSV file in the same format as the listings table.
                        Existing eBay URLs will be updated; new ones will be created.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.import.store') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    {{-- File upload --}}
                    <div class="space-y-1.5">
                        <label for="csv_file" class="block text-sm font-medium text-slate-300">
                            CSV File <span class="text-rose-400">*</span>
                        </label>

                        <label for="csv_file"
                               class="flex flex-col items-center justify-center gap-3 w-full rounded-xl border-2 border-dashed border-white/20 bg-slate-900/40 px-6 py-10 cursor-pointer hover:border-cyan-400/50 hover:bg-slate-900/60 transition"
                               x-data="{ fileName: '' }"
                               x-on:change="fileName = $event.target.files[0]?.name ?? ''">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                            <span class="text-sm text-slate-400" x-text="fileName || 'Click to browse or drag & drop a .csv file'"></span>
                            <input id="csv_file" name="csv_file" type="file" accept=".csv,text/csv" class="sr-only" required>
                        </label>
                    </div>

                    {{-- CSV format hint --}}
                    <details class="rounded-lg border border-white/10 bg-slate-900/30 px-4 py-3 text-xs text-slate-400 cursor-pointer">
                        <summary class="font-semibold text-slate-300 select-none">Expected CSV column order</summary>
                        <div class="mt-3 overflow-x-auto">
                            <table class="min-w-full text-left">
                                <thead class="text-slate-500 border-b border-white/10">
                                    <tr>
                                        <th class="pr-4 pb-1">#</th>
                                        <th class="pr-4 pb-1">Column</th>
                                        <th class="pb-1">Required?</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach([
                                        [1, 'eBay URL',       'Yes'],
                                        [2, 'Amazon URL',     'No'],
                                        [3, 'eBay Price',     'Yes'],
                                        [4, 'Amazon Price',   'Yes'],
                                        [5, 'eBay Fee',       'No'],
                                        [6, 'Profit',         'No (calculated if blank)'],
                                        [7, 'ROI %',          'No (calculated if blank)'],
                                        [8, 'Status',         'No (defaults to "research")'],
                                    ] as [$n, $col, $req])
                                    <tr>
                                        <td class="pr-4 py-1 text-slate-500">{{ $n }}</td>
                                        <td class="pr-4 py-1">{{ $col }}</td>
                                        <td class="py-1">{{ $req }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                            class="rounded-lg bg-cyan-400/90 px-6 py-2.5 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">
                            Import Listings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

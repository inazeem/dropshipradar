<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Currency Preference') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Choose your preferred currency for displaying prices and profits.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.currency') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="currency" :value="__('Currency')" />
            <select id="currency" name="currency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">-- Select Currency --</option>
                <option value="GBP" {{ old('currency', $user->currency) === 'GBP' ? 'selected' : '' }}>British Pound (£)</option>
                <option value="USD" {{ old('currency', $user->currency) === 'USD' ? 'selected' : '' }}>US Dollar ($)</option>
                <option value="EUR" {{ old('currency', $user->currency) === 'EUR' ? 'selected' : '' }}>Euro (€)</option>
                <option value="JPY" {{ old('currency', $user->currency) === 'JPY' ? 'selected' : '' }}>Japanese Yen (¥)</option>
                <option value="CAD" {{ old('currency', $user->currency) === 'CAD' ? 'selected' : '' }}>Canadian Dollar (C$)</option>
                <option value="AUD" {{ old('currency', $user->currency) === 'AUD' ? 'selected' : '' }}>Australian Dollar (A$)</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('currency')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'currency-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >
                    {{ __('Currency preference updated.') }}
                </p>
            @endif
        </div>
    </form>
</section>

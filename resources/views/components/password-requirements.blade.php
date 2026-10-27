{{--
    Wraps a password input and shows a live checklist of the password rules.
    Keep the rules in sync with Password::defaults() in AppServiceProvider.
--}}
@php
    $requirements = [
        ['test' => 'password.length >= 8', 'label' => __('At least 8 characters')],
        ['test' => '/[A-Z]/.test(password)', 'label' => __('One uppercase letter')],
        ['test' => '/[0-9]/.test(password)', 'label' => __('One number')],
    ];
@endphp

<div {{ $attributes }} x-data="{ password: '' }" x-on:input="if ($event.target.matches('input')) password = $event.target.value">
    {{ $slot }}

    <ul class="mt-3 space-y-1 text-sm" aria-live="polite" data-test="password-requirements">
        @foreach ($requirements as $requirement)
            <li class="flex items-center gap-2" x-bind:class="{{ $requirement['test'] }} ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400'">
                <flux:icon.check-circle variant="mini" class="size-4" x-show="{{ $requirement['test'] }}" x-cloak />
                <flux:icon.minus-circle variant="mini" class="size-4" x-show="! ({{ $requirement['test'] }})" />
                <span>{{ $requirement['label'] }}</span>
            </li>
        @endforeach
    </ul>
</div>

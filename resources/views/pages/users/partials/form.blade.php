@php
    $passwordRequired = $passwordRequired ?? true;
    $roleDisabled = $roleDisabled ?? false;
@endphp

<div class="grid gap-6 sm:grid-cols-2">
    <flux:input wire:model="name" :label="__('Name')" type="text" required autocomplete="name" />

    <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

    <flux:select wire:model="role" :label="__('Role')" :disabled="$roleDisabled" :description="$roleDisabled ? __('The last admin must remain an admin.') : null">
        @foreach ($roles as $value => $label)
            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>

    <x-password-requirements>
        <flux:input
            wire:model="password"
            :label="$passwordRequired ? __('Password') : __('New password')"
            type="password"
            :required="$passwordRequired"
            :description="$passwordRequired ? null : __('Leave blank to keep the current password.')"
            autocomplete="new-password"
            viewable
        />
    </x-password-requirements>
</div>

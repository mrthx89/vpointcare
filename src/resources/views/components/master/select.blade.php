@props(['label', 'model'])

<label class="block">
    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</span>
    <select
        wire:model="{{ $model }}"
        class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"
    >
        {{ $slot }}
    </select>
    @error($model)
        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
    @enderror
</label>

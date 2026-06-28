@props(['title', 'value'])

<div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $title }}</div>
    <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $value }}</div>
</div>

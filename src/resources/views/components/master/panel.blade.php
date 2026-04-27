@props(['title'])

<section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="border-b border-gray-200 px-4 py-3 font-semibold text-gray-950 dark:border-gray-800 dark:text-white">
        {{ $title }}
    </div>
    <div class="p-4">
        {{ $slot }}
    </div>
</section>

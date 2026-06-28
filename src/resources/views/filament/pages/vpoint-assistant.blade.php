<x-filament-panels::page>
    <div
        x-data="{ scrollToBottom() { this.$nextTick(() => { const el = this.$refs.chatArea; if (el) el.scrollTop = el.scrollHeight }) } }"
        x-init="scrollToBottom()"
        x-effect="scrollToBottom()"
        class="flex h-[calc(100dvh-10.5rem)] min-h-0 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
    >
        <section
            x-ref="chatArea"
            class="min-h-0 flex-1 overflow-y-auto bg-gray-50 p-4 dark:bg-gray-950 sm:p-5"
        >
            @if (empty($messages))
                <div class="flex min-h-full flex-col items-center justify-center px-4 text-center text-gray-500 dark:text-gray-400">
                    <div class="mb-4 rounded-full bg-primary-50 p-4 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                        <x-heroicon-o-chat-bubble-bottom-center-text class="h-12 w-12" />
                    </div>
                    <div class="text-lg font-semibold text-gray-950 dark:text-white">{{ __('ui.chatbot.empty_title') }}</div>
                    <div class="mt-2 max-w-xl text-sm leading-6">{{ __('ui.chatbot.empty_description') }}</div>
                </div>
            @endif

            <div class="space-y-3">
                @foreach ($messages as $message)
                    <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div
                            class="max-w-[min(42rem,82%)] rounded-2xl px-4 py-3 text-sm shadow-sm {{ $message['role'] === 'user' ? 'rounded-br-md bg-primary-600 text-white' : 'rounded-bl-md border border-gray-200 bg-white text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100' }} {{ ! empty($message['error']) ? 'border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300' : '' }}"
                        >
                            <div class="whitespace-pre-wrap leading-6">{{ $message['content'] }}</div>

                            @if (! empty($message['knowledge']))
                                <div class="mt-3 border-t border-gray-200 pt-2 text-xs opacity-75 dark:border-gray-700">
                                    {{ __('ui.chatbot.knowledge_used', ['knowledge' => implode(', ', $message['knowledge'])]) }}
                                </div>
                            @endif

                            <div class="mt-2 text-right text-[10px] opacity-60">{{ $message['time'] }}</div>
                        </div>
                    </div>
                @endforeach

                @if ($isTyping)
                    <div class="flex justify-start">
                        <div class="rounded-2xl rounded-bl-md border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <span>{{ __('ui.chatbot.typing') }}</span>
                                <span class="flex gap-1">
                                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400"></span>
                                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:0.1s]"></span>
                                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:0.2s]"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <form wire:submit.prevent="sendMessage" class="flex shrink-0 flex-col gap-2 border-t border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900 sm:flex-row sm:items-center sm:p-4">
            <x-filament::button
                type="button"
                color="gray"
                icon="heroicon-o-trash"
                wire:click="clearHistory"
                wire:confirm="{{ __('ui.chatbot.clear_confirm') }}"
                outlined
            >
                {{ __('ui.chatbot.clear_history') }}
            </x-filament::button>

            <div class="fi-input-wrp flex min-w-0 flex-1 overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus-within:ring-2 focus-within:ring-primary-600 dark:bg-white/5 dark:ring-white/20 dark:focus-within:ring-primary-500">
                <div class="min-w-0 flex-1">
                    <input
                        type="text"
                        wire:model="userMessage"
                        placeholder="{{ __('ui.chatbot.placeholder') }}"
                        maxlength="2000"
                        autocomplete="off"
                        class="fi-input block w-full border-none bg-transparent px-3 py-2 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:placeholder:text-gray-500 sm:text-sm sm:leading-6"
                    />
                </div>
            </div>

            <x-filament::button
                type="submit"
                icon="heroicon-o-paper-airplane"
                wire:loading.attr="disabled"
                wire:target="sendMessage"
            >
                {{ __('ui.chatbot.send') }}
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page>

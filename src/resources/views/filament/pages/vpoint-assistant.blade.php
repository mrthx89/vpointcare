<x-filament-panels::page>
    <div
        x-data="{
            scroller: null,
            autoScroll: true,
            init() { this.scroller = this.$refs.chatArea; this.scrollToBottom(true); this.scroller?.addEventListener('scroll', () => { if (! this.scroller) return; const atBottom = this.scroller.scrollHeight - this.scroller.scrollTop - this.scroller.clientHeight < 80; this.autoScroll = atBottom; }); this.$watch('$wire.messages', () => this.scrollToBottom(false)); },
            scrollToBottom(force) { if (! this.scroller) return; if (force || this.autoScroll) { this.$nextTick(() => { this.scroller.scrollTop = this.scroller.scrollHeight }) } },
            copy(text) { navigator.clipboard?.writeText(text) },
            handlePaste(e) {
                const items = e.clipboardData?.items ? Array.from(e.clipboardData.items) : [];
                if (items.length === 0) return;
                const dt = new DataTransfer();
                let added = false;
                for (const item of items) {
                    if (item.kind !== 'file') continue;
                    const file = item.getAsFile();
                    if (file) { dt.items.add(file); added = true; }
                }
                if (! added) return;
                e.preventDefault();
                const target = this.$refs.fileInput;
                if (! target) return;
                target.files = dt.files;
                target.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }"
        class="relative flex h-[calc(100dvh-6rem)] min-h-0 flex-col overflow-hidden rounded-3xl bg-gray-50 dark:bg-gray-950"
    >
        <div class="pointer-events-none absolute inset-x-0 top-0 z-20 flex justify-center px-4 pt-3 sm:px-6">
            <div class="pointer-events-auto flex w-full max-w-3xl justify-end">
                @if (! empty($messages))
                    <button
                        type="button"
                        wire:click="clearHistory"
                        wire:confirm="{{ __('ui.chatbot.clear_confirm') }}"
                        class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white/90 px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm backdrop-blur transition hover:border-danger-200 hover:bg-danger-50 hover:text-danger-600 dark:border-gray-700 dark:bg-gray-900/90 dark:text-gray-300 dark:hover:border-danger-500/40 dark:hover:bg-danger-500/10 dark:hover:text-danger-300"
                    >
                        <x-heroicon-o-trash class="h-4 w-4" />
                        <span>{{ __('ui.chatbot.clear_history') }}</span>
                    </button>
                @endif
            </div>
        </div>

        <main x-ref="chatArea" class="min-h-0 flex-1 overflow-y-auto px-4 pb-72 pt-16 sm:px-6" x-on:paste.document="handlePaste($event)">
            <div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
                @if (empty($messages))
                    <div class="flex min-h-[60vh] flex-col items-center justify-center px-4 text-center text-gray-500 dark:text-gray-400">
                        <div class="mb-4 rounded-full bg-primary-50 p-4 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                            <x-heroicon-o-chat-bubble-bottom-center-text class="h-12 w-12" />
                        </div>
                        <div class="text-lg font-semibold text-gray-950 dark:text-white">{{ __('ui.chatbot.empty_title') }}</div>
                        <div class="mt-2 max-w-xl text-sm leading-6">{{ __('ui.chatbot.empty_description') }}</div>
                    </div>
                @endif

                @foreach ($messages as $index => $message)
                    <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="group relative max-w-[min(44rem,90%)] rounded-2xl px-4 py-3 text-sm shadow-sm {{ $message['role'] === 'user' ? 'rounded-br-md bg-primary-600 text-white' : 'rounded-bl-md border border-gray-200 bg-white text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100' }} {{ ! empty($message['error']) ? 'border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300' : '' }}">
                            @if ($message['role'] === 'assistant')
                                <div class="absolute right-2 top-2 flex gap-1 opacity-0 transition group-hover:opacity-100">
                                    <button type="button" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200" title="{{ __('ui.chatbot.copy') }}" x-on:click="copy(@js($message['content']))">
                                        <x-heroicon-o-clipboard-document class="h-4 w-4" />
                                    </button>
                                    @if (\App\Support\FilamentAccess::can(\App\Support\AccessPermissions::KNOWLEDGE_MANAGE) && empty($message['error']))
                                        <button type="button" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200" title="{{ __('ui.chatbot.create_draft') }}" wire:click="createKnowledgeDraft({{ $index }})">
                                            <x-heroicon-o-book-open class="h-4 w-4" />
                                        </button>
                                    @endif
                                </div>
                            @endif

                            @if ($message['role'] === 'assistant')
                                <div class="vpoint-ai-markdown pr-8 leading-6">
                                    {!! $this->renderMarkdown((string) $message['content']) !!}
                                </div>
                            @else
                                <div class="whitespace-pre-wrap leading-6">{{ $message['content'] }}</div>
                            @endif

                            @if (! empty($message['attachments']))
                                <div class="mt-3 flex flex-wrap gap-1 border-t border-white/20 pt-2 text-xs opacity-85 dark:border-gray-700">
                                    @foreach ($message['attachments'] as $attachment)
                                        <span class="rounded-full bg-black/10 px-2 py-1 dark:bg-white/10">{{ $attachment }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if (! empty($message['knowledge']))
                                <div class="mt-3 border-t border-gray-200 pt-2 text-xs opacity-75 dark:border-gray-700">
                                    {{ __('ui.chatbot.knowledge_used', ['knowledge' => implode(', ', $message['knowledge'])]) }}
                                </div>
                            @endif

                            <div class="mt-2 flex items-center justify-end gap-2 text-[10px] opacity-60">
                                @if (! empty($message['response_mode']))
                                    <span>{{ __('ui.chatbot.mode_'.$message['response_mode']) }}</span>
                                @endif
                                @if (! empty($message['knowledge_mode']))
                                    <span>{{ __('ui.chatbot.knowledge_'.$message['knowledge_mode']) }}</span>
                                @endif
                                <span>{{ $message['time'] }}</span>
                            </div>
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
        </main>

        <div class="pointer-events-none fixed inset-x-0 bottom-0 z-30 flex justify-center bg-gradient-to-t from-gray-50 via-gray-50/95 to-transparent px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-14 dark:from-gray-950 dark:via-gray-950/95 sm:px-6 sm:pb-5">
            <div class="pointer-events-auto w-full max-w-3xl">
                <div class="mx-auto flex flex-col gap-2 rounded-[1.65rem] border border-gray-200/80 bg-white/95 p-2 shadow-2xl shadow-gray-950/10 ring-1 ring-gray-950/5 backdrop-blur-xl dark:border-white/10 dark:bg-gray-900/95 dark:shadow-black/40 dark:ring-white/10">
                    @if (! empty($attachments))
                        <div class="flex flex-wrap gap-1 px-2 pt-1 text-xs text-gray-500 dark:text-gray-400">
                            @foreach ($attachments as $attachment)
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 dark:bg-gray-800">
                                    <x-heroicon-o-paper-clip class="h-3 w-3" />
                                    {{ $attachment->getClientOriginalName() }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if (! empty($suggestedReplies))
                        <div class="flex max-h-24 flex-wrap gap-1 overflow-y-auto px-2 pt-1">
                            @foreach ($suggestedReplies as $reply)
                                <button
                                    type="button"
                                    wire:click="useSuggestedReply(@js($reply))"
                                    class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-left text-xs font-medium text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800/70 dark:text-gray-200 dark:hover:border-primary-500/40 dark:hover:bg-primary-500/10 dark:hover:text-primary-200"
                                >
                                    {{ $reply }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <form wire:submit.prevent="sendMessage" class="flex w-full items-end gap-2">
                        <label class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100" title="{{ __('ui.chatbot.attach_file') }}">
                            <x-heroicon-o-paper-clip class="h-5 w-5" />
                            <input x-ref="fileInput" type="file" wire:model="attachments" multiple class="sr-only">
                        </label>

                        <textarea
                            wire:model="userMessage"
                            placeholder="{{ __('ui.chatbot.placeholder') }}"
                            maxlength="4000"
                            rows="1"
                            x-on:keydown.enter.prevent="$event.shiftKey ? ($event.target.value += '\n') : $wire.sendMessage()"
                            x-on:input="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight, Math.floor(window.innerHeight * 0.6))+'px'"
                            x-effect="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight, Math.floor(window.innerHeight * 0.6))+'px'"
                            wire:loading.attr="disabled"
                            wire:target="sendMessage"
                            class="block max-h-[60vh] min-h-10 flex-1 resize-none overflow-y-auto border-none bg-transparent px-1 py-2.5 text-sm leading-6 text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 disabled:cursor-wait disabled:text-gray-500 dark:text-white dark:placeholder:text-gray-500"
                        ></textarea>

                        <div x-data="{ open: false }" class="relative shrink-0">
                            <button type="button" x-on:click="open = ! open" x-on:click.outside="open = false" class="inline-flex h-10 items-center gap-1 rounded-full px-3 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
                                <span>{{ __('ui.chatbot.mode_'.$responseMode) }}</span>
                                <span class="hidden sm:inline">&middot; {{ __('ui.chatbot.knowledge_'.$knowledgeMode) }}</span>
                                <x-heroicon-m-chevron-down class="h-4 w-4" />
                            </button>

                            <div x-cloak x-show="open" x-transition.origin.bottom.right class="absolute bottom-12 right-0 w-72 overflow-hidden rounded-2xl border border-gray-200 bg-white p-2 text-sm shadow-2xl ring-1 ring-gray-950/5 dark:border-gray-700 dark:bg-gray-900 dark:ring-white/10">
                                <div class="px-2 pb-1 pt-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('ui.chatbot.response_mode') }}</div>
                                <button type="button" wire:click="$set('responseMode', 'light')" x-on:click="open = false" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-800">
                                    <span><span class="font-medium">{{ __('ui.chatbot.mode_light') }}</span><span class="block text-xs text-gray-500">{{ __('ui.chatbot.mode_light_desc') }}</span></span>
                                    @if ($responseMode === 'light') <x-heroicon-m-check class="h-5 w-5 text-success-500" /> @endif
                                </button>
                                <button type="button" wire:click="$set('responseMode', 'fast')" x-on:click="open = false" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-800">
                                    <span><span class="font-medium">{{ __('ui.chatbot.mode_fast') }}</span><span class="block text-xs text-gray-500">{{ __('ui.chatbot.mode_fast_desc') }}</span></span>
                                    @if ($responseMode === 'fast') <x-heroicon-m-check class="h-5 w-5 text-success-500" /> @endif
                                </button>

                                <div class="my-2 border-t border-gray-100 dark:border-gray-800"></div>
                                <div class="px-2 pb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('ui.chatbot.knowledge_mode') }}</div>
                                <button type="button" wire:click="$set('knowledgeMode', 'all')" x-on:click="open = false" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-800">
                                    <span><span class="font-medium">{{ __('ui.chatbot.knowledge_all') }}</span><span class="block text-xs text-gray-500">{{ __('ui.chatbot.knowledge_all_desc') }}</span></span>
                                    @if ($knowledgeMode === 'all') <x-heroicon-m-check class="h-5 w-5 text-success-500" /> @endif
                                </button>
                                <button type="button" wire:click="$set('knowledgeMode', 'none')" x-on:click="open = false" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-800">
                                    <span><span class="font-medium">{{ __('ui.chatbot.knowledge_none') }}</span><span class="block text-xs text-gray-500">{{ __('ui.chatbot.knowledge_none_desc') }}</span></span>
                                    @if ($knowledgeMode === 'none') <x-heroicon-m-check class="h-5 w-5 text-success-500" /> @endif
                                </button>
                            </div>
                        </div>

                        <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage,attachments" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-600 text-white shadow-sm transition hover:bg-primary-500 disabled:cursor-wait disabled:opacity-50">
                            <x-heroicon-m-paper-airplane wire:loading.remove wire:target="sendMessage,attachments" class="h-5 w-5" />
                            <svg wire:loading wire:target="sendMessage,attachments" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                            </svg>
                        </button>
                    </form>

                    <div wire:loading wire:target="sendMessage" class="px-3 text-center text-[11px] font-medium text-primary-500 dark:text-primary-300">
                        {{ __('ui.chatbot.typing') }}
                    </div>

                    <div class="px-3 pb-0.5 text-center text-[11px] text-gray-400 dark:text-gray-500">
                        {{ __('ui.chatbot.paste_hint') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak]{display:none!important}
        .vpoint-ai-markdown :where(h1,h2,h3){font-weight:700;margin:.65rem 0 .35rem}.vpoint-ai-markdown h1{font-size:1.15rem}.vpoint-ai-markdown h2{font-size:1.05rem}.vpoint-ai-markdown h3{font-size:1rem}.vpoint-ai-markdown p{margin:.45rem 0}.vpoint-ai-markdown ul,.vpoint-ai-markdown ol{margin:.45rem 0 .45rem 1.25rem}.vpoint-ai-markdown ul{list-style:disc}.vpoint-ai-markdown ol{list-style:decimal}.vpoint-ai-markdown code{border-radius:.35rem;background:rgba(148,163,184,.22);padding:.1rem .3rem;font-size:.86em}.vpoint-ai-markdown pre{margin:.7rem 0;overflow:auto;border-radius:.85rem;background:#020617;color:#e2e8f0;padding:1rem}.vpoint-ai-markdown pre code{background:transparent;padding:0;color:inherit}.vpoint-ai-markdown table{margin:.7rem 0;width:100%;border-collapse:collapse;font-size:.9em}.vpoint-ai-markdown th,.vpoint-ai-markdown td{border:1px solid rgba(148,163,184,.35);padding:.45rem}.vpoint-ai-markdown blockquote{border-left:3px solid rgba(99,102,241,.65);padding-left:.8rem;color:#64748b}
    </style>
</x-filament-panels::page>

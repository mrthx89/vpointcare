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
        class="flex h-full min-h-0 flex-col"
    >
        <section x-ref="chatArea" class="min-h-0 flex-1 overflow-y-auto bg-gray-50 px-4 py-6 dark:bg-gray-950 sm:px-6" x-on:paste.document="handlePaste($event)">
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
        </section>

        <div class="shrink-0 border-t border-gray-200 bg-white px-4 pb-4 pt-3 dark:border-gray-800 dark:bg-gray-900 sm:px-6">
            <div class="mx-auto w-full max-w-3xl">
                <form wire:submit.prevent="sendMessage" class="flex flex-col gap-2">
                    @if (! empty($attachments))
                        <div class="flex flex-wrap gap-1 text-xs text-gray-500 dark:text-gray-400">
                            @foreach ($attachments as $attachment)
                                <span class="rounded-full bg-gray-100 px-2 py-1 dark:bg-gray-800">{{ $attachment->getClientOriginalName() }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex items-end gap-2 rounded-2xl border border-gray-200 bg-white p-2 shadow-sm focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/30 dark:border-gray-700 dark:bg-gray-900">
                        <label class="inline-flex cursor-pointer items-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800" title="{{ __('ui.chatbot.attach_file') }}">
                            <x-heroicon-o-paper-clip class="h-5 w-5" />
                            <input x-ref="fileInput" type="file" wire:model="attachments" multiple class="sr-only">
                        </label>

                        <textarea
                            wire:model="userMessage"
                            placeholder="{{ __('ui.chatbot.placeholder') }}"
                            maxlength="4000"
                            rows="1"
                            x-on:keydown.enter.prevent="$event.shiftKey ? $event.target.value += '\n' : $wire.sendMessage()"
                            x-on:input="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight, 180)+'px'"
                            x-effect="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight, 180)+'px'"
                            class="block max-h-[180px] min-h-10 flex-1 resize-none border-none bg-transparent px-1 py-2 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 dark:text-white dark:placeholder:text-gray-500"
                        ></textarea>

                        <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage,attachments" class="inline-flex items-center justify-center rounded-xl bg-primary-600 p-2 text-white shadow-sm transition hover:bg-primary-500 disabled:opacity-50">
                            <x-heroicon-m-paper-airplane class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <label class="font-medium text-gray-700 dark:text-gray-200">{{ __('ui.chatbot.response_mode') }}</label>
                        <select wire:model="responseMode" class="rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                            <option value="light">{{ __('ui.chatbot.mode_light') }}</option>
                            <option value="fast">{{ __('ui.chatbot.mode_fast') }}</option>
                        </select>
                        <label class="ml-2 font-medium text-gray-700 dark:text-gray-200">{{ __('ui.chatbot.knowledge_mode') }}</label>
                        <select wire:model="knowledgeMode" class="rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                            <option value="all">{{ __('ui.chatbot.knowledge_all') }}</option>
                            <option value="none">{{ __('ui.chatbot.knowledge_none') }}</option>
                        </select>
                        <span class="ml-auto text-[11px] text-gray-400 dark:text-gray-500">{{ __('ui.chatbot.paste_hint') }}</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .vpoint-ai-markdown :where(h1,h2,h3){font-weight:700;margin:.65rem 0 .35rem}.vpoint-ai-markdown h1{font-size:1.15rem}.vpoint-ai-markdown h2{font-size:1.05rem}.vpoint-ai-markdown h3{font-size:1rem}.vpoint-ai-markdown p{margin:.45rem 0}.vpoint-ai-markdown ul,.vpoint-ai-markdown ol{margin:.45rem 0 .45rem 1.25rem}.vpoint-ai-markdown ul{list-style:disc}.vpoint-ai-markdown ol{list-style:decimal}.vpoint-ai-markdown code{border-radius:.35rem;background:rgba(148,163,184,.22);padding:.1rem .3rem;font-size:.86em}.vpoint-ai-markdown pre{margin:.7rem 0;overflow:auto;border-radius:.85rem;background:#020617;color:#e2e8f0;padding:1rem}.vpoint-ai-markdown pre code{background:transparent;padding:0;color:inherit}.vpoint-ai-markdown table{margin:.7rem 0;width:100%;border-collapse:collapse;font-size:.9em}.vpoint-ai-markdown th,.vpoint-ai-markdown td{border:1px solid rgba(148,163,184,.35);padding:.45rem}.vpoint-ai-markdown blockquote{border-left:3px solid rgba(99,102,241,.65);padding-left:.8rem;color:#64748b}
    </style>
</x-filament-panels::page>
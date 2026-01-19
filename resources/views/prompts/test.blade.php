@extends('layouts.app')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <div class="container mx-auto p-4">
        <div class="max-w-7xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-bold dark:text-white mb-6">{{ __('Test Prompt') }}: {{ $key }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Left Column: Prompt Details -->
                        <div class="md:col-span-1 space-y-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2"> {{ $key }}</h3>
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600 text-sm whitespace-pre-wrap h-96 overflow-y-auto dark:text-gray-200">{{ $prompt }}</div>
                            </div>

                            <div>
                                <a href="{{ route('prompts.edit', $key) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm">&larr; Back to  {{ $key }}</a>
                            </div>
                        </div>

                        <!-- Right Column: Chat Interface -->
                        <div class="md:col-span-2" x-data="promptTester()">
                            <div class="flex flex-col h-[600px] border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm">
                                <!-- Toolbar -->
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <label for="provider" class="text-sm font-medium text-gray-700 dark:text-gray-300">Provider:</label>
                                        <select x-model="provider" id="provider" class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="" disabled>Select Provider</option>
                                            @foreach($availableApis as $provider => $enabled)
                                                @if($enabled)
                                                    <option value="{{ $provider }}">{{ ucfirst($provider) }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <button @click="messages = []" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">Clear Chat</button>
                                </div>

                                <!-- Chat Area -->
                                <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-white dark:bg-gray-800" id="chat-container">
                                    <template x-if="messages.length === 0">
                                        <div class="text-center text-gray-400 mt-10">
                                            <p>Select a provider and start testing your prompt.</p>
                                        </div>
                                    </template>

                                    <template x-for="(msg, index) in messages" :key="index">
                                        <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                                            <div :class="msg.role === 'user' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'" class="max-w-[80%] rounded-lg px-4 py-2 text-sm shadow-sm">
                                                <div class="font-xs opacity-75 mb-1" x-text="msg.role === 'user' ? 'You' : 'AI'"></div>
                                                <div x-html="parseMarkdown(msg.content)" class="prose max-w-none" :class="msg.role === 'user' ? 'prose-invert' : 'dark:prose-invert'"></div>
                                            </div>
                                        </div>
                                    </template>

                                    <div x-show="loading" class="flex justify-start">
                                        <div class="bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-lg px-4 py-2 text-sm">
                                            <span class="animate-pulse">Thinking...</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Input Area -->
                                <div class="p-3 border-t border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                                    <form @submit.prevent="sendMessage" class="flex gap-2">
                                        <input x-model="input" type="text" placeholder="Type a message..." class="pl-4 flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :disabled="loading || !provider">
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="loading || !provider || !input.trim()">
                                            Send
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
        </div>
    </div>

    <script>
        function promptTester() {
            return {
                provider: '',
                input: '',
                messages: [],
                loading: false,
                systemPrompt: @json($prompt),

                init() {
                    const select = document.getElementById('provider');
                    if (select && select.options.length > 1) {
                        this.provider = select.options[1].value;
                    }

                    this.$watch('messages', () => {
                        this.$nextTick(() => {
                            document.querySelectorAll('#chat-container pre code').forEach((block) => {
                                hljs.highlightElement(block);
                            });
                        });
                    });
                },

                async sendMessage() {
                    if (!this.input.trim() || !this.provider) return;

                    const userContent = this.input;
                    this.messages.push({ role: 'user', content: userContent });
                    this.input = '';
                    this.loading = true;
                    this.scrollToBottom();

                    try {
                        const response = await fetch('/api/chat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                provider: this.provider,
                                messages: [
                                    { role: 'system', content: this.systemPrompt },
                                    ...this.messages.map(m => ({ role: m.role, content: m.content }))
                                ]
                            })
                        });

                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                        const data = await response.json();
                        const aiContent = data.content || data.message || JSON.stringify(data);

                        this.messages.push({ role: 'assistant', content: aiContent });
                    } catch (error) {
                        console.error('Chat error:', error);
                        this.messages.push({ role: 'assistant', content: 'Error: ' + error.message });
                    } finally {
                        this.loading = false;
                        this.scrollToBottom();
                    }
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = document.getElementById('chat-container');
                        container.scrollTop = container.scrollHeight;
                    });
                },
                parseMarkdown(content) {
                    return typeof marked !== 'undefined' ? marked.parse(content) : content;
                }
            }
        }
    </script>
@endsection

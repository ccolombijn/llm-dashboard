export default function promptConverter(initialValue: string = '') {
    return {
        activeTab: 'raw',
        rawPrompt: initialValue,
        displayPrompt: initialValue,
        highlightedCode: '',
        copyButtonText: 'Copy',
        isLoading: false,
        stats: { size: 0, tokens: 0 },
        cachedConversions: {} as Record<string, { content: string, size: number, tokens: number }>,
        cacheSource: initialValue,

        init() {
            this.rawPrompt = initialValue;
            this.displayPrompt = initialValue;
            this.cacheSource = initialValue;
        },

        switchTab(tab: string) {
            if (this.isLoading) return;
            this.activeTab = tab;
            this.convertContent();
        },

        async convertContent(force: boolean = false) {
            if (this.activeTab === 'raw') {
                this.displayPrompt = this.rawPrompt;
            }

            // Check cache if not forced and source hasn't changed
            if (!force && this.cachedConversions[this.activeTab] && this.rawPrompt === this.cacheSource) {
                const cached = this.cachedConversions[this.activeTab];
                this.displayPrompt = cached.content;
                this.stats = { size: cached.size, tokens: cached.tokens };
                this.updateHighlighting();
                return;
            }

            // If source changed, invalidate cache
            if (this.rawPrompt !== this.cacheSource) {
                this.cachedConversions = {};
                this.cacheSource = this.rawPrompt;
            }

            this.isLoading = true;
            this.displayPrompt = 'Converting...';

            try {
                let prompt = '';
                if (this.activeTab === 'raw') {
                    prompt = this.rawPrompt;
                } else if (this.activeTab === 'toon') {
                    prompt = `Convert the following prompt content into Token-Oriented Object Notation (TOON).
TOON is a compact format designed to minimize tokens.

Syntax Reference:
- Key-Value: key: value
- Arrays: key[count]: item1,item2
- Object Arrays: key[count]{prop1,prop2}:
  val1,val2

Example Output:
context:
  location: Boulder
friends[2]: ana,luis
hikes[2]{name,dist}:
  Blue Lake,7.5
  Ridge,9.2

Convert the following text to TOON:
${this.rawPrompt}`;
                } else if (this.activeTab === 'poml') {
                    prompt = `Convert the following prompt content into Prompt Orchestration Markup Language (POML).
POML is an XML-based format.

Requirements:
- Use strictly XML syntax (tags like <poml>, <persona>, <context>, <task>, <constraints>).
- Do NOT use YAML or Markdown formatting.
- The root tag must be <poml>.

Convert the following text to POML:
${this.rawPrompt}`;
                } else {
                    prompt = `Convert the following prompt to JSON format:\n\n${this.rawPrompt}`;
                }

                const response = await fetch('/ai-generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        stream : false,
                        prompt: prompt,
                    })
                });

                if (!response.ok) throw new Error('Conversion failed');

                const data = await response.json();

                let content = '';
                let tokens = 0;

                if (this.activeTab === 'raw') {
                    content = this.rawPrompt;
                    tokens = data.tokens_in || (data.usage ? data.usage.prompt_tokens : 0);
                } else {
                    content = data.response;
                    const codeBlockMatch = content.match(/```(?:[\w-]*\s+)?([\s\S]*?)```/);
                    if (codeBlockMatch) {
                        content = codeBlockMatch[1].trim();
                    }
                    tokens = data.tokens_out || data.tokens || (data.usage ? data.usage.completion_tokens : 0);
                }
                this.displayPrompt = content;

                const size = content.length;
                this.stats = { size, tokens };
                this.cachedConversions[this.activeTab] = { content, size, tokens };
                this.updateHighlighting();
            } catch (error) {
                console.error(error);
                this.displayPrompt = 'Error converting prompt.';
            } finally {
                this.isLoading = false;
            }
        },

        updatePrompt(value: string) {
            this.displayPrompt = value;
            // Only update the raw source if we are in raw mode to preserve the original text
            if (this.activeTab === 'raw') {
                this.rawPrompt = value;
                this.stats = { size: value.length, tokens: 0 };
            }
        },

        updateHighlighting() {
            const hljs = (window as any).hljs;
            if (!hljs || this.activeTab === 'raw') return;

            let language = 'plaintext';
            if (this.activeTab === 'json') language = 'json';
            if (this.activeTab === 'poml') language = 'xml';
            if (this.activeTab === 'toon') language = 'yaml';

            try {
                // Ensure displayPrompt is a string before highlighting
                const content = typeof this.displayPrompt === 'string' ? this.displayPrompt : JSON.stringify(this.displayPrompt, null, 2);
                this.highlightedCode = hljs.highlight(content, { language }).value;
            } catch (e) {
                console.warn('Highlighting failed, falling back to auto', e);
                this.highlightedCode = hljs.highlightAuto(this.displayPrompt).value;
            }
        },

        async copyToClipboard() {
            const content = typeof this.displayPrompt === 'string' ? this.displayPrompt : JSON.stringify(this.displayPrompt, null, 2);
            try {
                await navigator.clipboard.writeText(content);
                this.copyButtonText = 'Copied!';
                setTimeout(() => this.copyButtonText = 'Copy', 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
                this.copyButtonText = 'Error';
            }
        }
    };
}

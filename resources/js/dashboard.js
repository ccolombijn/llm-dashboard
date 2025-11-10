export default function dashboard(generateUrl, modelsUrl, initialAvailableApis) {
    return {
        isConfigModalOpen: false,
        isTestModalOpen: false,
        modalProvider: '',
        testPrompt: 'Write a short haiku about a robot.',
        testInput: '',
        testResponse: '',
        testResponseDetails: null,
        isTesting: false,
        availableModels: {},
        availableApis: initialAvailableApis, // Store the passed data
        selectedModel: '',

        openConfigModal(provider) {
            this.isConfigModalOpen = true;
            this.modalProvider = provider;
            this.$nextTick(() => this.$refs.apiKeyInput.focus());
        },

        async openTestModal(provider, prompt = null, ) {
            this.isTestModalOpen = true;
            this.modalProvider = provider || document.querySelector('.text-green-600').closest('li').dataset.provider;
            this.testResponse = '';
            this.testResponseDetails = null;
            this.testInput = '';
            this.selectedModel = '';
            if (provider) { // Called from "Test" button next to a specific API
                this.modalProvider = provider;
                this.selectedProvider = provider;
                this.testPrompt = 'Write a short haiku about a robot.'; // Default for API test
            } else { // Called from "Test" button next to a prompt
                // Find the first configured provider as a default for prompt tests
                const firstConfigured = Object.keys(this.availableApis).find(key => this.availableApis[key]);
                this.modalProvider = firstConfigured || '';
                this.selectedProvider = firstConfigured || '';
                this.testPrompt = prompt; // Set the specific prompt
            }

            // Fetch models if they haven't been fetched yet
             if (Object.keys(this.availableModels).length === 0 || (this.selectedProvider && (!this.availableModels.models || !this.availableModels.models[this.selectedProvider]))) {
                try {
                    const response = await fetch(modelsUrl); // Assuming modelsUrl is the correct route
                    const data = await response.json();
                    this.availableModels = data;
                } catch (error) {
                    console.error('Could not fetch AI models:', error);
                }
            }


            this.$nextTick(() => this.$refs.testPromptInput.focus());
        },

        async runTest() {
            this.isTesting = true;
            this.testResponse = '';
            this.testResponseDetails = null;

            const startTime = performance.now();
            const body = {
                provider: this.modalProvider,
                prompt: this.testPrompt,
            };

            if (this.selectedModel) {
                body.model = this.selectedModel;
            }

            if (this.testInput) {
                body.input = this.testInput;
            }

            try {
                const response = await fetch(generateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(body)
                });

                const responseTime = ((performance.now() - startTime) / 1000).toFixed(2);
                const responseSize = response.headers.get('Content-Length');

                const data = await response.json();
                this.testResponse = data.response || data.error || 'An unknown error occurred.';
                this.testResponseDetails = {
                    tokensIn: data.tokens_in ?? 'N/A',
                    tokensOut: data.tokens_out ?? 'N/A',
                    time: responseTime,
                    bytes: responseSize ? `${responseSize} bytes` : 'N/A',
                };

            } catch (error) {
                this.testResponse = 'An error occurred while sending the request.' + error.message
                console.error(error);
            } finally {
                this.isTesting = false;
            }
        }
    };
}

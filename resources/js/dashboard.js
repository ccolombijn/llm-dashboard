export default function dashboard(updateHandlerUrl, generateUrl, modelsUrl, initialAvailableApis, defaultHandler) {
    return {
        isConfigModalOpen: false,
        isTestModalOpen: false,
        isChangeDefaultModelModalOpen: false,
        modalProvider: '',
        testPrompt: 'Write a short haiku about a robot.',
        testInput: '',
        testResponse: '',
        testResponseDetails: null,
        isTesting: false,
        availableModels: {},
        modelError: {}, // To track errors per provider
        availableApis: initialAvailableApis, // Store the passed data
        providerApiKeyUrls: {
            openai: 'https://platform.openai.com/api-keys',
            anthropic: 'https://console.anthropic.com/settings/admin-keys',
            mistral: 'https://admin.mistral.ai/organization/api-keys',
            gemini: 'https://aistudio.google.com/app/apikey',
        },
        selectedModel: '',
        selectedProvider: '',
        isApiTest: false,
        defaultHandler: defaultHandler,
        newDefaultModel: '',
        showSuccessNotification: false,
        successNotificationMessage: '',

        get providerDefaultModel() {
            if (!defaultHandler) return null;
            const [defaultProvider, defaultModel] = defaultHandler.split(':');
            if (this.selectedProvider === defaultProvider) {
                return defaultModel;
            }
            return null;
        },

        openConfigModal(provider) {
            this.isConfigModalOpen = true;
            this.modalProvider = provider;
            this.$nextTick(() => this.$refs.apiKeyInput.focus());
        },

        getAPIKey(provider) {
            window.open(this.providerApiKeyUrls[provider] ,  '_blank');
            //return this.providerApiKeyUrls[provider] || '#';
        },

        async openChangeDefaultModelModal(provider) {
            this.modalProvider = provider;
            await this.fetchModelsIfNeeded(provider);

            const [defaultProvider, defaultModel] = this.defaultHandler.split(':');
            if (provider === defaultProvider) {
                this.newDefaultModel = defaultModel;
            } else {
                // Set to the first available model for that provider if not the default
                const providerModels = this.availableModels.models?.[provider];
                this.newDefaultModel = providerModels?.[0]?.id || '';
            }

            this.isChangeDefaultModelModalOpen = true;
        },





        async openTestModal(provider, prompt = null, ) {
            this.isTestModalOpen = true;
            this.modalProvider = provider || document.querySelector('.text-green-600').closest('li').dataset.provider;
            this.testResponse = '';
            this.testResponseDetails = null;
            this.testInput = '';

            if (provider) { // Called from "Test" button next to a specific API
                this.isApiTest = true;
                this.modalProvider = provider;
                this.selectedProvider = provider;
                this.testPrompt = 'Write a short haiku about a robot.'; // Default for API test
                // Set default model from defaultHandler if provider matches
                const [defaultProvider, defaultModel] = defaultHandler.split(':');
                this.selectedModel = provider === defaultProvider ? defaultModel : '';
            } else { // Called from "Test" button next to a prompt
                this.isApiTest = false;
                // Find the first configured provider as a default for prompt tests
                const firstConfigured = Object.keys(this.availableApis).find(key => this.availableApis[key]);
                this.modalProvider = firstConfigured || '';
                this.selectedProvider = firstConfigured || '';
                this.testPrompt = prompt; // Set the specific prompt
            }
            this.selectedModel = this.providerDefaultModel || '';


            // Fetch models if they haven't been fetched yet
            await this.fetchModelsIfNeeded(this.selectedProvider);

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
        },

        async fetchModelsIfNeeded(provider) {
            if (!provider) return;
            const hasModels = this.availableModels.models && this.availableModels.models[provider];
            this.modelError[provider] = false; // Reset error state
            if (Object.keys(this.availableModels).length === 0 || !hasModels) {
                try {
                    const response = await fetch(modelsUrl);
                    const data = await response.json();
                    this.availableModels = data;
                    this.modelError[provider] = !data.models?.[provider]?.length;
                } catch (error) {
                    this.modelError[provider] = true;
                    console.error('Could not fetch AI models:', error);
                }
            }
        },

        async updateDefaultHandler() {
            const newHandler = `${this.modalProvider}:${this.newDefaultModel}`;
            try {
                const response = await fetch(updateHandlerUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ handler: newHandler })
                });
                if (response.ok) {
                    this.defaultHandler = newHandler;
                    this.isChangeDefaultModelModalOpen = false;
                    this.showSuccessNotification = true;
                    this.successNotificationMessage = 'Default handler updated successfully!';
                    setTimeout(() => {
                        this.showSuccessNotification = false;
                    }, 3000);
                }
            } catch (error) {
                console.error('Failed to update default handler:', error);
            }
        }
    };
}

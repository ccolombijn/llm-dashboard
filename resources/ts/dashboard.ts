import { DashboardState, TestResponseDetails, AvailableModels } from './types';
import { AlpineComponent, Refs } from "alpinejs";

export default function dashboard(
    updateHandlerUrl: string,
    generateUrl: string,
    modelsUrl: string,
    initialAvailableApis: { [key: string]: boolean },
    defaultHandler: string
): DashboardState {
    return {
        activeModal: null,
        modalProvider: '',
        promptToDeleteKey: '',
        providerToDeleteKey: '',
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
        theme: localStorage.getItem('theme') || 'system',
        themeSwitcherOpen: false,

        get providerDefaultModel() {
            if (!this.defaultHandler) return null;
            const [defaultProvider, defaultModel] = (this.defaultHandler || '').split(':');
            if (this.selectedProvider === defaultProvider) {
                return defaultModel;
            }
            return null;
        },

        isDefaultProvider(provider: string) {
            try {
                if (!this.defaultHandler) {
                    return false;
                }
                return this.defaultHandler.split(':')[0] === provider;
            } catch (e) {
                console.error(`[Dashboard Error] Failed to check if provider '${provider}' is default.`, {
                    defaultHandler: this.defaultHandler,
                    error: e,
                });
                return false;
            }
        },

        getDefaultModelName() {
            try {
                if (!this.defaultHandler) {
                    return '';
                }
                const parts = this.defaultHandler.split(':');
                return parts.length > 1 ? parts[1] : '';
            } catch (e) {
                console.error(`[Dashboard Error] Failed to get default model name.`, {
                    defaultHandler: this.defaultHandler,
                    error: e,
                });
                return '';
            }
        },

        init(this: DashboardState & AlpineComponent) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.theme === 'system') this.setTheme('system'); // system theme on change
            });
            this.fetchAllModels();
        },

        setTheme(this: DashboardState, newTheme: string) {
            this.theme = newTheme;
            if (newTheme === 'system') {
                localStorage.removeItem('theme');
                document.documentElement.setAttribute('data-theme', window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            } else {
                localStorage.setItem('theme', newTheme);
                document.documentElement.setAttribute('data-theme', newTheme);
            }
        },


        async openConfigModal(this: DashboardState & AlpineComponent, provider: string) {
            this.modalProvider = provider;
            this.activeModal = 'config';
            await this.$nextTick();
            if (this.$refs.apiKeyInput) {
                (this.$refs.apiKeyInput as HTMLInputElement).focus();
            }
        },

        getAPIKey(provider: string) {
            window.open(this.providerApiKeyUrls[provider] ,  '_blank');
        },

        async openChangeDefaultModelModal(this: DashboardState & AlpineComponent, provider: string) {
            this.modalProvider = provider;
            await this.fetchModelsIfNeeded(provider);

            const [defaultProvider, defaultModel] = (this.defaultHandler || '').split(':');
            if (provider === defaultProvider) {
                this.newDefaultModel = defaultModel;
            } else {
                // Set to the first available model for that provider if not the default
                const providerModels = this.availableModels.models?.[provider];
                this.newDefaultModel = providerModels?.[0]?.id || '';
            }
            this.activeModal = 'changeDefault';
            await this.$nextTick();
            if (this.$refs.defaultModelSelect) {
                (this.$refs.defaultModelSelect as HTMLSelectElement).focus();
            }
        },

        async openChangeModelModal(this: DashboardState & AlpineComponent, provider: string) {
            this.modalProvider = provider;
            await this.fetchModelsIfNeeded(provider);

            const [defaultProvider, defaultModel] = (this.defaultHandler || '').split(':');
            if (provider === defaultProvider) {
                this.newDefaultModel = defaultModel;
            } else {
                // Set to the first available model for that provider if not the default
                const providerModels = this.availableModels.models?.[provider];
                this.newDefaultModel = providerModels?.[0]?.id || '';
            }
            this.activeModal = 'changeModel';
            await this.$nextTick();
            if (this.$refs.defaultModelSelect) {
                (this.$refs.defaultModelSelect as HTMLSelectElement).focus();
            }
        },

        async openTestModal(this: DashboardState & AlpineComponent, provider: string, prompt: string | null = null) {
            this.testResponse = '';
            this.testResponseDetails = null;
            this.testInput = '';

            if (provider) { // Test is for a specific API
                this.isApiTest = true;
                this.modalProvider = provider;
                this.selectedProvider = provider;
                this.testPrompt = 'Write a short haiku about a robot.';
            } else { // Test is for a generic prompt
                this.isApiTest = false;
                // Find the first configured provider as a default for prompt tests
                const firstConfigured = Object.keys(this.availableApis).find(key => this.availableApis[key] && !this.modelError[key]) || '';
                this.modalProvider = firstConfigured;
                this.selectedProvider = firstConfigured;
                this.testPrompt = prompt || '';
            }

            this.selectedModel = this.providerDefaultModel || '';
            await this.fetchModelsIfNeeded(this.selectedProvider);
            this.activeModal = 'test';
            await this.$nextTick();
            if (this.$refs.testPromptInput) {
                (this.$refs.testPromptInput as HTMLTextAreaElement).focus();
            }
        },

        async openDeletePromptModal(this: DashboardState, key: string) {
            this.promptToDeleteKey = key;
            this.activeModal = 'deletePrompt';
        },

        openDeleteApiKeyModal(this: DashboardState, provider: string) {
            this.providerToDeleteKey = provider;
            this.activeModal = 'deleteApiKey';
            console.log('Opening delete API key modal for provider:', provider, this.activeModal);
        },


        async runTest(this: DashboardState & AlpineComponent) {
            this.isTesting = true;
            this.testResponse = '';
            this.testResponseDetails = null;

            const startTime = performance.now();
            const body: { provider: string; prompt: string; model?: string; input?: string } = {
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
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch(generateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
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

            } catch (error: any) {
                this.testResponse = 'An error occurred while sending the request.' + error.message
                console.error(error);
            } finally {
                this.isTesting = false;
            }
        },

        async fetchAllModels(this: DashboardState & AlpineComponent) {
            if (Object.keys(this.availableModels).length > 0) return; // Already fetched

            try {
                const response = await fetch(modelsUrl);
                const data: AvailableModels = await response.json();
                this.availableModels = data;
                for (const provider in this.availableApis) {
                    if (this.availableApis[provider]) {
                        this.modelError[provider] = !data.models?.[provider]?.length;
                    }

                }
            } catch (error) {
                console.error('Could not fetch AI models:', error);
                for (const provider in this.availableApis) {
                    if (this.availableApis[provider]) {
                        this.modelError[provider] = true;
                    }
                }
            }
        },

        async fetchModelsIfNeeded(this: DashboardState & AlpineComponent, provider: string) {
            // This function now just ensures the global fetch has happened.
            await this.fetchAllModels();
        },

        async updateDefaultHandler(this: DashboardState & AlpineComponent) {
            const newHandler = `${this.modalProvider}:${this.newDefaultModel}`;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch(updateHandlerUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({ handler: newHandler })
                });
                if (response.ok) {
                    this.defaultHandler = newHandler;
                    this.activeModal = null;
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

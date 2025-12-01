import Alpine, { Alpine as AlpineType } from "alpinejs";

declare global {
    interface Window {
        axios: any;
        Alpine: AlpineType;
    }
}

export interface TestResponseDetails {
    tokensIn: number | 'N/A';
    tokensOut: number | 'N/A';
    time: string;
    bytes: string;
}

export interface Model {
    id: string;
    name: string;
}

export interface AvailableModels {
    models?: {
        [provider: string]: Model[];
    };
}

export interface DashboardState {
    activeModal: string | null;
    modalProvider: string;
    promptToDeleteKey: string;
    providerToDeleteKey: string;
    testPrompt: string;
    testInput: string;
    testResponse: string;
    testResponseDetails: TestResponseDetails | null;
    isTesting: boolean;
    availableModels: AvailableModels;
    modelError: { [key: string]: boolean };
    availableApis: { [key: string]: boolean };
    providerApiKeyUrls: { [key: string]: string };
    selectedModel: string;
    selectedProvider: string;
    isApiTest: boolean;
    defaultHandler: string;
    newDefaultModel: string;
    showSuccessNotification: boolean;
    successNotificationMessage: string;
    theme: string;
    themeSwitcherOpen: boolean;
    readonly providerDefaultModel: string | null;
    profileToDeleteName: string;
    formToDeleteId: string;
    isDefaultProvider(provider: string): boolean;
    getDefaultModelName(): string;
    init(): void;
    setTheme(newTheme: string): void;
    openConfigModal(provider: string): Promise<void>;
    getAPIKey(provider: string): void;
    openChangeDefaultModelModal(provider: string): Promise<void>;
    openChangeModelModal(provider: string): Promise<void>;
    openTestModal(provider: string, prompt?: string | null): Promise<void>;
    openDeletePromptModal(key: string): Promise<void>;
    openDeleteApiKeyModal(provider: string): void;
    runTest(): Promise<void>;
    updateDefaultHandler(): Promise<void>;
    fetchAllModels(): Promise<void>;
    fetchModelsIfNeeded(provider: string): Promise<void>;
    openDeleteProfileModal(formId: string, profileName: string): void;
}

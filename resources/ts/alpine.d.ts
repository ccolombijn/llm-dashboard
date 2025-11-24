declare module 'alpinejs' {
    export interface Alpine {
        data(name: string, data: any): void;
        start(): void;
        [key: string]: any;
    }

    export interface AlpineComponent<T = {}> {
        $nextTick: (callback?: () => void) => Promise<void>;
        $refs: Refs;
        init?(): void;
    }

    export interface Refs {
        apiKeyInput: HTMLInputElement;
        defaultModelSelect: HTMLSelectElement;
        testPromptInput: HTMLTextAreaElement;
    }

    const Alpine: Alpine;
    export default Alpine;
}

import { fetchStream } from "../fetchStream";
import loadingGifUrl from '../../../images/loading.gif';
/**
 * Summarize page with AI
 * @returns {void}
 */
export function aiSummarize(): void {

    const contentElement = document.querySelector('.content') as HTMLElement | null;
    const summarizeElement = document.querySelector('#ai-summarize') as HTMLElement | null;
    const summarizeButton = document.querySelector('.summarize-btn') as HTMLButtonElement | null;

    if (!contentElement || !summarizeElement || !summarizeButton) {
        console.warn("ai/summarize : one or more (.content, #ai-summarize, .summarize-btn) not found. Aborted");
        return; 
    }

    const pageContent = contentElement.textContent?.trim() || '';

    summarizeButton.addEventListener('click', () => {
        summarizeElement.innerHTML = `<img src="${loadingGifUrl}" width="35" /> <span>Wachten..</span>`;
        fetchStream(
            {
                prompt: 'summarize',
                input: pageContent,
                stream: true
            },
            '/ai-generate',
            summarizeElement
        );
    });
}
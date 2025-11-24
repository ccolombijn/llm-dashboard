import { fetchStream } from '../fetchStream';
import loadingGifUrl from '../../../images/loading.gif';
/**
 * Sends textContent of elements with .click-me with fetchStream to backend
 * @returns {void}
 */
export function aiClickwords(): void {

    const explanationElement = document.querySelector('#ai-explanation') as HTMLElement | null;
    if (!explanationElement) {
        console.warn("ai/clickwords : '#ai-explanation' not found. Aborted");
        return;
    }

    const clickwords = document.querySelectorAll('.click-me');
    clickwords.forEach(word => {
        if (word instanceof HTMLElement) {
            const textContent = word.textContent?.trim();
            if (textContent) {
                word.addEventListener('click', () => {
                    explanationElement.innerHTML = `<img src="${loadingGifUrl}" width="35" /> <span>Wachten..</span>`;
                    fetchStream(
                        {
                            prompt: 'explanation',
                            input: textContent,
                            stream: true
                        },
                        '/ai-generate',
                        explanationElement
                    );
                });
            }
        }
    });
}
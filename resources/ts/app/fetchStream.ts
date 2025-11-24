import { marked } from 'marked';
/**
 * Implements fetch stream reader and text decoder
 * @param request 
 * @param endpoint 
 * @param target 
 * @returns {Promise<void>}
 */
export async function fetchStream(
    request: Record<string, any>, // Generic request body object
    endpoint: string,
    target: HTMLElement
): Promise<void> {

    try {

        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta?.getAttribute('content');

        if (!csrfToken) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(request)
        });

        if (!response.ok) {
            throw new Error(`HTTP error status: ${response.status}`);
        }

        if (!response.body) {
            throw new Error('Response does not contain body to stream');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let fullResponse = '';
        target.innerHTML = ''; 

        while (true) {
            const { done, value } = await reader.read();
            if (done) {
                break; // Stream complete; exit loop
            }

            const chunk = decoder.decode(value, { stream: true });

            if (!chunk.includes('<!DOCTYPE html>')) { // Check if Laravel error page is returned
                fullResponse += chunk;
                target.innerHTML = await marked.parse(fullResponse);
            }
        }

    } catch (error) {
        console.error('Error getting stream:', error);
        target.innerHTML = `Er is iets misgegaan bij uitvoeren van verzoek`;
    }
}
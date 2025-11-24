import { marked } from 'marked';
import loadingGifUrl from '../../../images/loading.gif';
type HistoryRole = 'user' | 'model';
type DisplayRole = 'user' | 'ai';

interface ChatMessage {
  role: HistoryRole;
  text: string;
}

const chatHistory: ChatMessage[] = [];
const profiles = [];
/**
 * Get and populate profiles from backend
 * @todo populate profiles from backend
 * @returns 
 */
function populateProfiles(): void {
    const profileSelectEl = document.getElementById('profile-select') as HTMLSelectElement | null;
    if (!profileSelectEl) {
        console.warn('ai/chat.populateProfiles : profile-select element not found. Aborted');
        return;
    }
    fetchProfilesFromBackend().then(data => {
        profiles.push(...data);
        profiles.unshift('Default'); 
        appendProfilesToSelect(profileSelectEl, profiles);
    }).catch(error => {
        console.error('Error fetching profiles:', error);
    });
}
/**
 * Append profiles to select element
 * @param profileSelectEl 
 * @param profiles 
 */
function appendProfilesToSelect(profileSelectEl: HTMLSelectElement, profiles: string[]): void {
    profileSelectEl.innerHTML = '';
    profiles.forEach(profile => {
        const option = document.createElement('option');
        option.value = profile === 'default' ? '' : profile;
        option.textContent = profile;
        profileSelectEl.appendChild(option);
    });
}
/**
 * Fetch profiles from backend
 * @returns Promise<string[]>
 */
function fetchProfilesFromBackend(): Promise<string[]> {
    return new Promise((resolve, reject) => {
        fetch('/ai-profiles').then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        }).then(data => {
            resolve(data.profiles);
        }).catch(error => {
            reject(error);
        });
    });
}
      
export function aiChat(): void {
    populateProfiles();
    fetchAndDisplaySuggestions();
    const sendPromptBtn = document.getElementById('user-input-btn') as HTMLButtonElement | null;
    const userInputEl = document.getElementById('user-input') as HTMLDivElement | null;

    if (sendPromptBtn) {
        sendPromptBtn.addEventListener('click', sendPrompt);
    }

    if (userInputEl) {
        userInputEl.addEventListener('keydown', (event: KeyboardEvent) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault(); // Prevent adding a new line
                sendPrompt();
            }
        });
        userInputEl.addEventListener('input', () => {
            const suggestionsContainer = document.getElementById('chat-suggestions');
            if (suggestionsContainer) {
                suggestionsContainer.innerHTML = '';
            }
        });
    } else {
        console.warn('ai/chat : #user-input-btn not found. Aborted' );
    }
}

/**
 * @todo display message implementation
 */
function displayMessage(input: string, role: DisplayRole): HTMLElement { 
    const element = createMessageElement(role);
    element.textContent = input;
    return element; 
}
/**
 * 
 * @param role {DisplayRole}
 * @returns HTMLElement
 */
function createMessageElement(role: DisplayRole): HTMLElement {
    const chatContainer = document.getElementById('chat');
    if (!chatContainer) {
        throw new Error('chat element not found');
    }
    const element = document.createElement('div');
    element.classList.add(`message-${role}`);
    return element;
}
/**
 * 
 * @returns Promise<void>
 */
async function sendPrompt(): Promise<void> {

    const userInputEl = document.getElementById('user-input') as HTMLDivElement | null;
    if (!userInputEl) {
        console.error("Input with ID 'user-input' not found.");
        return;
    }
    
    const userInput = userInputEl.textContent?.trim() || '';
    const filePathInput = document.getElementById('file-path-input') as HTMLInputElement | null;
    const filePaths = filePathInput ? filePathInput.value.trim().split(',').map(p => p.trim()).filter(p => p) : [];
    const profileSelectEl = document.getElementById('profile-select') as HTMLSelectElement | null;
    const selectedProfile = profileSelectEl ? profileSelectEl.value : userInputEl.dataset.profile || undefined;


    if (!userInput && filePaths.length === 0) return;
    
    // Display user message immediately
    let userMessage = userInput;
    if (filePaths.length > 0) {
        const fileList = filePaths.join(', ');
        userMessage = `File(s): ${fileList}\n\n${userInput}`;
    }
    const userMessageElement = displayMessage(userMessage, 'user');
    const chatContainer = document.getElementById('chat');
    if (chatContainer) {
        chatContainer.appendChild(userMessageElement);
    }
    chatHistory.push({ role: 'user', text: userInput });
    userInputEl.textContent = ''; // Clear the div after sending

    const requestBody = {
        stream : true,
        prompt: userInput,
        history: chatHistory.slice(0, -1), // Send complete history except current message
        file_paths: filePaths.length > 0 ? filePaths : undefined,
        profile: selectedProfile && selectedProfile !== '' ? selectedProfile : undefined
    };

    let fullResponse = '';
    const aiMessageElement = createMessageElement('ai'); // Create AI message element
    aiMessageElement.innerHTML = `<img src="${loadingGifUrl}" width="35" /> <span>Wachten..</span>`;
    if (chatContainer) {
        chatContainer.appendChild(aiMessageElement); // Append AI message placeholder
        chatContainer.scrollTop = chatContainer.scrollHeight; // Scroll to bottom
    }
    try {
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta?.getAttribute('content');
        if (!csrfToken) {
            throw new Error('CSRF token not found.');
        }

        const response = await fetch('/ai-generate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });

        if (!response.body) {
            throw new Error("Response body is missing.");
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();

        while (true) {
            const { done, value } = await reader.read();
            if (done) {
                //chatHistory.push({ role: 'model', text: fullResponse });
                break;
            }
            const chunk = decoder.decode(value, { stream: true });
            fullResponse += chunk; 
            aiMessageElement.innerHTML = await marked.parse(fullResponse); 
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight; // Scroll to bottom with each chunk
            }
        }
        chatHistory.push({ role: 'model', text: fullResponse }); // Add complete AI message to history
        fetchAndDisplaySuggestions();
    } catch (error) {
        console.error('Fetch error:', error);
        aiMessageElement.textContent = 'Er is een fout opgetreden bij de communicatie met de AI.'; // Display error
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight; // Scroll to bottom on error
        }
    }
}

/**
 * Fetches and displays prompt suggestions from the backend.
 */
async function fetchAndDisplaySuggestions(): Promise<void> {
    const suggestionsContainer = document.getElementById('chat-suggestions');
    if (!suggestionsContainer) {
        console.warn('ai/chat.fetchAndDisplaySuggestions: #chat-suggestions element not found. Aborted');
        return;
    }
    suggestionsContainer.innerHTML = ''; // Clear previous suggestions

    const filePathInput = document.getElementById('file-path-input') as HTMLInputElement | null;
    const filePaths = filePathInput ? filePathInput.value.trim().split(',').map(p => p.trim()).filter(p => p) : [];
    const profileSelectEl = document.getElementById('profile-select') as HTMLSelectElement | null;
    const selectedProfile = profileSelectEl ? profileSelectEl.value : undefined;

    const requestBody = {
        history: chatHistory,
        file_paths: filePaths.length > 0 ? filePaths : undefined,
        profile: selectedProfile && selectedProfile !== '' ? selectedProfile : undefined
    };

    try {
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta?.getAttribute('content');
        if (!csrfToken) {
            throw new Error('CSRF token not found.');
        }

        const response = await fetch('/ai-suggest', { // New endpoint
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(requestBody)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        console.log(data);
        if (data.suggestions && data.suggestions.length > 0) {
            data.suggestions.forEach((suggestion: string) => {
                const button = document.createElement('button');
                button.textContent = suggestion;
                button.classList.add('btn','btn--outline'); // Add some styling class
                button.onclick = () => {
                    const userInputEl = document.getElementById('user-input') as HTMLDivElement | null;
                    if (userInputEl) {
                        userInputEl.textContent = suggestion;
                        userInputEl.focus();
                        sendPrompt();
                    }
                };
                suggestionsContainer.appendChild(button);
            });
        }
    } catch (error) {
        console.error('Error fetching prompt suggestions:', error);
    }
}
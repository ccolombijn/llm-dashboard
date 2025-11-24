/**
 * Adds textContent of <label> elements as placeholder
 * @returns {void}
 */
export function form(): void {
    const form = document.querySelector('form.form');
    if (form) {
        const inputs = form.querySelectorAll('[name]');
        inputs.forEach(input => {
            if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {
                const label = form.querySelector(`label[for="${input.name}"]`) as HTMLLabelElement | null;
                if (label && label.textContent) {
                    input.placeholder = label.textContent.trim();
                    label.innerText = '';
                }
            }
        });
    }
}
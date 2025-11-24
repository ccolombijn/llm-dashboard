export function initializeFilePreviews(): void {
 
    const imageRows = document.querySelectorAll('tr.has-preview');

    imageRows.forEach(row => {
        let previewPopup: HTMLDivElement | null = null;
        row.addEventListener('mouseenter', (event: MouseEvent) => {
            const targetRow = event.currentTarget as HTMLElement;
            const imageUrl = targetRow.dataset.previewUrl;
            if (!imageUrl) return;

            previewPopup = document.createElement('div');
            previewPopup.id = 'file-preview-popup';
            previewPopup.className = 'absolute z-50 p-1 bg-white border border-stone-300 rounded shadow-lg pointer-events-none';
            previewPopup.innerHTML = `<img src="${imageUrl}" class="max-w-xs max-h-48 rounded">`;
            
            document.body.appendChild(previewPopup);
        });

        row.addEventListener('mousemove', (event: MouseEvent) => {
            if (previewPopup) {
                previewPopup.style.left = `${event.pageX + 15}px`;
                previewPopup.style.top = `${event.pageY + 15}px`;
            }
        });

        row.addEventListener('mouseleave', () => {
            if (previewPopup) {
                previewPopup.remove();
                previewPopup = null;
            }
        });
    });
}

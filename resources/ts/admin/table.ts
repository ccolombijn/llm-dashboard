export function table() {
    const table = document.querySelector('table') as HTMLTableElement | null;
    if(table) {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const rowLink = row.querySelector('a') as HTMLAnchorElement | null;
            if(rowLink) {
                row.addEventListener('click', () => {
                    window.location.href = rowLink.href;
                })
            }
            row.removeChild(row.lastChild);
        });
    }
}
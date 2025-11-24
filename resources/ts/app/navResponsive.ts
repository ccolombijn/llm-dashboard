/**
 * Adds class to body when responsive menu is opened
 * @returns {void}
 */
export function navResponsive(): void {

    const mobileOpen = document.querySelector('.nav-mobile-open') as HTMLElement;
    const mobileClose = document.querySelector('.nav-mobile-close') as HTMLElement;
    const body = document.querySelector('body') as HTMLElement;

    mobileOpen?.addEventListener('click', () => {
        body.classList.add('open')
    });

    mobileClose?.addEventListener('click', () => {
        body.classList.remove('open');
    });

}
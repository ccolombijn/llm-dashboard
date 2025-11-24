/**
 * Detects webp compatibility
 * @returns {void}
 */
export const image = (): void => {
    const img = new Image();
    img.onload = function() {
        document.documentElement.classList.add('webp');
    };
    img.onerror = function() {
        document.documentElement.classList.add('no-webp');
    };
    // 1x1px image determines if webp is supported or not by browser
    img.src = 'data:image/webp;base64,UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==';
     
};
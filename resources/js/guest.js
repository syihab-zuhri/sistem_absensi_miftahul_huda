export function initializePasswordToggle(document) {
    const button = document.querySelector('[data-password-toggle]');
    if (!button) return;

    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;

    button.addEventListener('click', () => {
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.querySelector('[data-password-hidden]').hidden = visible;
        button.querySelector('[data-password-visible]').hidden = !visible;
    });
}

if (typeof document !== 'undefined') initializePasswordToggle(document);

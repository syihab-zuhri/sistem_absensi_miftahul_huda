import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';

test('password visibility can be toggled without changing the value or submitting the form', async () => {
    const dom = new JSDOM(`<form>
        <input id="password" type="password" value="example-password">
        <button type="button" data-password-toggle aria-controls="password" aria-pressed="false">
            <span data-password-hidden>Show</span><span data-password-visible hidden>Hide</span>
        </button>
    </form>`);
    const { initializePasswordToggle } = await import('../../resources/js/guest.js');
    const document = dom.window.document;
    let submissions = 0;
    document.querySelector('form').addEventListener('submit', event => {
        event.preventDefault();
        submissions++;
    });
    initializePasswordToggle(document);
    const button = document.querySelector('button');
    const password = document.getElementById('password');

    button.click();
    assert.equal(password.type, 'text');
    assert.equal(password.value, 'example-password');
    assert.equal(button.getAttribute('aria-pressed'), 'true');
    assert.equal(button.querySelector('[data-password-hidden]').hidden, true);
    assert.equal(button.querySelector('[data-password-visible]').hidden, false);

    button.click();
    assert.equal(password.type, 'password');
    assert.equal(password.value, 'example-password');
    assert.equal(button.getAttribute('aria-pressed'), 'false');
    assert.equal(button.querySelector('[data-password-hidden]').hidden, false);
    assert.equal(button.querySelector('[data-password-visible]').hidden, true);
    assert.equal(submissions, 0);
    dom.window.close();
});

test('other guest pages work without a password toggle', async () => {
    const { initializePasswordToggle } = await import('../../resources/js/guest.js');
    const dom = new JSDOM('<form><input type="email"></form>');
    assert.doesNotThrow(() => initializePasswordToggle(dom.window.document));
    dom.window.close();
});

import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../public/js/unggah-dokumen.js', import.meta.url), 'utf8');

function browser({ size = 50 * 1024 * 1024, type = 'application/pdf', uploadFails = false, signingFails = false, networkFails = false } = {}) {
    const calls = [];
    const input = { files: size === null ? [] : [{ size, type }], disabled: false };
    const token = { value: '' };
    const status = { textContent: '' };
    const button = { disabled: false };
    let submitHandler;
    let submissions = 0;
    const form = {
        elements: { namedItem: (name) => name === '_token' ? { value: 'csrf-test' } : token },
        querySelector: (selector) => selector.includes('type="file"') ? input : status,
        querySelectorAll: () => [button],
        addEventListener: (name, handler) => { if (name === 'submit') submitHandler = handler; },
    };
    const marker = {
        closest: () => form,
        dataset: { endpoint: '/internal/dokumen/unggah', tujuan: 'hasil-permintaan', target: '12' },
    };
    class Form {}
    Form.prototype.submit = function () {
        submissions++;
        if (input.files.length) assert.equal(input.disabled, true);
    };
    runInNewContext(source, {
        document: { querySelectorAll: () => [marker] },
        HTMLFormElement: Form, AbortSignal,
        fetch: async (url, options) => {
            if (url === 'https://bucket.example.test') {
                calls.push({ type: 'r2', url, options });
                if (networkFails) throw new Error('Koneksi terputus');
                return { ok: !uploadFails, status: uploadFails ? 403 : 200 };
            }
            calls.push({ type: 'application', url, options });
            return {
                ok: !signingFails, status: signingFails ? 419 : 200,
                json: async () => ({ finalize_token: 'upload-test', upload_url: 'https://bucket.example.test', method: 'PUT', key: 'pending_uploads/test', headers: { 'Content-Type': type || 'application/octet-stream' } }),
            };
        },
    });
    return { input, token, status, button, calls, submissions: () => submissions, submit: () => submitHandler({ preventDefault() {} }) };
}

test('50 MB dikirim langsung lewat PUT ke R2 dan final submit membawa token', async () => {
    const b = browser();
    await b.submit();
    const [application, r2] = b.calls;
    assert.equal(application.options.headers['X-CSRF-TOKEN'], 'csrf-test');
    assert.deepEqual(JSON.parse(application.options.body), { tujuan: 'hasil-permintaan', target_id: '12', ukuran: 52428800, content_type: 'application/pdf' });
    assert.equal(r2.url, 'https://bucket.example.test');
    assert.equal(r2.options.method, 'PUT');
    assert.equal(r2.options.body, b.input.files[0]);
    assert.equal(r2.options.headers['Content-Type'], 'application/pdf');
    assert.equal(r2.options.credentials, 'omit');
    assert.equal(r2.options.headers['X-CSRF-TOKEN'], undefined);
    assert.equal(b.token.value, 'upload-test');
    assert.equal(b.submissions(), 1);
    await b.submit();
    assert.equal(b.calls.length, 2);
});

test('file melebihi batas ditolak sebelum request', async () => {
    const b = browser({ size: 52428801 });
    await b.submit();
    assert.equal(b.calls.length, 0);
    assert.equal(b.submissions(), 0);
    assert.match(b.status.textContent, /50 MB/);
});

test('gagal upload tidak memfinalisasi dan pengguna dapat mencoba lagi', async () => {
    const b = browser({ uploadFails: true });
    await b.submit();
    assert.equal(b.submissions(), 0);
    assert.equal(b.token.value, '');
    assert.equal(b.input.disabled, false);
    assert.equal(b.button.disabled, false);
    await b.submit();
    assert.equal(b.calls.length, 4);
});

test('sesi kedaluwarsa menghentikan request R2', async () => {
    const b = browser({ signingFails: true });
    await b.submit();
    assert.equal(b.calls.length, 1);
    assert.equal(b.submissions(), 0);
    assert.match(b.status.textContent, /Sesi berakhir/);
});

test('edit metadata tanpa file tidak meminta URL upload', async () => {
    const b = browser({ size: null });
    await b.submit();
    assert.equal(b.calls.length, 0);
    assert.equal(b.submissions(), 1);
});

test('MIME browser kosong memakai Content-Type fallback yang ditandatangani', async () => {
    const b = browser({ type: '' });
    await b.submit();
    assert.equal(JSON.parse(b.calls[0].options.body).content_type, 'application/octet-stream');
    assert.equal(b.calls[1].options.headers['Content-Type'], 'application/octet-stream');
    assert.equal(b.submissions(), 1);
});

test('koneksi R2 gagal tidak mengirim finalisasi', async () => {
    const b = browser({ networkFails: true });
    await b.submit();
    assert.equal(b.submissions(), 0);
    assert.equal(b.token.value, '');
    assert.equal(b.input.disabled, false);
    assert.equal(b.button.disabled, false);
});

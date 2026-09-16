(() => {
    for (const marker of document.querySelectorAll('[data-unggah-langsung]')) {
        const form = marker.closest('form');
        const input = form.querySelector('input[type="file"]');
        const token = form.elements.namedItem('upload_token');
        const status = form.querySelector('[data-status-unggah]');
        let busy = false;

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (busy) return;
            const file = input.files[0];
            if (!file) {
                // Edit metadata tanpa file baru tetap diizinkan oleh validasi server.
                HTMLFormElement.prototype.submit.call(form);
                return;
            }
            if (file.size < 1 || file.size > 50 * 1024 * 1024) {
                status.textContent = 'Ukuran file harus lebih dari 0 dan maksimal 50 MB.';
                return;
            }

            busy = true;
            const buttons = [...form.querySelectorAll('button[type="submit"]')];
            buttons.forEach((button) => { button.disabled = true; });
            input.disabled = true;
            token.value = '';

            try {
                status.textContent = 'Menyiapkan upload…';
                const response = await fetch(marker.dataset.endpoint, {
                    method: 'POST', credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json', 'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': form.elements.namedItem('_token').value,
                    },
                    body: JSON.stringify({
                        tujuan: marker.dataset.tujuan,
                        target_id: marker.dataset.target || null,
                        ukuran: file.size,
                        content_type: file.type || 'application/octet-stream',
                    }),
                });
                if (!response.ok) {
                    throw new Error(response.status === 419 || response.status === 401
                        ? 'Sesi berakhir. Muat ulang halaman dan masuk kembali.'
                        : 'Upload belum dapat dimulai. Muat ulang halaman atau coba lagi.');
                }
                const izin = await response.json();
                status.textContent = 'Mengunggah file…';
                const upload = await fetch(izin.upload_url, {
                    method: 'PUT',
                    credentials: 'omit',
                    headers: { 'Content-Type': izin.headers['Content-Type'] },
                    body: file,
                    signal: AbortSignal.timeout(240000),
                });
                if (!upload.ok) throw new Error('Upload gagal. Periksa koneksi lalu coba lagi.');

                token.value = izin.finalize_token;
                status.textContent = 'File terkirim. Memeriksa dan menyimpan dokumen…';
                // Input file tidak dikirim ke aplikasi; hanya token dan metadata formulir.
                HTMLFormElement.prototype.submit.call(form);
            } catch (error) {
                status.textContent = error.message || 'Upload gagal. Silakan coba lagi.';
                token.value = '';
                input.disabled = false;
                buttons.forEach((button) => { button.disabled = false; });
                busy = false;
            }
        });
    }
})();

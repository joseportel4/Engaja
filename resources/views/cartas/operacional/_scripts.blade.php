<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-modal-open]').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById(button.dataset.modalOpen)?.classList.add('is-open');
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach((button) => {
            button.addEventListener('click', () => {
                button.closest('.cpe-modal')?.classList.remove('is-open');
            });
        });

        document.querySelectorAll('.cpe-modal__backdrop').forEach((backdrop) => {
            backdrop.addEventListener('click', () => {
                backdrop.closest('.cpe-modal')?.classList.remove('is-open');
            });
        });

        document.querySelectorAll('.cpe-modal__dialog').forEach((dialog) => {
            dialog.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });

        document.querySelectorAll('.cpe-upload input[type="file"]').forEach((input) => {
            const upload = input.closest('.cpe-upload');
            if (!upload) {
                return;
            }

            const preview = document.createElement('span');
            preview.className = 'cpe-upload-preview';
            preview.innerHTML = `
                <span class="cpe-upload-preview__thumb">ARQ</span>
                <span>
                    <span class="cpe-upload-preview__label">Arquivo selecionado</span>
                    <span class="cpe-upload-preview__name"></span>
                    <span class="cpe-upload-preview__meta"></span>
                </span>
            `;
            upload.appendChild(preview);

            input.addEventListener('change', () => {
                const file = input.files?.[0];
                const thumb = preview.querySelector('.cpe-upload-preview__thumb');
                const name = preview.querySelector('.cpe-upload-preview__name');
                const meta = preview.querySelector('.cpe-upload-preview__meta');

                if (!file) {
                    upload.classList.remove('has-file');
                    thumb.textContent = 'ARQ';
                    name.textContent = '';
                    meta.textContent = '';
                    return;
                }

                upload.classList.add('has-file');
                name.textContent = file.name;
                meta.textContent = `${formatFileSize(file.size)} - clique para trocar`;
                thumb.innerHTML = '';

                if (file.type.startsWith('image/')) {
                    const image = document.createElement('img');
                    image.alt = '';
                    image.src = URL.createObjectURL(file);
                    image.onload = () => URL.revokeObjectURL(image.src);
                    thumb.appendChild(image);
                } else {
                    thumb.textContent = file.type === 'application/pdf' ? 'PDF' : 'ARQ';
                }
            });
        });

        document.querySelectorAll('.cpe-floating-user').forEach((menu) => {
            const trigger = menu.querySelector('.cpe-user-trigger');
            trigger?.addEventListener('click', (event) => {
                event.stopPropagation();
                const isOpen = menu.classList.toggle('is-open');
                trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });

        document.addEventListener('click', () => {
            document.querySelectorAll('.cpe-floating-user').forEach((menu) => {
                menu.classList.remove('is-open');
                menu.querySelector('.cpe-user-trigger')?.setAttribute('aria-expanded', 'false');
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                document.querySelectorAll('.cpe-modal.is-open').forEach((modal) => modal.classList.remove('is-open'));
                document.querySelectorAll('.cpe-floating-user').forEach((menu) => {
                    menu.classList.remove('is-open');
                    menu.querySelector('.cpe-user-trigger')?.setAttribute('aria-expanded', 'false');
                });
            }
        });

        function formatFileSize(bytes) {
            if (bytes < 1024 * 1024) {
                return `${Math.max(1, Math.round(bytes / 1024))} KB`;
            }

            return `${(bytes / 1024 / 1024).toFixed(1).replace('.', ',')} MB`;
        }
    });
</script>

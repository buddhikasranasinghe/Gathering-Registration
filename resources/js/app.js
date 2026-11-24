const THEME_KEY = 'theme';

const applyTheme = (isDark) => {
    document.documentElement.classList.toggle('dark', isDark);
    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-pressed', String(isDark));
    });
};

const storedTheme = () => {
    try {
        return localStorage.getItem(THEME_KEY);
    } catch {
        return null;
    }
};

const prefersDark = () => window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;

applyTheme(storedTheme() ? storedTheme() === 'dark' : prefersDark());

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-theme-toggle]');

    if (!toggle) {
        return;
    }

    const isDark = ! document.documentElement.classList.contains('dark');

    applyTheme(isDark);

    try {
        localStorage.setItem(THEME_KEY, isDark ? 'dark' : 'light');
    } catch {
        // Storage can be unavailable in private browsing; the theme still applies for this page.
    }
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-filter]').forEach((form) => {
        let timer = null;

        const submit = () => form.requestSubmit();

        form.querySelectorAll('select, input[type="checkbox"]').forEach((field) => {
            field.addEventListener('change', submit);
        });

        const search = form.querySelector('input[name="search"]');

        if (search) {
            search.addEventListener('input', () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(submit, 400);
            });
        }
    });

    document.querySelectorAll('[data-tabs]').forEach((group) => {
        const tabs = Array.from(group.querySelectorAll('[data-tab]'));
        const panels = Array.from(group.querySelectorAll('[data-panel]'));

        const selectTab = (name) => {
            tabs.forEach((tab) => {
                const isActive = tab.dataset.tab === name;

                tab.setAttribute('aria-selected', String(isActive));
                tab.tabIndex = isActive ? 0 : -1;
                tab.classList.toggle('tab-button-active', isActive);
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.panel !== name;
            });

            const url = new URL(window.location.href);
            url.searchParams.set('tab', name);
            window.history.replaceState(null, '', url);
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => selectTab(tab.dataset.tab));

            tab.addEventListener('keydown', (event) => {
                if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                    return;
                }

                event.preventDefault();

                const offset = event.key === 'ArrowRight' ? 1 : -1;
                const nextTab = tabs[(index + offset + tabs.length) % tabs.length];

                nextTab.focus();
                selectTab(nextTab.dataset.tab);
            });
        });
    });

    document.querySelectorAll('[data-history-open]').forEach((button) => {
        const dialog = document.getElementById(button.dataset.historyOpen);

        if (! dialog) {
            return;
        }

        const close = () => {
            if (typeof dialog.close === 'function') {
                dialog.close();
            } else {
                dialog.removeAttribute('open');
            }
        };

        button.addEventListener('click', () => {
            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else {
                dialog.setAttribute('open', '');
            }
        });

        dialog.querySelectorAll('[data-history-close]').forEach((control) => {
            control.addEventListener('click', close);
        });

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                close();
            }
        });
    });

    const paymentDialog = document.getElementById('payment-detail');

    if (paymentDialog) {
        const editForm = document.getElementById('payment-edit-form');
        const removeSlipInput = editForm?.querySelector('[data-payment-remove-slip]') ?? null;
        const tabs = Array.from(paymentDialog.querySelectorAll('[data-payment-form-tab]'));
        const panels = Array.from(paymentDialog.querySelectorAll('[data-payment-form-panel]'));
        const slipPanel = paymentDialog.querySelector('[data-payment-form-panel="slip"]');
        const slipImage = paymentDialog.querySelector('[data-payment-slip-image]');
        const slipPdf = paymentDialog.querySelector('[data-payment-slip-pdf]');
        const slipPdfStatus = paymentDialog.querySelector('[data-payment-slip-pdf-status]');
        const slipPdfPages = paymentDialog.querySelector('[data-payment-slip-pdf-pages]');
        const slipPreview = paymentDialog.querySelector('[data-payment-slip-preview]');
        const slipEmpty = paymentDialog.querySelector('[data-payment-slip-empty]');
        const slipEmptyTitle = paymentDialog.querySelector('[data-payment-slip-empty-title]');
        const slipEmptyHint = paymentDialog.querySelector('[data-payment-slip-empty-hint]');
        const slipMeta = paymentDialog.querySelector('[data-payment-slip-meta]');
        const slipName = paymentDialog.querySelector('[data-payment-slip-name]');
        const downloadLink = paymentDialog.querySelector('[data-payment-download]');
        const openOriginalLink = paymentDialog.querySelector('[data-payment-open-original]');
        const slipActions = paymentDialog.querySelector('[data-payment-slip-actions]');
        const slipUpload = paymentDialog.querySelector('[data-payment-slip-upload]');
        const slipInput = paymentDialog.querySelector('input[name="slip"]');
        const slipSelected = paymentDialog.querySelector('[data-payment-slip-selected]');
        const slipPending = paymentDialog.querySelector('[data-payment-slip-pending-removal]');
        const slipKeep = paymentDialog.querySelector('[data-payment-slip-keep]');
        const slipChange = paymentDialog.querySelector('[data-payment-slip-change]');
        const slipRemove = paymentDialog.querySelector('[data-payment-slip-remove]');
        const defaultEmptyTitle = slipEmptyTitle.textContent;
        const defaultEmptyHint = slipEmptyHint.textContent;
        const deleteDialog = document.getElementById('payment-delete-confirm');
        const deleteForm = document.getElementById('payment-delete-form');
        const deleteSummary = deleteDialog?.querySelector('[data-payment-delete-summary]');

        let slip = { has: false, kind: 'image', name: '', url: '', pdfUrl: '', downloadUrl: '' };
        let stagedRemoval = false;
        let selectedFileUrl = null;
        let pdfSource = null;
        let pdfRenderToken = 0;
        let pdfLoadingTask = null;
        let pdfRendering = false;
        let pdfRendered = false;

        const setText = (selector, value) => {
            const element = paymentDialog.querySelector(selector);

            if (element) {
                element.textContent = value;
            }
        };

        const resetPdfPreview = () => {
            pdfRenderToken += 1;
            pdfSource = null;
            pdfRendering = false;
            pdfRendered = false;
            slipPdfPages.replaceChildren();
            slipPdfStatus.textContent = 'Loading PDF…';
            slipPdfStatus.hidden = true;

            if (pdfLoadingTask) {
                pdfLoadingTask.destroy().catch(() => {});
                pdfLoadingTask = null;
            }
        };

        const canRenderPdf = () => paymentDialog.open && ! slipPanel.hidden && ! slipPdf.hidden;

        const startPdfRenderIfNeeded = () => {
            if (pdfSource === null || pdfRendering || pdfRendered || ! canRenderPdf()) {
                return;
            }

            pdfRendering = true;
            const token = ++pdfRenderToken;
            const source = pdfSource;

            slipPdfStatus.textContent = 'Loading PDF…';
            slipPdfStatus.hidden = false;

            (async () => {
                const [pdfjs, worker] = await Promise.all([
                    import('pdfjs-dist'),
                    import('pdfjs-dist/build/pdf.worker.min.mjs?url'),
                ]);

                pdfjs.GlobalWorkerOptions.workerSrc = worker.default;

                const response = await fetch(source, { headers: { Accept: 'application/pdf' } });

                if (! response.ok) {
                    throw new Error('Unable to load the payment slip.');
                }

                const data = new Uint8Array(await response.arrayBuffer());

                if (token !== pdfRenderToken) {
                    return;
                }

                const loadingTask = pdfjs.getDocument({ data });
                pdfLoadingTask = loadingTask;
                const loadedDocument = await loadingTask.promise;

                if (token !== pdfRenderToken) {
                    loadingTask.destroy().catch(() => {});

                    return;
                }

                const width = Math.max(slipPdfPages.clientWidth, 240);
                const devicePixelRatio = window.devicePixelRatio || 1;

                for (let pageNumber = 1; pageNumber <= loadedDocument.numPages; pageNumber += 1) {
                    const page = await loadedDocument.getPage(pageNumber);

                    if (token !== pdfRenderToken) {
                        return;
                    }

                    const baseViewport = page.getViewport({ scale: 1 });
                    const viewport = page.getViewport({ scale: (width / baseViewport.width) * devicePixelRatio });
                    const canvas = document.createElement('canvas');

                    canvas.className = 'block h-auto w-full rounded-lg bg-white shadow-sm';
                    canvas.setAttribute('aria-label', `Payment slip page ${pageNumber}`);
                    canvas.width = Math.floor(viewport.width);
                    canvas.height = Math.floor(viewport.height);
                    slipPdfPages.append(canvas);

                    await page.render({ canvas, viewport }).promise;

                    if (token !== pdfRenderToken) {
                        return;
                    }
                }

                pdfRendering = false;
                pdfRendered = true;
                slipPdfStatus.hidden = true;
            })().catch((error) => {
                console.error('Payment slip PDF preview failed', error);

                if (token !== pdfRenderToken) {
                    return;
                }

                pdfRendering = false;
                pdfRendered = true;
                slipPdfPages.replaceChildren();
                slipPdfStatus.textContent = 'Could not preview this PDF. Use Open original instead.';
                slipPdfStatus.hidden = false;
            });
        };

        const setTab = (name) => {
            tabs.forEach((tab) => {
                tab.setAttribute('aria-selected', tab.dataset.paymentFormTab === name ? 'true' : 'false');
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.paymentFormPanel !== name;
            });

            if (name === 'slip') {
                startPdfRenderIfNeeded();
            }
        };

        const revokeSelectedFileUrl = () => {
            if (selectedFileUrl !== null) {
                URL.revokeObjectURL(selectedFileUrl);
                selectedFileUrl = null;
            }
        };

        const clearSelectedFile = () => {
            if (slipInput) {
                slipInput.value = '';
            }

            revokeSelectedFileUrl();
        };

        const clearMedia = () => {
            slipImage.removeAttribute('src');
            slipImage.hidden = true;
            slipPdf.hidden = true;
            slipPreview.hidden = true;
            resetPdfPreview();
        };

        const showMedia = (source, kind) => {
            slipPreview.hidden = false;

            if (kind === 'image') {
                slipPdf.hidden = true;
                slipImage.hidden = false;
                slipImage.src = source;

                return;
            }

            slipImage.hidden = true;
            slipImage.removeAttribute('src');
            slipPdf.hidden = false;
            pdfSource = source;
            pdfRendering = false;
            pdfRendered = false;
            slipPdfPages.replaceChildren();
            slipPdfStatus.textContent = 'Loading PDF…';
            slipPdfStatus.hidden = false;
            startPdfRenderIfNeeded();
        };

        const renderSlip = () => {
            if (removeSlipInput) {
                removeSlipInput.value = stagedRemoval ? '1' : '0';
            }

            const file = slipInput?.files[0] ?? null;

            clearMedia();
            slipEmpty.hidden = true;
            slipMeta.hidden = true;

            if (slipActions) {
                slipActions.hidden = true;
            }

            if (slipUpload) {
                slipUpload.hidden = true;
            }

            if (slipSelected) {
                slipSelected.hidden = true;
            }

            if (slipPending) {
                slipPending.hidden = true;
            }

            if (slipKeep) {
                slipKeep.hidden = true;
            }

            if (file) {
                const isHeic = /\.(heic|heif)$/i.test(file.name) || ['image/heic', 'image/heif'].includes(file.type);

                if (isHeic) {
                    slipEmpty.hidden = false;
                    slipEmptyTitle.textContent = 'HEIC image selected.';
                    slipEmptyHint.textContent = 'It will be converted to JPEG when you save.';
                } else {
                    const isImage = file.type.startsWith('image/') || /\.(jpe?g|png|webp|gif)$/i.test(file.name);

                    showMedia(selectedFileUrl, isImage ? 'image' : 'pdf');
                }

                if (slipSelected) {
                    slipSelected.textContent = `Selected: ${file.name}`;
                    slipSelected.hidden = false;
                }

                if (slipPending) {
                    slipPending.textContent = slip.has
                        ? 'The current slip will be replaced when you save.'
                        : 'This slip will be uploaded when you save.';
                    slipPending.hidden = false;
                }

                if (slipUpload) {
                    slipUpload.hidden = false;
                }

                return;
            }

            if (slip.has && ! stagedRemoval) {
                showMedia(slip.kind === 'image' ? slip.url : slip.pdfUrl, slip.kind);
                slipMeta.hidden = false;
                slipName.textContent = slip.name;
                downloadLink.href = slip.downloadUrl;
                openOriginalLink.href = slip.url;

                if (slipActions) {
                    slipActions.hidden = false;
                }

                return;
            }

            slipEmpty.hidden = false;

            if (slip.has) {
                slipEmptyTitle.textContent = 'The current slip will be removed when you save.';
                slipEmptyHint.textContent = 'Choose a replacement file below, or save to remove it.';
            } else {
                slipEmptyTitle.textContent = defaultEmptyTitle;
                slipEmptyHint.textContent = defaultEmptyHint;
            }

            if (slipUpload) {
                slipUpload.hidden = false;
            }

            if (slipKeep) {
                slipKeep.hidden = ! slip.has;
            }

            if (slipPending) {
                slipPending.textContent = 'The current slip will be removed when you save.';
                slipPending.hidden = ! slip.has;
            }
        };

        const closePaymentDialog = () => {
            if (typeof paymentDialog.close === 'function') {
                paymentDialog.close();
            } else {
                paymentDialog.removeAttribute('open');
            }

            clearSelectedFile();
            clearMedia();
        };

        const openPaymentDialog = (row) => {
            const data = row.dataset;

            setText('[data-payment-summary]', `${data.paymentAmount} on ${data.paymentDate}`);
            setText('[data-payment-field-added-by]', data.paymentAddedBy);
            setText('[data-payment-field-updated-by]', data.paymentUpdatedBy);
            setText('[data-payment-read-date]', data.paymentDate);
            setText('[data-payment-read-amount]', data.paymentAmount);
            setText('[data-payment-read-method]', data.paymentMethod);

            slip = {
                has: data.paymentHasSlip === '1',
                kind: data.paymentSlipKind === 'image' ? 'image' : 'pdf',
                name: data.paymentSlipName,
                url: data.paymentSlipUrl,
                pdfUrl: data.paymentSlipPdfUrl,
                downloadUrl: data.paymentDownloadUrl,
            };

            stagedRemoval = data.paymentOldRemoveSlip === '1';
            clearSelectedFile();

            if (editForm) {
                editForm.action = paymentDialog.dataset.updateRouteTemplate.replace('__PAYMENT__', data.paymentId);
                editForm.querySelector('input[name="payment_id"]').value = data.paymentId;
                paymentDialog.querySelector('input[name="amount"]').value = data.paymentOldAmount || data.paymentRawAmount;
                paymentDialog.querySelector('select[name="method"]').value = data.paymentOldMethod || data.paymentMethodValue;
                paymentDialog.querySelector('input[name="paid_at"]').value = data.paymentOldDate || data.paymentRawDate;
            }

            setTab(data.paymentErrorTab === 'slip' ? 'slip' : 'details');
            renderSlip();

            if (typeof paymentDialog.showModal === 'function') {
                paymentDialog.showModal();
            } else {
                paymentDialog.setAttribute('open', '');
            }

            startPdfRenderIfNeeded();
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => setTab(tab.dataset.paymentFormTab));
        });

        document.querySelectorAll('[data-payment-open]').forEach((row) => {
            row.addEventListener('click', (event) => {
                if (event.target.closest('a, button, input, select, textarea')) {
                    return;
                }

                openPaymentDialog(row);
            });

            row.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                event.preventDefault();
                openPaymentDialog(row);
            });

            if (row.dataset.paymentOpenError === '1') {
                openPaymentDialog(row);
            }
        });

        const closeDeleteDialog = () => {
            if (typeof deleteDialog.close === 'function') {
                deleteDialog.close();
            } else {
                deleteDialog.removeAttribute('open');
            }
        };

        const openDeleteDialog = (row) => {
            if (! deleteDialog || ! deleteForm || ! deleteSummary) {
                return;
            }

            deleteForm.action = deleteDialog.dataset.deleteRouteTemplate.replace('__PAYMENT__', row.dataset.paymentId);
            deleteSummary.textContent = `${row.dataset.paymentAmount} on ${row.dataset.paymentDate}`;

            if (typeof deleteDialog.showModal === 'function') {
                deleteDialog.showModal();
            } else {
                deleteDialog.setAttribute('open', '');
            }
        };

        document.querySelectorAll('[data-payment-delete]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const row = button.closest('[data-payment-open]');

                if (row) {
                    openDeleteDialog(row);
                }
            });
        });

        deleteDialog?.querySelectorAll('[data-payment-delete-cancel]').forEach((control) => {
            control.addEventListener('click', closeDeleteDialog);
        });

        deleteDialog?.addEventListener('click', (event) => {
            if (event.target === deleteDialog) {
                closeDeleteDialog();
            }
        });

        slipChange?.addEventListener('click', () => {
            stagedRemoval = true;
            clearSelectedFile();
            renderSlip();
            slipInput?.focus();
        });

        slipRemove?.addEventListener('click', () => {
            stagedRemoval = true;
            clearSelectedFile();
            renderSlip();
        });

        slipKeep?.addEventListener('click', () => {
            stagedRemoval = false;
            clearSelectedFile();
            renderSlip();
        });

        slipInput?.addEventListener('change', () => {
            revokeSelectedFileUrl();

            const file = slipInput.files[0] ?? null;

            if (file) {
                selectedFileUrl = URL.createObjectURL(file);
            }

            renderSlip();
        });

        paymentDialog.querySelectorAll('[data-payment-close]').forEach((control) => {
            control.addEventListener('click', closePaymentDialog);
        });

        paymentDialog.addEventListener('click', (event) => {
            if (event.target === paymentDialog) {
                closePaymentDialog();
            }
        });
    }

    document.querySelectorAll('[data-attendance-toggle]').forEach((toggle) => {
        toggle.addEventListener('change', () => toggle.form?.requestSubmit());
    });

    document.querySelectorAll('[data-open-tab]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const tab = link.closest('[data-tabs]')?.querySelector(`[data-tab="${link.dataset.openTab}"]`);

            if (! tab) {
                return;
            }

            event.preventDefault();
            tab.click();
            tab.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
});

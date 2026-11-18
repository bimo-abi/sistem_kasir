//

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';

const transactionToastContainer = document.getElementById('transaction-toast-container');

if (transactionToastContainer && window.Echo) {
    window.Echo.private('transactions').listen('.transaction.completed', (event) => {
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-success border-0';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.setAttribute('aria-atomic', 'true');

        const content = document.createElement('div');
        content.className = 'toast-body';
        const total = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(Number(event.grand_total));
        content.textContent = `Transaksi ${event.invoice_number} selesai. Total ${total}.`;

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close btn-close-white me-2 m-auto';
        closeButton.setAttribute('data-bs-dismiss', 'toast');
        closeButton.setAttribute('aria-label', 'Tutup');

        const contentWrapper = document.createElement('div');
        contentWrapper.className = 'd-flex';
        contentWrapper.append(content, closeButton);
        toast.append(contentWrapper);
        transactionToastContainer.append(toast);

        toast.addEventListener('hidden.bs.toast', () => toast.remove(), { once: true });
        window.bootstrap.Toast.getOrCreateInstance(toast, { delay: 7000 }).show();
    });
}

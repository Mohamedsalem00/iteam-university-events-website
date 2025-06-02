function showSuccessModal(title, message, onClose) {
    const modal = document.getElementById('success-modal');
    const titleEl = document.getElementById('success-modal-title');
    const messageEl = document.getElementById('success-modal-message');
    const okBtn = document.getElementById('success-modal-ok');
    const backdrop = document.getElementById('success-modal-backdrop');
    
    titleEl.textContent = title || 'Success!';
    messageEl.textContent = message || 'Your request was processed successfully.';
    
    modal.classList.remove('hidden');
    
    // Setup event listeners
    const closeModal = () => {
        modal.classList.add('hidden');
        if (onClose && typeof onClose === 'function') {
            onClose();
        }
    };
    
    okBtn.addEventListener('click', closeModal, { once: true });
    backdrop.addEventListener('click', closeModal, { once: true });
}

function showErrorModal(title, message, onClose) {
    const modal = document.getElementById('error-modal');
    const titleEl = document.getElementById('error-modal-title');
    const messageEl = document.getElementById('error-modal-message');
    const okBtn = document.getElementById('error-modal-ok');
    const backdrop = document.getElementById('error-modal-backdrop');
    
    titleEl.textContent = title || 'Error';
    messageEl.textContent = message || 'An error occurred while processing your request.';
    
    modal.classList.remove('hidden');
    
    // Setup event listeners
    const closeModal = () => {
        modal.classList.add('hidden');
        if (onClose && typeof onClose === 'function') {
            onClose();
        }
    };
    
    okBtn.addEventListener('click', closeModal, { once: true });
    backdrop.addEventListener('click', closeModal, { once: true });
}

function showConfirmModal(title, message, onConfirm, onCancel) {
    const modal = document.getElementById('confirm-modal');
    const titleEl = document.getElementById('confirm-modal-title');
    const messageEl = document.getElementById('confirm-modal-message');
    const confirmBtn = document.getElementById('confirm-modal-confirm');
    const cancelBtn = document.getElementById('confirm-modal-cancel');
    const backdrop = document.getElementById('confirm-modal-backdrop');
    
    titleEl.textContent = title || 'Confirm Action';
    messageEl.textContent = message || 'Are you sure you want to perform this action?';
    
    modal.classList.remove('hidden');
    
    // Setup event listeners
    const closeModal = () => {
        modal.classList.add('hidden');
    };
    
    const handleCancel = () => {
        closeModal();
        if (onCancel && typeof onCancel === 'function') {
            onCancel();
        }
    };
    
    const handleConfirm = () => {
        closeModal();
        if (onConfirm && typeof onConfirm === 'function') {
            onConfirm();
        }
    };
    
    confirmBtn.addEventListener('click', handleConfirm, { once: true });
    cancelBtn.addEventListener('click', handleCancel, { once: true });
    backdrop.addEventListener('click', handleCancel, { once: true });
}
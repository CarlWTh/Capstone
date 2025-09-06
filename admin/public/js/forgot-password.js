document.addEventListener('DOMContentLoaded', function() {
    const forgotPasswordForm = document.querySelector('.forgot-password-form');
    
    forgotPasswordForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const email = document.getElementById('email').value.trim();

        if (!email) {
            showErrorMessage('Please enter your email');
            return;
        }

        const isEmailSent = await sendResetEmail(email);

        if (isEmailSent) {
            alert('A password reset link has been sent to your email.');
            window.location.href = 'login.html';
        } else {
            showErrorMessage('Failed to send reset email. Please try again.');
        }
    });

    function showErrorMessage(message) {
        const existingError = document.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.color = '#e74c3c';
        errorDiv.style.marginBottom = '10px';
        errorDiv.style.textAlign = 'center';
        
        const forgotPasswordForm = document.querySelector('.forgot-password-form');
        forgotPasswordForm.insertBefore(errorDiv, forgotPasswordForm.firstChild);
    }

    async function sendResetEmail(email) {
        console.log('Sending reset email to:', email);
        // Simulate API call
        return true;
    }
});
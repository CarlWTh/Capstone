document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('.login-form');

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        if (!username) {
            showErrorMessage('Please enter your username');
            return;
        }

        if (!password) {
            showErrorMessage('Please enter your password');
            return;
        }

        // Call backend for authentication
        try {
            const response = await fetch('../../../private/helpers/login_backend.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username,
                    password,
                    remember: document.getElementById('remember') ? document.getElementById('remember').checked : false
                })
            });
            const data = await response.json();
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            } else if (data.error) {
                showErrorMessage(data.error);
            } else {
                showErrorMessage('Invalid username or password');
            }
        } catch (err) {
            showErrorMessage('An error occurred. Please try again.');
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

        const loginForm = document.querySelector('.login-form');
        loginForm.insertBefore(errorDiv, loginForm.firstChild);
    }
});
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.querySelector('.register-form');
    
    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm-password').value;

        if (!username) {
            showErrorMessage('Please enter your username');
            return;
        }

        if (!email) {
            showErrorMessage('Please enter your email');
            return;
        }

        if (!password) {
            showErrorMessage('Please enter your password');
            return;
        }

        if (password !== confirmPassword) {
            showErrorMessage('Passwords do not match');
            return;
        }

        const isRegistered = await registerUser(username, email, password);

        if (isRegistered) {
            alert('Registration successful! Redirecting to login...');
            window.location.href = 'login.html';
        } else {
            showErrorMessage('Registration failed. Please try again.');
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
        
        const registerForm = document.querySelector('.register-form');
        registerForm.insertBefore(errorDiv, registerForm.firstChild);
    }

    async function registerUser(username, email, password) {
        console.log('Registering:', { username, email, password });
        return true; 
    }
});
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

        const isAuthenticated = await authenticateUser(username, password);

        if (isAuthenticated) {
            window.location.href = 'dashboard.html';
        } else {
            showErrorMessage('Invalid username or password');
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

    async function authenticateUser(username, password) {
        // Replace this with an actual API call to your server
        const expectedPassword = 'admin123'; // Plain text password
        console.log('Authenticating:', { username, password });
        return username === 'admin' && password === expectedPassword;
    }
});
document.addEventListener('DOMContentLoaded', () => {
    
    const loginForm = document.getElementById('login-form');
    const errorMessage = document.getElementById('error-message');
    const loginButton = document.getElementById('login-button');
    const buttonText = document.getElementById('button-text');
    const buttonSpinner = document.getElementById('button-spinner');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Stop the form from submitting normally
            
            // Show loading state
            loginButton.disabled = true;
            buttonText.classList.add('hidden');
            buttonSpinner.classList.remove('hidden');
            errorMessage.classList.add('hidden');

            // Get form data
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                const response = await fetch('api/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                });

                if (response.ok) {
                    // Login was successful
                    const result = await response.json();
                    
                    // Redirect to the main dashboard
                    window.location.href = 'dashboard.php';

                } else {
                    // Login failed (e.g., 401 Unauthorized)
                    const errorData = await response.json();
                    errorMessage.textContent = errorData.error || 'Invalid email or password.';
                    errorMessage.classList.remove('hidden');
                }

            } catch (error) {
                // Network error or other fetch-related error
                console.error('Login error:', error);
                errorMessage.textContent = 'An error occurred. Please try again.';
                errorMessage.classList.remove('hidden');
            } finally {
                // Restore button state
                loginButton.disabled = false;
                buttonText.classList.remove('hidden');
                buttonSpinner.classList.add('hidden');
            }
        });
    }
});
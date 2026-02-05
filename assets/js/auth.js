/**
 * Authentication Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // Login Form Handler
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // Register Form Handler
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
});

async function handleLogin(e) {
    e.preventDefault();
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Logging in...';

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    try {
        const result = await apiRequest('/auth/login.php', 'POST', data);

        if (result.success) {
            // Save user to local storage
            localStorage.setItem('user', JSON.stringify(result.user));

            // Redirect based on role
            if (result.user.user_type === 'employer') {
                window.location.href = '/Job_Hiring/pages/employer/dashboard.html';
            } else {
                window.location.href = '/Job_Hiring/pages/jobseeker/dashboard.html';
            }
        }
    } catch (error) {
        showAlert(error.message, 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Login';
    }
}

async function handleRegister(e) {
    e.preventDefault();
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating Account...';

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    // Basic validation
    if (data.password !== data.confirm_password) {
        showAlert('Passwords do not match', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Account';
        return;
    }

    try {
        const result = await apiRequest('/auth/register.php', 'POST', data);

        if (result.success) {
            showAlert('Registration successful! Please login.', 'success');
            setTimeout(() => {
                window.location.href = '/Job_Hiring/pages/login.html';
            }, 2000);
        }
    } catch (error) {
        showAlert(error.message, 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Account';
    }
}

async function handleProfileUpload(input) {
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];
    const formData = new FormData();
    formData.append('profile_picture', file);

    const btn = document.getElementById('upload-btn-text');
    if (btn) btn.textContent = 'Uploading...';

    try {
        const result = await apiRequest('/users/update_profile.php', 'POST', formData);

        if (result.success) {
            // Update local storage
            const user = JSON.parse(localStorage.getItem('user'));
            user.profile_picture = result.profile_picture;
            localStorage.setItem('user', JSON.stringify(user));

            // Update UI
            document.querySelectorAll('.profile-avatar-large').forEach(img => {
                img.src = result.profile_picture_url;
            });

            showAlert('Profile picture updated!', 'success');
        }
    } catch (error) {
        showAlert(error.message, 'error');
    } finally {
        if (btn) btn.textContent = 'Change Photo';
    }
}

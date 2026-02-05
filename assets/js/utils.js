/**
 * Utility functions and Helpers
 */

const API_BASE_URL = 'http://localhost/Job_Hiring/api';

/**
 * Wrapper for Fetch API with error handling and default headers
 */
async function apiRequest(endpoint, method = 'GET', data = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
        }
    };

    if (data) {
        // Handle FormData for file uploads specifically
        if (data instanceof FormData) {
            delete options.headers['Content-Type']; // Let browser set boundary
            options.body = data;
        } else {
            options.body = JSON.stringify(data);
        }
    }

    try {
        const response = await fetch(`${API_BASE_URL}${endpoint}`, options);
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'Something went wrong');
        }

        return result;
    } catch (error) {
        console.error('API Request Failed:', error);
        throw error;
    }
}

/**
 * Show alert message
 */
function showAlert(message, type = 'error') {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;

    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;

    alertContainer.appendChild(alert);

    // Auto dismiss
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

/**
 * Check if user is logged in
 */
function checkAuth(requiredRole = null) {
    const user = JSON.parse(localStorage.getItem('user'));

    if (!user) {
        window.location.href = '/Job_Hiring/pages/login.html';
        return null;
    }

    if (requiredRole && user.user_type !== requiredRole) {
        window.location.href = '/Job_Hiring/index.html';
        return null;
    }

    // Update UI for logged in user
    updateNav(user);

    return user;
}

/**
 * Update Navigation based on auth state
 */
function updateNav(user) {
    const navLinks = document.querySelector('.nav-links');
    if (!navLinks) return;

    if (user) {
        const dashboardLink = user.user_type === 'employer'
            ? '/Job_Hiring/pages/employer/dashboard.html'
            : '/Job_Hiring/pages/jobseeker/dashboard.html';

        navLinks.innerHTML = `
            <a href="${dashboardLink}" class="nav-link">Dashboard</a>
            ${user.user_type === 'employer' ? '<a href="/Job_Hiring/pages/employer/post-job.html" class="nav-link">Post Job</a>' : ''}
            <span class="nav-link">Welcome, ${user.full_name}</span>
            <button onclick="logout()" class="btn btn-outline btn-sm">Logout</button>
        `;
    }
}

/**
 * Handle Logout
 */
async function logout() {
    try {
        await apiRequest('/auth/logout.php', 'POST');
        localStorage.removeItem('user');
        window.location.href = '/Job_Hiring/pages/login.html';
    } catch (error) {
        console.error('Logout failed', error);
    }
}

/**
 * Toggle Password Visibility
 */
function togglePasswordVisibility(inputId, toggleBtn) {
    const input = document.getElementById(inputId);
    const icon = toggleBtn.querySelector('span'); // Assuming simple text/emoji for now or use class switch

    if (input.type === 'password') {
        input.type = 'text';
        toggleBtn.innerHTML = '👁️‍🗨️'; // Or different icon
        toggleBtn.title = 'Hide password';
    } else {
        input.type = 'password';
        toggleBtn.innerHTML = '👁️';
        toggleBtn.title = 'Show password';
    }
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    if (!amount) return 'Not specified';
    // Remove any non-numeric characters if it's a string range like "20k-30k"
    if (isNaN(amount) && typeof amount === 'string') return amount;

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

/**
 * Format date
 */
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

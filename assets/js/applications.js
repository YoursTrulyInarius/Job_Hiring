/**
 * Application Management Logic
 */

// Handle Application Submission
async function handleApplicationSubmit(e) {
    e.preventDefault();
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    const formData = new FormData(e.target);

    try {
        // FormData is automatically handled by apiRequest wrapper in utils.js
        const result = await apiRequest('/applications/submit.php', 'POST', formData);

        if (result.success) {
            showAlert('Application submitted successfully!', 'success');
            e.target.reset();
            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 2000);
        }
    } catch (error) {
        showAlert(error.message, 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Application';
    }
}

// Load Applications (Employer View)
async function loadEmployerApplications() {
    const container = document.getElementById('applications-list');
    if (!container) return;

    try {
        const result = await apiRequest('/applications/list.php');

        if (result.data.length === 0) {
            container.innerHTML = '<p>No applications received yet.</p>';
            return;
        }

        container.innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Applicant</th>
                        <th>Email</th>
                        <th>Date</th>
                        <th>Resume</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${result.data.map(app => `
                        <tr>
                            <td>${app.job_title}</td>
                            <td>${app.applicant_name}</td>
                            <td>${app.applicant_email}</td>
                            <td>${formatDate(app.applied_at)}</td>
                            <td><a href="/Job_Hiring/${app.resume_path}" target="_blank" class="text-accent">View Resume</a></td>
                            <td><span class="badge badge-${app.status}">${app.status}</span></td>
                            <td>
                                ${app.status === 'pending' ? `
                                    <button onclick="updateStatus(${app.id}, 'accepted')" class="btn-icon accept">✓</button>
                                    <button onclick="updateStatus(${app.id}, 'rejected')" class="btn-icon reject">✗</button>
                                ` : ''}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    } catch (error) {
        container.innerHTML = `<div class="alert alert-error">${error.message}</div>`;
    }
}

// Load Applications (Job Seeker View)
async function loadMyApplications() {
    const container = document.getElementById('my-applications');
    if (!container) return;

    try {
        const result = await apiRequest('/applications/list.php');

        if (result.data.length === 0) {
            container.innerHTML = '<p>You haven\'t applied to any jobs yet.</p>';
            return;
        }

        container.innerHTML = result.data.map(app => `
            <div class="card mb-1">
                <div class="flex justify-between items-center">
                    <div>
                        <h3>${app.job_title}</h3>
                        <p class="text-muted">${app.employer_name} • ${app.location}</p>
                    </div>
                    <span class="badge badge-${app.status}">${app.status}</span>
                </div>
                <div class="mt-1 text-sm text-muted">
                    Applied on: ${formatDate(app.applied_at)}
                </div>
            </div>
        `).join('');
    } catch (error) {
        container.innerHTML = `<div class="alert alert-error">${error.message}</div>`;
    }
}

async function updateStatus(id, status) {
    if (!confirm(`Mark application as ${status}?`)) return;

    try {
        const result = await apiRequest('/applications/update_status.php', 'POST', { id, status });
        if (result.success) {
            showAlert(`Application ${status}`, 'success');
            loadEmployerApplications(); // Reload list
        }
    } catch (error) {
        showAlert(error.message, 'error');
    }
}

/**
 * Jobs Management Logic
 */

// Load jobs based on context
async function loadJobs(containerId, filters = {}) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '<div class="text-center">Loading jobs...</div>';

    try {
        // Construct query string
        const params = new URLSearchParams(filters);
        const endpoint = `/fetch_jobs.php?${params.toString()}`;

        const result = await apiRequest(endpoint);

        if (result.data.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">No jobs found.</p>';
            return;
        }

        container.innerHTML = result.data.map(job => renderJobCard(job)).join('');
    } catch (error) {
        container.innerHTML = `<p class="text-error">Failed to load jobs: ${error.message}</p>`;
    }
}

// Render single job card
function renderJobCard(job) {
    const isEmployer = JSON.parse(localStorage.getItem('user'))?.user_type === 'employer';

    return `
        <div class="card job-card">
            <div class="job-card-header">
                <div class="employer-info">
                    <img src="${job.profile_picture ? '/Job_Hiring/assets/uploads/profile_pictures/' + job.profile_picture : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(job.employer_name) + '&background=random'}" 
                         alt="${job.employer_name}" class="profile-avatar-small">
                    <div>
                        <h3>${job.title}</h3>
                        <p class="employer-name">${job.employer_name}</p>
                    </div>
                </div>
                <span class="badge ${job.status === 'active' ? 'badge-active' : 'badge-closed'}">
                    ${job.job_type}
                </span>
            </div>
            
            <div class="job-card-meta">
                <span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    ${job.location}
                </span>
                <span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 19v-14h3.5a4.5 4.5 0 1 1 0 9h-3.5"></path>
                        <path d="M18 8h-12"></path>
                        <path d="M18 11h-12"></path>
                    </svg>
                    ${isNaN(job.salary_range) ? job.salary_range : formatCurrency(job.salary_range)}
                </span>
                <span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    ${formatDate(job.created_at)}
                </span>
            </div>
            
            <p class="job-description-preview">${job.description}</p>
            
            <div class="job-card-actions">
                <a href="/Job_Hiring/pages/jobseeker/job-details.html?id=${job.id}" class="btn btn-primary btn-sm">View Details</a>
                
                ${isEmployer && job.employer_id == JSON.parse(localStorage.getItem('user')).id ? `
                    <a href="/Job_Hiring/pages/employer/view-applications.html?job_id=${job.id}" class="btn btn-accent btn-sm">View Applications</a>
                    <button onclick="deleteJob(${job.id})" class="btn btn-danger btn-sm">Delete</button>
                ` : ''}
            </div>
        </div>
    `;
}

// Post Job Handler
async function handlePostJob(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Posting...';

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    try {
        const result = await apiRequest('/jobs/create.php', 'POST', data);
        if (result.success) {
            showAlert('Job posted successfully!', 'success');
            setTimeout(() => {
                window.location.href = '/Job_Hiring/pages/employer/dashboard.html';
            }, 1000);
        }
    } catch (error) {
        showAlert(error.message, 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Post Job';
    }
}

// Load full job details
async function loadJobDetails() {
    const urlParams = new URLSearchParams(window.location.search);
    const jobId = urlParams.get('id');

    if (!jobId) {
        window.location.href = 'dashboard.html';
        return;
    }

    try {
        const result = await apiRequest(`/jobs/details.php?id=${jobId}`);
        const job = result.data;

        // Render job details with better layout
        document.getElementById('job-title').textContent = job.title;

        // Meta info with icons
        const metaContainer = document.querySelector('.job-header-meta');
        metaContainer.innerHTML = `
            <span id="employer-name" style="font-weight: 600; width: 100%; margin-bottom: 0.5rem; font-size: 1.1rem; align-items: center; display: flex;">
                <img src="${job.profile_picture ? '/Job_Hiring/assets/uploads/profile_pictures/' + job.profile_picture : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(job.employer_name) + '&background=random'}" 
                     class="profile-avatar-small" style="margin-right: 0.75rem; border: 2px solid rgba(255,255,255,0.2);">
                ${job.employer_name}
            </span>
            <span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                ${job.location}
            </span>
            <span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                ${job.job_type}
            </span>
            <span>
                 <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 19v-14h3.5a4.5 4.5 0 1 1 0 9h-3.5"></path>
                    <path d="M18 8h-12"></path>
                    <path d="M18 11h-12"></path>
                </svg>
                ${isNaN(job.salary_range) ? job.salary_range : formatCurrency(job.salary_range)}
            </span>
            <span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Posted on ${formatDate(job.created_at)}
            </span>
        `;

        document.getElementById('job-description').style.whiteSpace = 'pre-line';
        document.getElementById('job-description').textContent = job.description;

        document.getElementById('job-requirements').innerHTML = job.requirements || 'No specific requirements listed.';
        document.getElementById('job-requirements').style.whiteSpace = 'pre-line';

        // Setup hidden input for application
        const jobInput = document.getElementById('job-id-input');
        if (jobInput) jobInput.value = job.id;

    } catch (error) {
        showAlert('Failed to load job details', 'error');
    }
}

async function deleteJob(id) {
    if (!confirm('Are you sure you want to delete this job?')) return;

    try {
        const result = await apiRequest('/jobs/delete.php', 'POST', { id });
        if (result.success) {
            showAlert('Job deleted', 'success');
            location.reload();
        }
    } catch (error) {
        showAlert(error.message, 'error');
    }
}

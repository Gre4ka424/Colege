// Check if user is logged in and has admin role
const token = localStorage.getItem('token');
const userRole = localStorage.getItem('userRole');

console.log("Admin page - token:", token);
console.log("Admin page - userRole:", userRole);

if (!token || userRole !== '1') {
    console.log("Not authorized as admin, redirecting to login page");
    window.location.href = 'admin-login.html';
}

// Load users list
async function loadUsers() {
    try {
        const response = await fetch('../Backend/api.php/users', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch users');
        }

        const result = await response.json();
        console.log("Users data received:", result);
        
        // Проверяем формат ответа API
        const users = result.data || (Array.isArray(result) ? result : []);
        
        const tbody = document.querySelector('#usersTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '';
        users.forEach(user => {
            // Обрабатываем имя роли
            const roleName = user.role_name || (user.role_id === 1 ? 'Administrator' : 
                                               user.role_id === 2 ? 'Manager' : 
                                               user.role_id === 3 ? 'Regular User' : 'Unknown');
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${user.user_id}</td>
                <td>${user.full_name}</td>
                <td>${user.email}</td>
                <td>${user.student_id || 'N/A'}</td>
                <td>${user.phone || 'N/A'}</td>
                <td><span class="badge bg-info">${roleName}</span></td>
                <td>${user.status || 'pending'}</td>
                <td>
                    <button class="btn btn-sm btn-success approve-user" data-id="${user.user_id}">
                        Approve
                    </button>
                    <button class="btn btn-sm btn-danger reject-user" data-id="${user.user_id}">
                        Reject
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
        
        // Добавляем обработчики для кнопок
        document.querySelectorAll('.approve-user').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                alert(`Approval for user ${userId} will be implemented soon.`);
            });
        });
        
        document.querySelectorAll('.reject-user').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                alert(`Rejection for user ${userId} will be implemented soon.`);
            });
        });
    } catch (error) {
        console.error('Error loading users:', error);
        alert('Error loading users list. Please try again.');
    }
}

// Load deletion requests
async function loadDeletionRequests() {
    try {
        const response = await fetch('../Backend/api.php/deletion_requests', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch deletion requests');
        }

        const data = await response.json();
        const tbody = document.querySelector('#deletionRequestsTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '';
        data.forEach(request => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${request.request_id}</td>
                <td>${request.full_name}<br><small>${request.email}</small></td>
                <td>${request.request_type}</td>
                <td>${request.item_id}</td>
                <td>${new Date(request.request_date).toLocaleString()}</td>
                <td>${request.admin_comment || '-'}</td>
                <td><span class="badge ${getStatusBadgeClass(request.status)}">${request.status || 'pending'}</span></td>
                <td>
                    ${(!request.status || request.status === 'pending') ? `
                        <button class="btn btn-sm btn-primary" onclick="showProcessModal(${request.request_id})">
                            Process
                        </button>
                    ` : '-'}
                </td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        console.error('Error loading deletion requests:', error);
        alert('Error loading deletion requests. Please try again.');
    }
}

// Show process modal
function showProcessModal(requestId) {
    document.getElementById('requestId').value = requestId;
    document.getElementById('adminComment').value = '';
    const modal = new bootstrap.Modal(document.getElementById('processRequestModal'));
    modal.show();
}

// Process deletion request
async function processDeletionRequest(status) {
    const requestId = document.getElementById('requestId').value;
    const comment = document.getElementById('adminComment').value.trim();

    if (!comment) {
        alert('Please provide a comment');
        return;
    }

    try {
        const response = await fetch('../Backend/api.php/process_deletion', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                request_id: requestId,
                status: status,
                admin_comment: comment
            })
        });

        if (!response.ok) {
            throw new Error('Failed to process deletion request');
        }

        const modal = bootstrap.Modal.getInstance(document.getElementById('processRequestModal'));
        modal.hide();
        loadDeletionRequests();
    } catch (error) {
        console.error('Error processing deletion request:', error);
        alert('Error processing request. Please try again.');
    }
}

// Helper function for status badge classes
function getStatusBadgeClass(status) {
    switch (status) {
        case 'pending': return 'bg-warning';
        case 'approved': return 'bg-success';
        case 'rejected': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

// Load appropriate content based on navigation
function loadContent(page) {
    const pageTitle = document.getElementById('page-title');
    const pageContent = document.getElementById('page-content');
    
    switch (page) {
        case 'users':
            pageTitle.textContent = 'Users Management';
            pageContent.innerHTML = `
                <div class="table-responsive">
                    <table class="table table-striped" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Student ID</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            `;
            loadUsers();
            break;
            
        case 'deletion-requests':
            pageTitle.textContent = 'Deletion Requests';
            pageContent.innerHTML = `
                <div class="table-responsive">
                    <table class="table table-striped" id="deletionRequestsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Item ID</th>
                                <th>Date</th>
                                <th>Comment</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            `;
            loadDeletionRequests();
            break;
            
        default: // dashboard
            pageTitle.textContent = 'Dashboard';
            pageContent.innerHTML = `
                <div class="row">
                    <div class="col-md-12">
                        <h3>Welcome to PRS Administration</h3>
                        <p>Select an option from the sidebar to manage users and deletion requests.</p>
                    </div>
                </div>
            `;
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    // Initial content load
    loadContent('dashboard');
    
    // Navigation event listeners
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = e.target.closest('.nav-link').dataset.page;
            if (page) {
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                e.target.closest('.nav-link').classList.add('active');
                loadContent(page);
            }
        });
    });

    // Process request buttons
    document.getElementById('approveRequestBtn')?.addEventListener('click', () => {
        processDeletionRequest('approved');
    });

    document.getElementById('rejectRequestBtn')?.addEventListener('click', () => {
        processDeletionRequest('rejected');
    });

    // Logout button
    document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('userRole');
        window.location.href = 'admin-login.html';
    });

    // Logout в сайдбаре
    document.getElementById('logout')?.addEventListener('click', (e) => {
        e.preventDefault();
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('userRole');
        window.location.href = 'admin-login.html';
    });
}); 
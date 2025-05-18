document.addEventListener("DOMContentLoaded", function() {
    // Определяем, на какой странице мы находимся
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    console.log("Current page:", currentPage);
    
    // Проверяем токен и роль при загрузке любой страницы
    const token = localStorage.getItem('token');
    const userRole = localStorage.getItem('userRole');
    console.log("Current token:", token);
    console.log("Current userRole:", userRole);
    
    if (!token) {
        // Если нет токена и страница не login.html или register.html, перенаправляем на логин
        if (currentPage !== 'login.html' && currentPage !== 'register.html') {
            console.log("No token found, redirecting to login");
            window.location.href = 'login.html';
            return;
        }
    } else {
        // Если есть токен, проверяем доступ к страницам
        if (userRole === '1') { // Администратор
            if (currentPage !== 'admin.html') {
                console.log("Admin accessing non-admin page, redirecting to admin panel");
                window.location.href = 'admin.html';
                return;
            }
        } else { // Не администратор
            if (currentPage === 'admin.html') {
                console.log("Non-admin accessing admin page, redirecting to dashboard");
                window.location.href = 'index.html';
                return;
            }
        }
    }
    
    // Обработка формы логина
    if (currentPage === 'login.html') {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            console.log("Login form found");
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log("Login form submitted");
                
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                
                console.log("Attempting login with email:", email);
                
                fetch('../Backend/api.php/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                })
                .then(response => {
                    console.log("Login response status:", response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("Login response data:", data);
                    
                    if (data.status === 'success' && data.token) {
                        // Сохраняем токен в localStorage
                        localStorage.setItem('token', data.token);
                        
                        // Сохраняем информацию о пользователе
                        if (data.user) {
                            localStorage.setItem('user', JSON.stringify(data.user));
                            localStorage.setItem('userRole', data.user.role_id.toString());
                            console.log("Stored role_id:", data.user.role_id);
                        }
                        
                        // Выводим сообщение об успешном входе
                        alert(data.message || 'Login successful!');
                        
                        // Перенаправляем пользователя в зависимости от роли
                        if (data.user && parseInt(data.user.role_id) === 1) {
                            console.log("Admin login detected, redirecting to admin panel");
                            window.location.href = 'admin.html';
                        } else {
                            console.log("User login detected, redirecting to profile");
                            window.location.href = 'profile.html';
                        }
                    } else {
                        console.error("Login failed:", data.message || "Unknown error");
                        alert(data.message || 'Login failed. Please check your credentials.');
                    }
                })
                .catch(error => {
                    console.error('Login error:', error);
                    alert('An error occurred during login. Please try again.');
                });
            });
        } else {
            console.error("Login form not found");
        }
    }
    
    // Обработка формы регистрации
    if (currentPage === 'register.html') {
        const registerForm = document.getElementById('registrationForm');
        if (registerForm) {
            console.log("Register form found");
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log("Register form submitted");
                
                const fullName = document.getElementById('fullName').value;
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const phone = document.getElementById('phone').value;
                const studentId = document.getElementById('studentId').value;
                const graduationYear = document.getElementById('graduationYear').value;
                const facultyId = document.getElementById('faculty').value;
                const bio = document.getElementById('bio').value;
                
                console.log("Registering user:", fullName, email);
                
                // Используем правильный путь для API
                fetch('../Backend/api.php/users', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        full_name: fullName,
                        email: email,
                        password: password,
                        phone: phone,
                        student_id: studentId,
                        faculty_id: facultyId,
                        graduation_year: graduationYear,
                        bio: bio,
                        role_id: 3 // Роль обычного пользователя
                    })
                })
                .then(response => {
                    console.log("Register response status:", response.status);
                    return response.json();
                })
                .then(data => {
                    console.log("Register response data:", data);
                    if (data.status === 'success') {
                        alert('Registration successful! Please login.');
                        window.location.href = 'login.html';
                    } else {
                        alert('Registration failed: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Register error:', error);
                    alert('An error occurred during registration. Please check the console for details.');
                });
            });
        } else {
            console.error("Register form not found");
        }
    }
    
    // Загрузка данных и создание графиков для дашборда
    if (currentPage === 'index.html') {
        console.log("Dashboard page loaded");
        // Проверяем, авторизован ли пользователь
        const token = localStorage.getItem('token');
        if (!token) {
            console.log("No token found, redirecting to login");
            // Если нет токена, перенаправляем на страницу логина
            window.location.href = 'login.html';
            return;
        }
        
        console.log("Token found, loading dashboard data");
        
        // Загружаем данные для графиков
        fetch('../Backend/api.php/vaccination_records', {
            headers: {
                'Authorization': 'Bearer ' + token
            }
        })
        .then(response => {
            console.log("Dashboard data response status:", response.status);
            if (response.status === 403) {
                // Если токен недействителен, перенаправляем на логин
                console.log("Unauthorized, redirecting to login");
                localStorage.removeItem('token');
                window.location.href = 'login.html';
                throw new Error('Unauthorized');
            }
            return response.json();
        })
        .then(data => {
            console.log("Dashboard data received:", data);
            
            // Для отладки - если данных нет или они некорректны, используем тестовые данные
            if (!Array.isArray(data) || data.length === 0) {
                console.log("No data or invalid data format, using sample data");
                data = [
                    { user_id: 1, full_name: "User 1", dose_number: 1, vaccine_name: "Vaccine A" },
                    { user_id: 2, full_name: "User 2", dose_number: 2, vaccine_name: "Vaccine B" },
                    { user_id: 3, full_name: "User 3", dose_number: 1, vaccine_name: "Vaccine A" },
                    { user_id: 4, full_name: "User 4", dose_number: 3, vaccine_name: "Vaccine C" }
                ];
            }
            
            // Подготавливаем данные для графиков
            const labels = data.map(item => item.full_name || 'User ' + item.user_id || 'Unknown');
            const doses = data.map(item => item.dose_number || 1);
            
            // Данные для круговой диаграммы (типы вакцин)
            const vaccineTypes = {};
            data.forEach(item => {
                const vaccineName = item.vaccine_name || 'Unknown';
                vaccineTypes[vaccineName] = (vaccineTypes[vaccineName] || 0) + 1;
            });
            const pieLabels = Object.keys(vaccineTypes);
            const pieData = Object.values(vaccineTypes);
            
            console.log("Preparing charts with labels:", labels);
            
            try {
                // Создаем столбчатую диаграмму (распределение вакцинации)
                const barChart = document.getElementById('barChart');
                if (barChart) {
                    const barCtx = barChart.getContext('2d');
                    new Chart(barCtx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Number of Doses',
                                data: doses,
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                    console.log("Bar chart created");
                } else {
                    console.error("Bar chart canvas not found");
                }
                
                // Создаем круговую диаграмму (типы вакцин)
                const pieChart = document.getElementById('pieChart');
                if (pieChart) {
                    const pieCtx = pieChart.getContext('2d');
                    new Chart(pieCtx, {
                        type: 'pie',
                        data: {
                            labels: pieLabels,
                            datasets: [{
                                data: pieData,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.7)',
                                    'rgba(54, 162, 235, 0.7)',
                                    'rgba(255, 206, 86, 0.7)',
                                    'rgba(75, 192, 192, 0.7)',
                                    'rgba(153, 102, 255, 0.7)'
                                ]
                            }]
                        },
                        options: {
                            responsive: true
                        }
                    });
                    console.log("Pie chart created");
                } else {
                    console.error("Pie chart canvas not found");
                }
                
                // Создаем линейный график (тренд вакцинации)
                const lineChart = document.getElementById('lineChart');
                if (lineChart) {
                    const lineCtx = lineChart.getContext('2d');
                    new Chart(lineCtx, {
                        type: 'line',
                        data: {
                            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
                            datasets: [{
                                label: 'Vaccinations Over Time',
                                data: [12, 19, 3, 5, 2, 3],
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 2,
                                tension: 0.1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                    console.log("Line chart created");
                } else {
                    console.error("Line chart canvas not found");
                }
            } catch (chartError) {
                console.error("Error creating charts:", chartError);
            }
        })
        .catch(error => {
            if (error.message !== 'Unauthorized') {
                console.error('Error fetching dashboard data:', error);
                alert('Could not load vaccination data. Please try again later.');
            }
        });
        
        // Добавляем кнопку выхода из системы, если она есть на странице
        const logoutButton = document.getElementById('logoutButton');
        if (logoutButton) {
            console.log("Logout button found, adding event listener");
            logoutButton.addEventListener('click', function() {
                console.log("Logout button clicked");
                localStorage.removeItem('token');
                localStorage.removeItem('userRole');
                window.location.href = 'login.html';
            });
        } else {
            console.error("Logout button not found");
        }
    }

    // Загрузка профиля пользователя
    if (currentPage === 'profile.html') {
        console.log("Profile page loaded");
        
        // Проверяем, есть ли токен в localStorage
        const token = localStorage.getItem('token');
        if (!token) {
            console.log("No token found, redirecting to login");
            window.location.href = 'login.html';
            return;
        }
        
        // Пытаемся получить сохраненные данные пользователя из localStorage
        let user = null;
        try {
            user = JSON.parse(localStorage.getItem('user'));
            console.log("User data from localStorage:", user);
            
            // Если есть данные пользователя, заполняем информацию в профиле
            if (user) {
                // Заполняем основную информацию
                document.getElementById('userNameDisplay').textContent = user.first_name 
                    ? `${user.first_name} ${user.last_name || ''}` 
                    : user.email;
                
                document.getElementById('userFullName').textContent = 
                    `${user.first_name || ''} ${user.last_name || ''} ${user.middle_name || ''}`.trim();
                
                document.getElementById('userEmail').textContent = user.email || 'Не указан';
                document.getElementById('userPhone').textContent = user.phone || 'Не указан';
                
                // Факультет и год выпуска - базовая информация
                if (user.faculty_id) {
                    document.getElementById('userFacultyYear').textContent = 
                        `Факультет ID: ${user.faculty_id}, Год выпуска: ${user.graduation_year || 'N/A'}`;
                } else {
                    document.getElementById('userFacultyYear').textContent = 'Факультет не указан';
                }

                // Если есть аватар пользователя, используем его
                if (user && user.avatar_url) {
                    // Обеспечиваем правильный путь относительно текущей страницы
                    const avatarUrl = '../' + user.avatar_url;
                    
                    // Обновляем все элементы с аватаром пользователя
                    const userAvatarElements = document.querySelectorAll('[id^="userAvatar"]');
                    userAvatarElements.forEach(element => {
                        element.src = avatarUrl;
                    });
                }
            }
        } catch (error) {
            console.error("Error parsing user data from localStorage:", error);
        }
        
        // Загружаем актуальные данные пользователя с сервера
        console.log("Fetching user profile from server");
        fetch('../Backend/api.php/profile', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        })
        .then(response => {
            console.log("Profile response status:", response.status);
            if (!response.ok) {
                if (response.status === 401 || response.status === 403) {
                    // Если токен недействителен, перенаправляем на логин
                    console.log("Invalid token, redirecting to login");
                    localStorage.removeItem('token');
                    localStorage.removeItem('user');
                    localStorage.removeItem('userRole');
                    window.location.href = 'login.html';
                    throw new Error('Unauthorized');
                }
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log("Profile data received:", data);
            
            if (data.status === 'success' && data.user) {
                // Обновляем данные пользователя в localStorage
                localStorage.setItem('user', JSON.stringify(data.user));
                
                // Обновляем информацию на странице
                document.getElementById('userNameDisplay').textContent = data.user.first_name 
                    ? `${data.user.first_name} ${data.user.last_name || ''}` 
                    : data.user.email;
                
                document.getElementById('userFullName').textContent = 
                    `${data.user.first_name || ''} ${data.user.last_name || ''} ${data.user.middle_name || ''}`.trim();
                
                document.getElementById('userEmail').textContent = data.user.email || 'Не указан';
                document.getElementById('userPhone').textContent = data.user.phone || 'Не указан';
                
                // Если есть bio, отображаем его
                if (data.user.bio) {
                    document.getElementById('userBio').textContent = data.user.bio;
                }
                
                // Если есть название факультета
                if (data.user.faculty_name) {
                    document.getElementById('userFacultyYear').textContent = 
                        `${data.user.faculty_name}, Год выпуска: ${data.user.graduation_year || 'N/A'}`;
                } else if (data.user.faculty_id) {
                    // Если есть id факультета, но нет названия - загрузим отдельно
                    fetch(`../Backend/api.php/faculties/${data.user.faculty_id}`)
                        .then(response => response.json())
                        .then(facultyData => {
                            if (facultyData.status === 'success' && facultyData.data) {
                                const facultyName = facultyData.data.name || facultyData.data.faculty_name;
                                document.getElementById('userFacultyYear').textContent = 
                                    `${facultyName}, Год выпуска: ${data.user.graduation_year || 'N/A'}`;
                            } else {
                                document.getElementById('userFacultyYear').textContent = 
                                    `Факультет ID: ${data.user.faculty_id}, Год выпуска: ${data.user.graduation_year || 'N/A'}`;
                            }
                        })
                        .catch(error => {
                            console.error("Error loading faculty:", error);
                            document.getElementById('userFacultyYear').textContent = 
                                `Факультет ID: ${data.user.faculty_id}, Год выпуска: ${data.user.graduation_year || 'N/A'}`;
                        });
                }
            } else {
                console.error("Failed to load profile data:", data.message || "Unknown error");
            }
        })
        .catch(error => {
            if (error.message !== 'Unauthorized') {
                console.error("Error loading profile:", error);
                alert("Ошибка загрузки профиля. Пожалуйста, попробуйте позже.");
            }
        });
        
        // Обработчик для кнопки "Редактировать профиль"
        const editProfileBtn = document.getElementById('editProfileBtn');
        if (editProfileBtn) {
            editProfileBtn.addEventListener('click', () => {
                alert('Функция редактирования профиля будет доступна в ближайшее время!');
                // Здесь в будущем можно добавить переход на страницу редактирования
                // window.location.href = 'edit-profile.html';
            });
        }
    }

    // Логика для страницы администратора
    if (currentPage === 'admin.html') {
        console.log("Admin page loaded");
        
        // Проверяем, есть ли токен и роль администратора
        const token = localStorage.getItem('token');
        const userRole = localStorage.getItem('userRole');
        
        if (!token) {
            console.log("No token found, redirecting to login");
            window.location.href = 'login.html';
            return;
        }
        
        if (userRole !== '1') {
            console.log("Not an admin, redirecting to profile");
            window.location.href = 'profile.html';
            return;
        }
        
        // Загружаем список пользователей для администратора
        fetch('../Backend/api.php/users', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        })
        .then(response => {
            console.log("Admin users response status:", response.status);
            if (!response.ok) {
                if (response.status === 401 || response.status === 403) {
                    console.log("Invalid token or not admin, redirecting to login");
                    localStorage.removeItem('token');
                    localStorage.removeItem('user');
                    localStorage.removeItem('userRole');
                    window.location.href = 'login.html';
                    throw new Error('Unauthorized');
                }
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log("Admin users data received:", data);
            
            if (data.status === 'success' && data.data) {
                // Заполняем таблицу пользователей, если она есть на странице
                const usersTableBody = document.getElementById('userTableBody');
                if (usersTableBody && Array.isArray(data.data)) {
                    // Очищаем таблицу
                    usersTableBody.innerHTML = '';
                    
                    // Заполняем таблицу данными
                    data.data.forEach(user => {
                        const row = document.createElement('tr');
                        
                        row.innerHTML = `
                            <td>${user.user_id}</td>
                            <td>${user.full_name}</td>
                            <td>${user.email}</td>
                            <td>${user.student_id || 'Н/Д'}</td>
                            <td>${user.status || 'pending'}</td>
                            <td>
                                <button class="btn btn-sm btn-primary approve-user" data-id="${user.user_id}">
                                    Подтвердить
                                </button>
                                <button class="btn btn-sm btn-danger reject-user" data-id="${user.user_id}">
                                    Отклонить
                                </button>
                            </td>
                        `;
                        
                        usersTableBody.appendChild(row);
                    });
                    
                    // Добавляем обработчики для кнопок
                    const approveButtons = document.querySelectorAll('.approve-user');
                    const rejectButtons = document.querySelectorAll('.reject-user');
                    
                    approveButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            const userId = this.getAttribute('data-id');
                            alert(`Подтверждение пользователя с ID ${userId} будет добавлено в будущем.`);
                        });
                    });
                    
                    rejectButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            const userId = this.getAttribute('data-id');
                            alert(`Отклонение пользователя с ID ${userId} будет добавлено в будущем.`);
                        });
                    });
                } else {
                    console.error("User table body not found or data is not an array");
                }
            } else {
                console.error("Failed to load users data:", data.message || "Unknown error");
            }
        })
        .catch(error => {
            if (error.message !== 'Unauthorized') {
                console.error("Error loading admin users:", error);
                alert("Ошибка загрузки списка пользователей. Пожалуйста, попробуйте позже.");
            }
        });
        
        // Обработчик для кнопки выхода
        const adminLogoutBtn = document.getElementById('adminLogout');
        if (adminLogoutBtn) {
            adminLogoutBtn.addEventListener('click', () => {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                localStorage.removeItem('userRole');
                alert('Вы вышли из системы.');
                window.location.href = 'login.html';
            });
        }
    }

    // Function to setup sidebar navigation
    function setupSidebar() {
        // Update active menu item
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        document.querySelectorAll('.sidebar .nav-link').forEach(item => {
            item.classList.remove('active');
        });
        
        // Set the active menu item based on the current page
        if (currentPage === 'profile.html') {
            document.getElementById('navProfile')?.classList.add('active');
        } else if (currentPage === 'calendar.html') {
            document.getElementById('navCalendar')?.classList.add('active');
        } else if (currentPage === 'dashboard.html' || currentPage === 'index.html' || currentPage === '') {
            document.getElementById('navDashboard')?.classList.add('active');
        } else {
            // Try to find the corresponding nav item
            const navId = 'nav' + currentPage.split('.')[0].split('_').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1)).join('');
            if (document.getElementById(navId)) {
                document.getElementById(navId).classList.add('active');
            }
        }
        
        // Setup menu navigation
        document.querySelectorAll('.sidebar .nav-link').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.id;
                if (id === 'navLogout') {
                    localStorage.removeItem('user');
                    localStorage.removeItem('token');
                    localStorage.removeItem('userRole');
                    window.location.href = 'login.html';
                    return;
                }
                
                // For help desk and contact links
                if (id === 'helpDesk' || this.hasAttribute('href') && 
                    (this.getAttribute('href').startsWith('tel:') || 
                     this.getAttribute('href').startsWith('mailto:'))) {
                    return; // Let the default behavior handle these links
                }
                
                // Extract the page name from the id (remove 'nav' prefix and convert to lowercase)
                let pageName = id.substring(3); // Remove 'nav' prefix
                
                // Handle special cases
                if (pageName === 'Dashboard') {
                    pageName = 'dashboard';
                } else if (pageName === 'Profile') {
                    pageName = 'profile';
                } else if (pageName === 'Calendar') {
                    pageName = 'calendar';
                } else if (pageName === 'SocialLearning') {
                    pageName = 'social_learning';
                } else if (pageName === 'LiveLearning') {
                    pageName = 'live_learning';
                } else if (pageName === 'AbsencesHistory') {
                    pageName = 'absences_history';
                } else if (pageName === 'ReEnrollment') {
                    pageName = 're_enrollment';
                } else {
                    // Convert camelCase to snake_case
                    pageName = pageName.replace(/([A-Z])/g, '_$1').toLowerCase();
                    if (pageName.startsWith('_')) {
                        pageName = pageName.substring(1);
                    }
                }
                
                window.location.href = pageName + '.html';
            });
        });
        
        // Setup user avatar if available
        const user = JSON.parse(localStorage.getItem('user'));
        if (user && user.avatar_url) {
            // Ensure correct path to avatar
            const avatarUrl = '../' + user.avatar_url;
            
            // Update all user avatar elements
            document.querySelectorAll('[id^="userAvatar"]').forEach(element => {
                element.src = avatarUrl;
            });
        }
    }

    // Call setupSidebar on DOMContentLoaded
    if (document.querySelector('.sidebar')) {
        setupSidebar();
    }
});
let currentRole = 'admin';

        function switchLogin(role) {
            currentRole = role;
            const adminBtn = document.querySelector('.admin-toggle');
            const nurseBtn = document.querySelector('.nurse-toggle');

            if (role === 'admin') {
                adminBtn.classList.add('active');
                nurseBtn.classList.remove('active');
                document.getElementById('loginForm').dataset.role = 'admin';
            } else {
                nurseBtn.classList.add('active');
                adminBtn.classList.remove('active');
                document.getElementById('loginForm').dataset.role = 'nurse';
            }
        }

        const loginForm = document.getElementById('loginForm');
        const loginMessage = document.getElementById('loginMessage');

        loginForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            console.log('zbi');

            const email = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const role = document.getElementById('loginForm').dataset.role;

            


            try {

                const response = await fetch('api/routes/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password, role })
                });



                const result= await response.json();



                if (response.ok) {
                    localStorage.setItem('loggedInUser', JSON.stringify({
                        id: result.id,
                        name: result.name,
                        role: result.role
                    }));
                    loginMessage.textContent = result.message;
                    loginMessage.className = 'message success animate__animated animate__fadeIn';
                    loginMessage.style.display = 'block';

                    setTimeout(() => {
                        if (result.role === 'admin') {
                            window.location.href = 'Agent.html';
                        } else if (result.role === 'nurse') {
                            window.location.href = 'nurse.html';
                        }
                    }, 1000);
                } else {
                    loginMessage.textContent = result.message;
                    loginMessage.className = 'message error animate__animated animate__fadeIn';
                    loginMessage.style.display = 'block';
                    setTimeout(() => {
                        loginMessage.style.display = 'none';
                    }, 3000);
                }
            } catch (error) {
                loginMessage.textContent = 'حدث خطأ أثناء تسجيل الدخول';
                loginMessage.className = 'message error animate__animated animate__fadeIn';
                loginMessage.style.display = 'block';
                setTimeout(() => {
                    loginMessage.style.display = 'none';
                }, 3000);
            }
        });
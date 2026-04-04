define([], function() {
    return {
        init: () => {
            const passwordEye = document.querySelector('.password-eye');
            const passwordInput = document.querySelector('#block-input-password input[type="password"]');

            passwordEye.addEventListener('click', () => {
                passwordInput.parentElement.classList.toggle('password-shown');
                passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
            });
        }
    };
});

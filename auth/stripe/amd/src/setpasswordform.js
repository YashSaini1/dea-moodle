define([], function() {
  return {
    init: () => {
      const passwordEye = document.querySelectorAll('.password-eye');

      passwordEye.forEach((item) => {
        item.addEventListener('click', () => {
          item.classList.toggle('password-shown');
          let passwordInput = item.previousElementSibling.querySelector('input');
          passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
        });
      });
    }
  };
});

document.addEventListener("DOMContentLoaded", function () {
    
    // --- 1. SIGNUP PAGE FUNCTIONALITY ---
    const signupPassword = document.getElementById("password");
    const confirmPassword = document.getElementById("confirmPassword");
    const signupCheckbox = document.getElementById("showPassword");

    // Password guidelines color validation
    if (signupPassword) {
        signupPassword.addEventListener("keyup", function () {
            const value = signupPassword.value;

            document.getElementById("length").classList.toggle("valid", value.length >= 8);
            document.getElementById("max").classList.toggle("valid", value.length <= 16);
            document.getElementById("upper").classList.toggle("valid", /[A-Z]/.test(value));
            document.getElementById("lower").classList.toggle("valid", /[a-z]/.test(value));
            document.getElementById("number").classList.toggle("valid", /[0-9]/.test(value));
            document.getElementById("special").classList.toggle("valid", /[\W]/.test(value));
        });
    }

    // Toggle Show/Hide on Signup Page
    if (signupCheckbox) {
        signupCheckbox.addEventListener("change", function () {
            const type = this.checked ? "text" : "password";
            if (signupPassword) signupPassword.type = type;
            if (confirmPassword) confirmPassword.type = type;
        });
    }

    // --- 2. LOGIN PAGE FUNCTIONALITY ---
    const loginPassword = document.getElementById("loginPassword");
    const loginCheckbox = document.getElementById("showLoginPassword");

    // Toggle Show/Hide on Login Page
    if (loginCheckbox && loginPassword) {
        loginCheckbox.addEventListener("change", function () {
            loginPassword.type = this.checked ? "text" : "password";
        });
    }
});
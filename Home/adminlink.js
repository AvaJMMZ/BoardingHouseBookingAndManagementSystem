document.addEventListener("DOMContentLoaded", function() {
    var passwordInput = document.getElementById("password");
    var passwordToggle = document.getElementById("password-toggle");
    var passwordModal = document.getElementById("password-modal");
    var adminLink = document.getElementById("admin-link");
    var adminLinkClicked = false;

    passwordToggle.addEventListener("click", togglePassword);
    adminLink.addEventListener("click", function(event) {
        event.preventDefault();
        adminLinkClicked = true;
        adminLink.style.display = "none";
        passwordModal.style.display = "block";
    });

    document.querySelector(".close").addEventListener("click", hidePasswordModal);
    document.getElementById("password-submit").addEventListener("click", validatePassword);

    function togglePassword() {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            passwordToggle.innerHTML = '<i class="fa fa-eye"></i>';
        } else {
            passwordInput.type = "password";
            passwordToggle.innerHTML = '<i class="fa fa-eye-slash"></i>';
        }
    }

    function validatePassword() {
        var password = passwordInput.value;

        if (password === "admin123") {
            if (adminLinkClicked) {
                adminLinkClicked = false;
                window.location.href = "Admin/pg_admin.php";
            }
        } else {
            alert("Invalid password. Access denied.");
        }
    }

    function hidePasswordModal() {
        passwordModal.style.display = "none";
        adminLink.style.display = "block";
    }
});

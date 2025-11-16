document.addEventListener("DOMContentLoaded", () => {
  const msg = document.getElementById("msg");

  //signup
  const signupBtn = document.getElementById("signupBtn");
  if (signupBtn) {
    signupBtn.addEventListener("click", async () => {
      const username = document.getElementById("signup_username").value.trim();
      const email = document.getElementById("signup_email").value.trim();
      const password = document.getElementById("signup_password").value;
      const password2 = document.getElementById("signup_password2").value;

      if (!username || !email || !password) {
        msg.textContent = "Please fill all fields";
        return;
      }
      if (password !== password2) {
        msg.textContent = "Passwords do not match";
        return;
      }

      try {
        const res = await fetch(
          "http://localhost/Health_AI/Backend/Controllers/SignUpController.php",
          {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username, email, password }),
          }
        );

        if (!res.ok) {
          console.error("server responded error :", res.status);
          msg.textContent = `Server error: ${res.status}`;
          return;
        }
        const data = await res.json();
        console.log(data);

        if (data.status !== 200) {
          msg.textContent = data.data || "Signup failed";
          return;
        }

        localStorage.setItem("user", JSON.stringify(data.data));
        location.href = "/Health_AI/Frontend/Pages/dashboard.html";
      } catch (e) {
        msg.textContent = "error signing up user";
      }
    });
  }

  //login
  const loginBtn = document.getElementById("loginBtn");
  if (loginBtn) {
    loginBtn.addEventListener("click", async () => {
      const email = document.getElementById("login_email").value.trim();
      const password = document.getElementById("login_password").value;

      if (!email || !password) {
        msg.textContent = "Please enter email and password";
        return;
      }

      try {
        const res = await fetch(
          "http://localhost/Health_AI/Backend/Controllers/LoginController.php",
          {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password }),
          }
        );

        if (!res.ok) {
          console.error("server responded error :", res.status);
          msg.textContent = `Server error: ${res.status}`;
          return;
        }

        const data = await res.json();

        if (data.status !== 200) {
          msg.textContent = data.data || "Login failed";
          return;
        }

        localStorage.setItem("user", JSON.stringify(data.data));
        const userType = JSON.parse(localStorage.getItem("user")).role;
        console.log(userType);
        if (userType === "user") {
          location.href = "/Health_AI/Frontend/Pages/dashboard.html";
        } else {
          location.href = "/Health_AI/Frontend/Pages/admin.html";
        }
      } catch (e) {
        msg.textContent = "error logging in";
      }
    });
  }
});

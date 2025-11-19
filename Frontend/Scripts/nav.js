function getUser() {
    const user = localStorage.getItem("user");
    return user ? JSON.parse(user) : null;
}

function renderAdminLink() {
    const user = getUser();
    const navbar = document.getElementById("navbarLinks");

    if (!user || !navbar) return;

    if (document.getElementById("adminLink")) return;

    if (user.role === "admin") {
        const link = document.createElement("a");
        link.href = "admin.html";
        link.id = "adminLink";
        link.innerText = "Admin";
        navbar.appendChild(link);
    }
}

function setActiveLink() {
    const path = window.location.pathname;
    const page = path.split("/").pop();
    const links = document.querySelectorAll("#navbarLinks a");

    links.forEach(link => {
        if (link.getAttribute("href") === page) {
            link.classList.add("active");
        } else {
            link.classList.remove("active");
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    renderAdminLink();
    setActiveLink();
});

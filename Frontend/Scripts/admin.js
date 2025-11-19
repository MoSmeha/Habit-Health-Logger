const API_URL =
  "http://localhost/Health_AI/Backend/Controllers/UserController.php";
let isEditMode = false;

function getUser() {
  const user = localStorage.getItem("user");
  return user ? JSON.parse(user) : null;
}

function checkAdmin() {
  const user = getUser();
  if (!user || user.role !== "admin") {
    showError("Access denied. Admin privileges required.");
    return false;
  }
  return true;
}

document.addEventListener("DOMContentLoaded", () => {
  if (checkAdmin()) {
    loadUsers();
  }
});

function showError(message) {
  const errorDiv = document.getElementById("error");
  errorDiv.textContent = message;
  errorDiv.style.display = "block";
}

async function loadUsers() {
  if (!checkAdmin()) return;

  const user = getUser();
  const loading = document.getElementById("loading");
  const tableContainer = document.getElementById("tableContainer");

  loading.style.display = "block";
  tableContainer.innerHTML = "";

  try {
    const response = await fetch(`${API_URL}?user_id=${user.id}`);
    const data = await response.json();

    loading.style.display = "none";

    if (data.status === 200 && Array.isArray(data.data)) {
      renderTable(data.data);
    } else {
      showError(data.message || "Failed to load users");
    }
  } catch (error) {
    loading.style.display = "none";
    showError("Error loading users: " + error.message);
  }
}

function renderTable(users) {
  const tableContainer = document.getElementById("tableContainer");

  if (users.length === 0) {
    tableContainer.innerHTML = "<p>No users found</p>";
    return;
  }

  let html = `
				<table>
					<thead>
						<tr>
							<th>ID</th>
							<th>Username</th>
							<th>Email</th>
							<th>Role</th>
							<th>Created At</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
			`;

  users.forEach((u) => {
    const roleClass = u.role === "admin" ? "badge-admin" : "badge-user";
    html += `
					<tr>
						<td>${u.id}</td>
						<td>${u.username}</td>
						<td>${u.email}</td>
						<td><span class="badge ${roleClass}">${u.role}</span></td>
						<td>${new Date(u.created_at).toLocaleString()}</td>
						<td>
							<button class="btn btn-success" onclick='openEditModal(${JSON.stringify(
                u
              )})'>Edit</button>
							<button class="btn btn-danger" onclick="deleteUser(${u.id}, '${
      u.username
    }')">Delete</button>
						</td>
					</tr>
				`;
  });

  html += `</tbody></table>`;
  tableContainer.innerHTML = html;
}

function openCreateModal() {
  if (!checkAdmin()) return;

  isEditMode = false;
  document.getElementById("modalTitle").textContent = "Add New User";
  document.getElementById("userForm").reset();
  document.getElementById("userId").value = "";
  document.getElementById("password").required = true;
  document.getElementById("passwordOptional").textContent = "";
  document.getElementById("userModal").style.display = "block";
}

function openEditModal(userData) {
  if (!checkAdmin()) return;

  isEditMode = true;
  document.getElementById("modalTitle").textContent = "Edit User";
  document.getElementById("userId").value = userData.id;
  document.getElementById("username").value = userData.username;
  document.getElementById("email").value = userData.email;
  document.getElementById("role").value = userData.role;
  document.getElementById("password").value = "";
  document.getElementById("password").required = false;
  document.getElementById("passwordOptional").textContent =
    "(leave blank to keep current)";
  document.getElementById("userModal").style.display = "block";
}

function closeModal() {
  document.getElementById("userModal").style.display = "none";
  document.getElementById("userForm").reset();
}

document.getElementById("userForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  if (!checkAdmin()) return;

  const user = getUser();
  const userId = document.getElementById("userId").value;
  const username = document.getElementById("username").value;
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;
  const role = document.getElementById("role").value;

  const payload = { username, email, role };
  if (password) {
    payload.password = password;
  }

  try {
    let response;
    if (isEditMode && userId) {
      response = await fetch(`${API_URL}?user_id=${user.id}&id=${userId}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    } else {
      response = await fetch(`${API_URL}?user_id=${user.id}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    }

    const data = await response.json();

    if (data.status === 200 || data.status === 201) {
      closeModal();
      loadUsers();
    } else {
      showError(data.message);
    }
  } catch (error) {
    showError("Error: " + error.message);
  }
});

async function deleteUser(id, username) {
  if (!checkAdmin()) return;

  if (!confirm(`Are you sure you want to delete user "${username}"?`)) {
    return;
  }

  const user = getUser();

  try {
    const response = await fetch(`${API_URL}?user_id=${user.id}&id=${id}`, {
      method: "DELETE",
    });

    const data = await response.json();

    if (data.status === 200) {
      loadUsers();
    } else {
      showError(data.message);
    }
  } catch (error) {
    showError("Error: " + error.message);
  }
}

window.onclick = function (event) {
  const modal = document.getElementById("userModal");
  if (event.target === modal) {
    closeModal();
  }
};

const API_BASE =
  "http://localhost/Health_AI/Backend/Controllers/HabitController.php";

function getUser() {
  const user = localStorage.getItem("user");
  return user ? JSON.parse(user) : null;
}

function renderUser() {
  const user = getUser();
  document.getElementById("userInfo").innerHTML = user
    ? `Logged in as: ${user.username} (ID: ${user.id})`
    : `No user in localStorage! <a href="Login.html">You must login first</a>`;
}

async function loadHabits() {
  const user = getUser();
  if (!user) return;

  const response = await fetch(`${API_BASE}?user_id=${user.id}`);
  const json = await response.json();
  renderHabits(json.data);
}

async function createHabit(name) {
  const user = getUser();
  await fetch(`${API_BASE}?user_id=${user.id}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ name }),
  });

  loadHabits();
}

async function toggleHabit(id, active) {
  const user = getUser();
  await fetch(`${API_BASE}?user_id=${user.id}&id=${id}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ active: active ? 0 : 1 }),
  });

  loadHabits();
}

async function deleteHabit(id) {
  const user = getUser();
  await fetch(`${API_BASE}?user_id=${user.id}&id=${id}`, {
    method: "DELETE",
  });

  loadHabits();
}

function renderHabits(habits) {
  const container = document.getElementById("habitList");
  container.innerHTML = "";

  const active = habits.filter((h) => h.active == 1);
  const inactive = habits.filter((h) => h.active == 0);

  // --- Activated Header ---
  const activeHeader = document.createElement("h2");
  activeHeader.innerText = "Activated";
  container.appendChild(activeHeader);

  active.forEach((h) => {
    const div = document.createElement("div");
    div.className = "habit";

    div.innerHTML = `
      <div>
        <div class="habit-name">${h.name}</div>
        <small>Created: ${h.created_at}</small>
      </div>
      <div class="habit-actions">
        <button onclick="toggleHabit(${h.id}, ${h.active})">
          Disable
        </button>
        <button class="danger" onclick="deleteHabit(${h.id})">Delete</button>
      </div>
    `;

    container.appendChild(div);
  });

  // --- Deactivated Header ---
  const inactiveHeader = document.createElement("h2");
  inactiveHeader.innerText = "Deactivated";
  inactiveHeader.style.marginTop = "25px";
  container.appendChild(inactiveHeader);

  inactive.forEach((h) => {
    const div = document.createElement("div");
    div.className = "habit";

    div.innerHTML = `
      <div>
        <div class="habit-name">${h.name}</div>
        <small>Created: ${h.created_at}</small>
      </div>
      <div class="habit-actions">
        <button onclick="toggleHabit(${h.id}, ${h.active})">
          Enable
        </button>
        <button class="danger" onclick="deleteHabit(${h.id})">Delete</button>
      </div>
    `;

    container.appendChild(div);
  });
}

document.getElementById("createForm").addEventListener("submit", (e) => {
  e.preventDefault();
  const name = document.getElementById("nameInput").value.trim();
  if (!name) return;

  createHabit(name);
  document.getElementById("nameInput").value = "";
});

renderUser();
loadHabits();

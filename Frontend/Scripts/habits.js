// CONFIGURATION
const API_BASE = "http://localhost/Health_AI/Backend/Controllers";

function getUser() {
  const user = localStorage.getItem("user");
  return user ? JSON.parse(user) : null;
}

function renderUser() {
  const user = getUser();
  const infoDiv = document.getElementById("userInfo");

  if (user) {
    infoDiv.innerHTML = `Logged in as: <strong>${user.username}</strong>`;
    renderAdminLink(user);
  } else {
    infoDiv.innerHTML = `Not logged in. <a href="Login.html" style="color: #4caf50; text-decoration: underline;">Login here</a>`;
    document.getElementById("analyzeBtn").disabled = true;
    document.querySelector("form button").disabled = true;
  }
}

function renderAdminLink(user) {
  const navbar = document.getElementById("navbarLinks");
  if (
    user.role === "admin" &&
    navbar &&
    !document.getElementById("adminLink")
  ) {
    const link = document.createElement("a");
    link.href = "admin.html";
    link.id = "adminLink";
    link.innerText = "Admin";
    navbar.appendChild(link);
  }
}

async function loadHabits() {
  const user = getUser();
  if (!user) return;

  try {
    const response = await fetch(
      `${API_BASE}/HabitController.php?user_id=${user.id}`
    );
    const json = await response.json();

    if (json.data) {
      renderHabitList(json.data);
    }
  } catch (error) {
    console.error("Failed to load habits:", error);
  }
}

async function createHabit(name) {
  const user = getUser();
  try {
    await fetch(`${API_BASE}/HabitController.php?user_id=${user.id}`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name }),
    });
    loadHabits();
  } catch (e) {
    console.error(e);
  }
}

async function toggleHabit(id, currentStatus) {
  const user = getUser();
  const newStatus = currentStatus == 1 ? 0 : 1;

  try {
    await fetch(`${API_BASE}/HabitController.php?user_id=${user.id}&id=${id}`, {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ active: newStatus }),
    });
    loadHabits();
  } catch (e) {
    console.error(e);
  }
}

async function deleteHabit(id) {
  if (!confirm("Delete this habit?")) return;

  const user = getUser();
  try {
    await fetch(`${API_BASE}/HabitController.php?user_id=${user.id}&id=${id}`, {
      method: "DELETE",
    });
    loadHabits();
  } catch (e) {
    console.error(e);
  }
}

function renderHabitList(habits) {
  const container = document.getElementById("habitList");
  container.innerHTML = "";

  const active = habits.filter((h) => h.active == 1);
  const inactive = habits.filter((h) => h.active == 0);

  const createRow = (h, isActionActive) => `
          <div class="habit">
            <div>
              <div class="habit-name">${h.name}</div>
              <small style="color:#888">Added: ${
                h.created_at.split(" ")[0]
              }</small>
            </div>
            <div class="habit-actions">
              <button onclick="toggleHabit(${h.id}, ${h.active})" 
                      style="background: ${
                        isActionActive ? "#777" : "#4caf50"
                      }">
                ${isActionActive ? "Pause" : "Activate"}
              </button>
              <button class="danger" onclick="deleteHabit(${
                h.id
              })">Delete</button>
            </div>
          </div>
        `;

  if (active.length > 0) {
    const h2 = document.createElement("h2");
    h2.innerText = "Active Habits";
    container.appendChild(h2);
    active.forEach((h) => (container.innerHTML += createRow(h, true)));
  }

  if (inactive.length > 0) {
    const h2 = document.createElement("h2");
    h2.innerText = "Paused Habits";
    container.appendChild(h2);
    inactive.forEach((h) => (container.innerHTML += createRow(h, false)));
  }

  if (habits.length === 0) {
    container.innerHTML =
      "<p style='text-align:center; color:#888'>No habits found.</p>";
  }
}

async function analyzeHabits() {
  const user = getUser();
  if (!user) return;

  const resultsDiv = document.getElementById("results");
  const analyzeBtn = document.getElementById("analyzeBtn");

  // Simple Loading State
  analyzeBtn.disabled = true;
  analyzeBtn.innerText = "Analyzing...";

  resultsDiv.innerHTML = `
          <div class="loading">
            <p>Analyzing...</p>
          </div>
        `;

  try {
    const response = await fetch(
      `${API_BASE}/HabitAnalysisController.php?user_id=${user.id}`
    );

    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      throw new Error("Server error: Response was not JSON.");
    }

    const jsonResponse = await response.json();

    if (response.ok && jsonResponse.data && jsonResponse.data.habit_analysis) {
      renderAnalysisResults(jsonResponse.data.habit_analysis);
    } else {
      const msg = jsonResponse.data?.error || "Unknown error occurred.";
      resultsDiv.innerHTML = `<div class="error"><p>${msg}</p></div>`;
    }
  } catch (error) {
    console.error(error);
    resultsDiv.innerHTML = `
            <div class="error">
              <p><strong>Connection Failed</strong></p>
              <p>${error.message}</p>
            </div>
          `;
  } finally {
    analyzeBtn.disabled = false;
    analyzeBtn.innerText = "Analyze My Week";
  }
}

function renderAnalysisResults(data) {
  const resultsDiv = document.getElementById("results");
  let html = "";

  if (data.summary) {
    html += `
            <div class="summary">
              <h3>Weekly Summary</h3>
              <p>${data.summary}</p>
            </div>
          `;
  }

  if (data.following_well && data.following_well.length > 0) {
    html += `<div class="habits-section"><h3>Consistent Habits</h3>`;
    data.following_well.forEach((item) => {
      html += `
              <div class="habit-item">
                <span class="habit-title">${item.habit}</span>
                <span>${item.reason}</span>
              </div>
            `;
    });
    html += `</div>`;
  }

  if (data.not_following && data.not_following.length > 0) {
    html += `<div class="habits-section needs-attention"><h3>Needs Attention</h3>`;
    data.not_following.forEach((item) => {
      html += `
              <div class="habit-item">
                <span class="habit-title">${item.habit}</span>
                <span>${item.reason}</span>
              </div>
            `;
    });
    html += `</div>`;
  }

  if (data.tips && data.tips.length > 0) {
    html += `
            <div class="tips">
              <h3>Actionable Tips</h3>
              <ul>
                ${data.tips.map((tip) => `<li>${tip}</li>`).join("")}
              </ul>
            </div>
          `;
  }

  resultsDiv.innerHTML = html;
  resultsDiv.scrollIntoView({ behavior: "smooth" });
}

window.toggleHabit = toggleHabit;
window.deleteHabit = deleteHabit;

document.getElementById("createForm").addEventListener("submit", (e) => {
  e.preventDefault();
  const input = document.getElementById("nameInput");
  const name = input.value.trim();
  if (name) {
    createHabit(name);
    input.value = "";
  }
});

document.getElementById("analyzeBtn").addEventListener("click", analyzeHabits);

renderUser();
loadHabits();

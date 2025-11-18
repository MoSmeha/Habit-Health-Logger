const API_URL =
  "https://localhost/Health_AI/Backend/Controllers/ParsedEntryController.php";
let entries = [];
let editingId = null;

function getUser() {
  const user = localStorage.getItem("user");
  return user ? JSON.parse(user) : null;
}

function showError(message) {
  const errorEl = document.getElementById("error");
  errorEl.textContent = message;
  errorEl.style.display = "block";
  setTimeout(() => (errorEl.style.display = "none"), 5000);
}

function showLoading(show) {
  document.getElementById("loading").style.display = show ? "block" : "none";
}

async function fetchEntries() {
  const user = getUser();
  if (!user || !user.id) {
    showError("User not logged in");
    return;
  }

  showLoading(true);
  try {
    const response = await fetch(`${API_URL}?user_id=${user.id}`);
    const data = await response.json();

    if (data.status === 200) {
      entries = data.data;
      renderEntries();
    } else {
      showError(data.data || "Failed to load entries");
    }
  } catch (error) {
    showError("Network error: " + error.message);
  } finally {
    showLoading(false);
  }
}

function renderEntries() {
  const container = document.getElementById("entries-container");

  if (entries.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
    
        <p>No entries found</p>
      </div>
    `;
    return;
  }

  const tableHTML = `
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Slept</th>
            <th>Coffee</th>
            <th>Walked</th>
            <th>Meal</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${entries.map((entry) => renderRow(entry)).join("")}
        </tbody>
      </table>
    </div>
  `;

  container.innerHTML = tableHTML;
}

function renderRow(entry) {
  const isEditing = editingId === entry.id;

  if (isEditing) {
    return `
      <tr>
        <td>${entry.id}</td>
        <td><input type="text" id="slept-${entry.id}" value="${
      entry.slept || ""
    }" /></td>
        <td><input type="number" id="coffee-${entry.id}" value="${
      entry.coffee || ""
    }" /></td>
        <td><input type="text" id="walked-${entry.id}" value="${
      entry.walked || ""
    }" /></td>
        <td><input type="text" id="meal-${entry.id}" value="${
      entry.meal || ""
    }" /></td>
        <td>
          <div class="actions">
            <button class="btn btn-save" onclick="saveEntry(${
              entry.id
            })">Save</button>
            <button class="btn btn-cancel" onclick="cancelEdit()">Cancel</button>
          </div>
        </td>
      </tr>
    `;
  }

  return `
    <tr>
      <td>${entry.id}</td>
      <td>${entry.slept || "N/A"}</td>
      <td>${entry.coffee || "N/A"}</td>
      <td>${entry.walked || "N/A"}</td>
      <td>${entry.meal || "N/A"}</td>
      <td>
        <div class="actions">
          <button class="btn btn-edit" onclick="editEntry(${
            entry.id
          })">Edit</button>
          <button class="btn btn-delete" onclick="deleteEntry(${
            entry.id
          })">Delete</button>
        </div>
      </td>
    </tr>
  `;
}

function editEntry(id) {
  editingId = id;
  renderEntries();
}

function cancelEdit() {
  editingId = null;
  renderEntries();
}

async function saveEntry(id) {
  const data = {
    slept: document.getElementById(`slept-${id}`).value,
    coffee: document.getElementById(`coffee-${id}`).value,
    walked: document.getElementById(`walked-${id}`).value,
    meal: document.getElementById(`meal-${id}`).value,
  };

  try {
    const response = await fetch(`${API_URL}?id=${id}`, {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
    const result = await response.json();

    if (result.status === 200) {
      editingId = null;
      await fetchEntries();
    } else {
      showError(result.data || "Failed to update entry");
    }
  } catch (error) {
    showError("Network error: " + error.message);
  }
}

async function deleteEntry(id) {
  if (!confirm("Are you sure you want to delete this entry?")) return;

  try {
    const response = await fetch(`${API_URL}?id=${id}`, {
      method: "DELETE",
    });
    const result = await response.json();

    if (result.status === 200) {
      await fetchEntries();
    } else {
      showError(result.data || "Failed to delete entry");
    }
  } catch (error) {
    showError("Network error: " + error.message);
  }
}

// Load entries on page load
fetchEntries();

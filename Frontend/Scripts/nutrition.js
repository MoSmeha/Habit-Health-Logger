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
renderAdminLink();

function showLoading() {
  const container = document.getElementById("loading-container");
  container.innerHTML = `
        <div class="loading">
            <p>Analyzing your diet and generating suggestions...</p>
        </div>
    `;
}

function hideLoading() {
  document.getElementById("loading-container").innerHTML = "";
}

function showError(message) {
  const container = document.getElementById("error-container");
  container.innerHTML = `
        <div class="error">
            <strong>Error:</strong> ${message}
        </div>
    `;
}

async function getMealSuggestions(userId) {
  const btn = document.getElementById("get-suggestions-btn");
  btn.disabled = true;

  document.getElementById("suggestions-container").innerHTML = "";
  document.getElementById("error-container").innerHTML = "";
  showLoading();

  try {
    const response = await fetch(
      `http://localhost/Health_AI/Backend/Controllers/NutritionController.php?user_id=${userId}`,
      {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
      }
    );

    const data = await response.json();
    hideLoading();

    if (data.status === 200) {
      displaySuggestions(data.data.suggestions);
    } else {
      showError(data.data.error || "Failed to get suggestions");
    }
  } catch (error) {
    hideLoading();
    console.error("Request failed:", error);
    showError("Failed to fetch meal suggestions. Please try again.");
  } finally {
    btn.disabled = false;
  }
}

function displaySuggestions(suggestions) {
  const container = document.getElementById("suggestions-container");
  container.innerHTML = "";

  // Analysis section
  if (suggestions.analysis) {
    const analysisSection = document.createElement("div");
    analysisSection.className = "section";
    analysisSection.innerHTML = `
            <h2 class="section-title">Your Diet Analysis</h2>
            <div class="analysis-card">
                <p class="analysis-text">${suggestions.analysis}</p>
            </div>
        `;
    container.appendChild(analysisSection);
  }

  // Meal suggestions section
  if (suggestions.suggestions && suggestions.suggestions.length > 0) {
    const mealsSection = document.createElement("div");
    mealsSection.className = "section";
    mealsSection.innerHTML = `
            <h2 class="section-title">Suggested Meals</h2>
            <div class="meals-grid" id="meals-grid"></div>
        `;
    container.appendChild(mealsSection);

    const mealsGrid = document.getElementById("meals-grid");
    suggestions.suggestions.forEach((meal) => {
      const mealCard = document.createElement("div");
      mealCard.className = "meal-card";

      const nutrients = meal.key_nutrients
        .map((n) => `<span class="nutrient-tag">${n}</span>`)
        .join("");

      mealCard.innerHTML = `
                <h3>${meal.meal_name}</h3>
                <p class="meal-description">${meal.description}</p>
                <div class="nutrients">${nutrients}</div>
            `;
      mealsGrid.appendChild(mealCard);
    });
  }

  // Tips section
  if (suggestions.general_tips && suggestions.general_tips.length > 0) {
    const tipsSection = document.createElement("div");
    tipsSection.className = "section";
    tipsSection.innerHTML = `
            <h2 class="section-title">General Tips</h2>
            <div class="tips-card">
                <ul id="tips-list"></ul>
            </div>
        `;
    container.appendChild(tipsSection);

    const tipsList = document.getElementById("tips-list");
    suggestions.general_tips.forEach((tip) => {
      const li = document.createElement("li");
      li.textContent = tip;
      tipsList.appendChild(li);
    });
  }
}

// Initialize
document.getElementById("get-suggestions-btn").addEventListener("click", () => {
  const user = getUser();

  if (!user || !user.id) {
    showError("Please log in to get meal suggestions");
    return;
  }

  getMealSuggestions(user.id);
});

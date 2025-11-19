function getUser() {
  const user = localStorage.getItem("user");
  return user ? JSON.parse(user) : null;
}

function renderUser() {
  const user = getUser();
  const userInfoDiv = document.getElementById("userInfo");
  const welcomeHeaderDiv = document.getElementById("welcomeHeader");
  const today = new Date().toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  if (user) {
    userInfoDiv.innerHTML = `Logged in as: ${user.username} (ID: ${user.id})`;
    welcomeHeaderDiv.innerHTML = `
      <h2>Welcome, ${user.username}!</h2>
      <p>${today}</p>
    `;
  } else {
    userInfoDiv.innerHTML = `No user in localStorage! <a href="Login.html">You must login first</a>`;
    welcomeHeaderDiv.innerHTML = `<p>${today}</p>`;
  }
}
renderUser();
function renderAdminLink() {
  const user = getUser();
  const navbar = document.getElementById("navbarLinks");

  if (!user || !navbar) return;

  // Prevent duplicating admin link if the page reloads
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
const user = getUser();
const userId = user ? user.id : null;

const parseBtn = document.getElementById("parseBtn");
const submitBtn = document.getElementById("submitBtn");
const parsedFields = document.getElementById("parsedFields");
const responseDiv = document.getElementById("response");
const userTextArea = document.getElementById("userText");

const generateTextBtn = document.getElementById("generateTextBtn");
const quickSlept = document.getElementById("quickSlept");
const quickCoffee = document.getElementById("quickCoffee");
const quickWalked = document.getElementById("quickWalked");
const quickMeal = document.getElementById("quickMeal");

generateTextBtn.addEventListener("click", () => {
  let parts = [];
  const sleptVal = quickSlept.value.trim();
  const coffeeVal = quickCoffee.value.trim();
  const walkedVal = quickWalked.value.trim();
  const mealVal = quickMeal.value.trim();

  if (sleptVal) {
    parts.push(`I slept for ${sleptVal} hours.`);
  }
  if (coffeeVal) {
    const coffeeNum = parseInt(coffeeVal);
    if (coffeeNum === 1) {
      parts.push(`I drank 1 cup of coffee.`);
    } else if (coffeeNum > 1) {
      parts.push(`I drank ${coffeeNum} cups of coffee.`);
    }
  }
  if (walkedVal) {
    parts.push(`I walked ${walkedVal} steps.`);
  }
  if (mealVal) {
    parts.push(`I ate ${mealVal}.`);
  }

  const existingText = userTextArea.value.trim();
  if (existingText) {
    userTextArea.value = existingText + " " + parts.join(" ");
  } else {
    userTextArea.value = parts.join(" ");
  }

  quickSlept.value = "";
  quickCoffee.value = "";
  quickWalked.value = "";
  quickMeal.value = "";
});

parseBtn.addEventListener("click", async () => {
  const text = userTextArea.value.trim();
  if (!text) {
    responseDiv.textContent = "Please enter some text.";
    return;
  }

  if (!userId) {
    responseDiv.textContent = "Error: You must be logged in to parse.";
    return;
  }

  responseDiv.textContent = "Parsing with AI...";

  try {
    const res = await fetch(
      "http://localhost/Health_AI/Backend/Controllers/EntryController.php?action=parse",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ input_text: text }),
      }
    );

    const response = await res.json();

    if (!res.ok || response.status !== 200) {
      responseDiv.textContent =
        "Error: " + (response.data?.error || "Unknown error");
      return;
    }

    const parsed = response.data.parsed || {
      slept: "",
      coffee: "",
      walked: "",
      meal: "",
    };

    document.getElementById("slept").value = parsed.slept || "";
    document.getElementById("coffee").value = parsed.coffee || "";
    document.getElementById("walked").value = parsed.walked || "";
    document.getElementById("meal").value = parsed.meal || "";

    parsedFields.style.display = "block";
    responseDiv.style.color = "#28a745";
    responseDiv.textContent =
      "Parsed! Review and edit the data, then click Submit to save.";
  } catch (err) {
    responseDiv.textContent = "Error: " + err.message;
  }
});

submitBtn.addEventListener("click", async () => {
  if (!userId) {
    responseDiv.textContent = "Error: You must be logged in to submit.";
    return;
  }

  const payload = {
    user_id: userId,
    input_text: userTextArea.value.trim(),
    slept: document.getElementById("slept").value.trim() || null,
    coffee: parseInt(document.getElementById("coffee").value) || null,
    walked: document.getElementById("walked").value.trim() || null,
    meal: document.getElementById("meal").value.trim() || null,
  };

  responseDiv.textContent = "Submitting to database...";

  try {
    const res = await fetch(
      "http://localhost/Health_AI/Backend/Controllers/EntryController.php",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      }
    );

    const response = await res.json();

    if (res.ok && response.status === 201) {
      responseDiv.textContent =
        "Saved successfully! Entry ID: " + response.data.user_entry_id;

      parsedFields.style.display = "none";
      userTextArea.value = "";

      document.getElementById("slept").value = "";
      document.getElementById("coffee").value = "";
      document.getElementById("walked").value = "";
      document.getElementById("meal").value = "";
    } else {
      responseDiv.textContent =
        "Error: " + (response.data?.error || "Unknown error");
    }
  } catch (err) {
    responseDiv.textContent = "Error: " + err.message;
  }
});

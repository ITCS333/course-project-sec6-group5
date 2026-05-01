// --- Element Selections ---
const weekListSection = document.getElementById("week-list-section");

// --- Functions ---

function createWeekArticle(week) {
  const article = document.createElement("article");

  article.innerHTML = `
    <h2>${week.title}</h2>
    <p>Starts on: ${week.start_date}</p>
    <p>${week.description}</p>
    <a href="details.html?id=${week.id}">View Details & Discussion</a>
  `;

  return article;
}

async function loadWeeks() {
  const response = await fetch("./api/index.php");
  const result = await response.json();

  // Clear previous content
  weekListSection.innerHTML = "";

  if (result.success === true) {
    result.data.forEach(function (week) {
      const article = createWeekArticle(week);
      weekListSection.appendChild(article);
    });
  } else {
    weekListSection.textContent = "Failed to load weeks.";
  }
}

// --- Initial Page Load ---
loadWeeks();

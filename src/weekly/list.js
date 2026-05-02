// --- Element Selections ---
// TODO: Select the section for the week list using its id 'week-list-section'.
const section = document.getElementById("week-list-section");

// --- Functions ---

function createWeekArticle(week) {
  // TODO: Implement createWeekArticle.
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
  // TODO: Implement loadWeeks (async).
  const response = await fetch("./api/index.php");
  const result = await response.json();

  section.innerHTML = "";

  if (result.success) {
    result.data.forEach(week => {
      const article = createWeekArticle(week);
      section.appendChild(article);
    });
  }
}

// --- Initial Page Load ---
loadWeeks();

const section = document.getElementById("week-list-section");

function createWeekArticle(week) {
  const article = document.createElement("article");

  const id = Number(week.id);

  article.innerHTML = `
    <h2>${week.title}</h2>
    <p>Starts on: ${week.start_date}</p>
    <p>${week.description}</p>
    <a href="details.html?id=${id}">View Details & Discussion</a>
  `;

  return article;
}

async function loadWeeks() {
  try {
    const res = await fetch("./api/index.php");
    const result = await res.json();

    section.innerHTML = "";

    if (result && result.success && Array.isArray(result.data)) {
      result.data.forEach(week => {
        section.appendChild(createWeekArticle(week));
      });
    }
  } catch (e) {
    console.log("Error loading weeks", e);
  }
}

loadWeeks();

// --- Global Data Store ---
let currentWeekId  = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select each element by its id:
//   weekTitle, weekStartDate, weekDescription,
//   weekLinksList, commentList, commentForm, newCommentInput.
const weekTitle = document.getElementById("week-title");
const weekStartDate = document.getElementById("week-start-date");
const weekDescription = document.getElementById("week-description");
const weekLinksList = document.getElementById("week-links-list");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentInput = document.getElementById("new-comment");

// --- Functions ---

function getWeekIdFromURL() {
  // TODO: Implement getWeekIdFromURL.
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

function renderWeekDetails(week) {
  // TODO: Implement renderWeekDetails.
  weekTitle.textContent = week.title;
  weekStartDate.textContent = "Starts on: " + week.start_date;
  weekDescription.textContent = week.description;

  weekLinksList.innerHTML = "";

  week.links.forEach(url => {
    const li = document.createElement("li");
    const a = document.createElement("a");

    a.href = url;
    a.textContent = url;
    a.target = "_blank";

    li.appendChild(a);
    weekLinksList.appendChild(li);
  });
}

function createCommentArticle(comment) {
  // TODO: Implement createCommentArticle.
  const article = document.createElement("article");

  article.innerHTML = `
    <p>${comment.text}</p>
    <footer>Posted by: ${comment.author}</footer>
  `;

  return article;
}

function renderComments() {
  // TODO: Implement renderComments.
  commentList.innerHTML = "";

  currentComments.forEach(comment => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

async function handleAddComment(event) {
  // TODO: Implement handleAddComment (async).
  event.preventDefault();

  const commentText = newCommentInput.value.trim();

  if (!commentText) return;

  const response = await fetch(`./api/index.php?action=comment`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      week_id: Number(currentWeekId),
      author: "Student",
      text: commentText
    })
  });

  const result = await response.json();

  if (result.success) {
    currentComments.push(result.data);
    renderComments();
    newCommentInput.value = "";
  }
}

async function initializePage() {
  // TODO: Implement initializePage (async).

  currentWeekId = getWeekIdFromURL();

  if (!currentWeekId) {
    weekTitle.textContent = "Week not found.";
    return;
  }

  const [weekRes, commentsRes] = await Promise.all([
    fetch(`./api/index.php?id=${currentWeekId}`),
    fetch(`./api/index.php?action=comments&week_id=${currentWeekId}`)
  ]);

  const weekResult = await weekRes.json();
  const commentsResult = await commentsRes.json();

  if (!weekResult.success) {
    weekTitle.textContent = "Week not found.";
    return;
  }

  const week = weekResult.data;
  currentComments = commentsResult.success ? commentsResult.data : [];

  renderWeekDetails(week);
  renderComments();

  commentForm.addEventListener("submit", handleAddComment);
}

// --- Initial Page Load ---
initializePage();

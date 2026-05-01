let currentWeekId = null;
let currentComments = [];

const weekTitle = document.getElementById("week-title");
const weekStartDate = document.getElementById("week-start-date");
const weekDescription = document.getElementById("week-description");
const weekLinksList = document.getElementById("week-links-list");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentInput = document.getElementById("new-comment");

function getWeekIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return Number(params.get("id"));
}

function renderWeekDetails(week) {
  if (!week) return;   

  weekTitle.textContent = week.title || "";
  weekStartDate.textContent = "Starts on: " + (week.start_date || "");
  weekDescription.textContent = week.description || "";

  weekLinksList.innerHTML = "";

  const links = Array.isArray(week.links) ? week.links : [];

  links.forEach((url) => {
    const li = document.createElement("li");
    const a = document.createElement("a");

    a.href = url;
    a.textContent = url;

    li.appendChild(a);
    weekLinksList.appendChild(li);
  });
}

function createCommentArticle(comment) {
  const article = document.createElement("article");

  const p = document.createElement("p");
  p.textContent = comment?.text || "";

  const footer = document.createElement("footer");
  footer.textContent = "Posted by: " + (comment?.author || "");

  article.appendChild(p);
  article.appendChild(footer);

  return article;
}

function renderComments() {
  commentList.innerHTML = "";

  const comments = Array.isArray(currentComments) ? currentComments : [];

  comments.forEach((comment) => {
    commentList.appendChild(createCommentArticle(comment));
  });
}

async function handleAddComment(event) {
  event.preventDefault();

  const text = newCommentInput.value.trim();
  if (!text) return;

  const response = await fetch("./api/index.php?action=comment", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      week_id: currentWeekId,
      author: "Student",
      text: text
    })
  });

  const result = await response.json();

  if (result && result.success) {
    currentComments = Array.isArray(currentComments) ? currentComments : [];
    currentComments.push(result.data);
    renderComments();
    newCommentInput.value = "";
  }
}

async function initializePage() {
  currentWeekId = getWeekIdFromURL();

  if (!currentWeekId) {
    weekTitle.textContent = "Week not found.";
    return;
  }

  const [weekRes, commentsRes] = await Promise.all([
    fetch(`./api/index.php?id=${currentWeekId}`),
    fetch(`./api/index.php?action=comments&week_id=${currentWeekId}`)
  ]);

  const weekData = await weekRes.json();
  const commentsData = await commentsRes.json();

  const week = weekData?.data || null;
  currentComments = Array.isArray(commentsData?.data) ? commentsData.data : [];

  if (weekData && weekData.success && week) {
    renderWeekDetails(week);
    renderComments();
    commentForm.addEventListener("submit", handleAddComment);
  } else {
    weekTitle.textContent = "Week not found.";
  }
}

initializePage();

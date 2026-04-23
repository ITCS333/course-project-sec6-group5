/*
  Requirement: Populate the weekly detail page and handle the discussion forum.

  Instructions:
  1. This file is already linked to `details.html` via:
         <script src="details.js" defer></script>

  2. The following ids must exist in details.html (already listed in the
     HTML comments):
       #week-title          — <h1>
       #week-start-date     — <p>
       #week-description    — <p>
       #week-links-list     — <ul>
       #comment-list        — <div>
       #comment-form        — <form>
       #new-comment         — <textarea>

  3. Implement the TODOs below.

  API base URL: ./api/index.php
  Week object shape returned by the API:
    {
      id:          number,
      title:       string,
      start_date:  string,
      description: string,
      links:       string[]
    }

  Comment object shape returned by the API
  (from the comments_week table):
    {
      id:          number,
      week_id:     number,
      author:      string,
      text:        string,
      created_at:  string
    }
*/

// --- Global Data Store ---
let currentWeekId = null;
let currentComments = [];

// --- Element Selections ---
const weekTitle = document.getElementById("week-title");
const weekStartDate = document.getElementById("week-start-date");
const weekDescription = document.getElementById("week-description");
const weekLinksList = document.getElementById("week-links-list");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentInput = document.getElementById("new-comment");

// --- Functions ---

function getWeekIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

function renderWeekDetails(week) {
  weekTitle.textContent = week.title;
  weekStartDate.textContent = "Starts on: " + week.start_date;
  weekDescription.textContent = week.description;

  weekLinksList.innerHTML = "";

  week.links.forEach(function (url) {
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
  const article = document.createElement("article");

  const p = document.createElement("p");
  p.textContent = comment.text;

  const footer = document.createElement("footer");
  footer.textContent = "Posted by: " + comment.author;

  article.appendChild(p);
  article.appendChild(footer);

  return article;
}

function renderComments() {
  commentList.innerHTML = "";

  currentComments.forEach(function (comment) {
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

  if (result.success === true) {
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

  const weekPromise = fetch(`./api/index.php?id=${currentWeekId}`);
  const commentsPromise = fetch(
    `./api/index.php?action=comments&week_id=${currentWeekId}`
  );

  const [weekRes, commentsRes] = await Promise.all([
    weekPromise,
    commentsPromise
  ]);

  const weekData = await weekRes.json();
  const commentsData = await commentsRes.json();

  const week = weekData.data;
  currentComments = commentsData.data || [];

  if (weekData.success && week) {
    renderWeekDetails(week);
    renderComments();
    commentForm.addEventListener("submit", handleAddComment);
  } else {
    weekTitle.textContent = "Week not found.";
  }
}

// --- Initial Page Load ---
initializePage();

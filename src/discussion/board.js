// --- Global Data Store ---
let topics = [];

const form = document.getElementById("new-topic-form");
const topicListContainer = document.getElementById("topic-list-container");

// ----------------------------
// Create Topic Article
// ----------------------------
function createTopicArticle(topic) {
  const article = document.createElement("article");

  const h3 = document.createElement("h3");
  const link = document.createElement("a");

  link.href = topic.html?id=${topic.id};
  link.textContent = topic.subject;

  h3.appendChild(link);

  const footer = document.createElement("footer");
  footer.textContent = Posted by: ${topic.author} on ${topic.created_at};

  const div = document.createElement("div");

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.classList.add("edit-btn");
  editBtn.dataset.id = topic.id;

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.classList.add("delete-btn");
  deleteBtn.dataset.id = topic.id;

  div.appendChild(editBtn);
  div.appendChild(deleteBtn);

  article.appendChild(h3);
  article.appendChild(footer);
  article.appendChild(div);

  return article;
}

// ----------------------------
// Render Topics
// ----------------------------
function renderTopics() {
  topicListContainer.innerHTML = "";

  topics.forEach((topic) => {
    const article = createTopicArticle(topic);
    topicListContainer.appendChild(article);
  });
}

// ----------------------------
// Create Topic
// ----------------------------
async function handleCreateTopic(event) {
  event.preventDefault();

  const subject = document.getElementById("topic-subject").value.trim();
  const message = document.getElementById("topic-message").value.trim();

  if (!subject || !message) return;

  const response = await fetch("./api/index.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      subject,
      message,
      author: "Student",
    }),
  });

  const result = await response.json();

  if (result.success) {
    topics.push(result.data);
    renderTopics();
    form.reset();
  }
}

// ----------------------------
// Delete / Edit Topic
// ----------------------------
async function handleTopicListClick(event) {
  const id = event.target.dataset.id;

  if (event.target.classList.contains("delete-btn")) {
    const response = await fetch(./api/index.php?id=${id}, {
      method: "DELETE",
    });

    const result = await response.json();

    if (result.success) {
      topics = topics.filter((t) => t.id != id);
      renderTopics();
    }
  }

  if (event.target.classList.contains("edit-btn")) {
    const topic = topics.find((t) => t.id == id);

    document.getElementById("topic-subject").value = topic.subject;
    document.getElementById("topic-message").value = topic.message;

    const btn = document.getElementById("create-topic");
    btn.textContent = "Update Topic";
    btn.dataset.editId = id;
  }
}

// ----------------------------
// Load Data
// ----------------------------
async function loadAndInitialize() {
  const response = await fetch("./api/index.php");
  const result = await response.json();

  if (result.success) {
    topics = result.data;
    renderTopics();
  }

  form.addEventListener("submit", handleCreateTopic);
  topicListContainer.addEventListener("click", handleTopicListClick);
}

loadAndInitialize();

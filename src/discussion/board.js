// --- Global Data Store ---
let topics = [];

// --- Element Selections ---
const form = document.getElementById("new-topic-form");
const topicListContainer = document.getElementById("topic-list-container");
const createTopicBtn = document.getElementById("create-topic");

// ----------------------------
// Create Topic Article
// ----------------------------
function createTopicArticle(topic) {
  const article = document.createElement("article");

  const h3 = document.createElement("h3");
  const link = document.createElement("a");

  link.href = `topic.html?id=${topic.id}`;
  link.textContent = topic.subject;
  h3.appendChild(link);

  const footer = document.createElement("footer");
  footer.textContent = `Posted by: ${topic.author} on ${topic.created_at}`;

  const div = document.createElement("div");
  div.classList.add("actions");

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.classList.add("edit-btn");
  editBtn.dataset.id = topic.id;
  editBtn.type = "button";

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.classList.add("delete-btn");
  deleteBtn.dataset.id = topic.id;
  deleteBtn.type = "button";

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
  if (!topicListContainer) return;

  topicListContainer.innerHTML = "";

  topics.forEach((topic) => {
    topicListContainer.appendChild(createTopicArticle(topic));
  });
}

// ----------------------------
// Load Topics
// ----------------------------
async function loadTopics() {
  try {
    const response = await fetch("./api/index.php");
    const result = await response.json();

    if (result.success) {
      topics = result.data;
      renderTopics();
    }
  } catch (error) {
    console.error("Error loading topics:", error);
  }
}

// ----------------------------
// Create / Update Topic
// ----------------------------
async function handleCreateTopic(event) {
  event.preventDefault();

  const subjectInput = document.getElementById("topic-subject");
  const messageInput = document.getElementById("topic-message");

  if (!subjectInput || !messageInput || !createTopicBtn) return;

  const subject = subjectInput.value.trim();
  const message = messageInput.value.trim();

  if (!subject || !message) return;

  const editId = createTopicBtn.dataset.editId;

  try {
    if (editId) {
      const response = await fetch("./api/index.php", {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          id: editId,
          subject: subject,
          message: message,
        }),
      });

      const result = await response.json();

      if (result.success) {
        topics = topics.map((topic) =>
          String(topic.id) === String(editId)
            ? { ...topic, subject, message }
            : topic
        );

        renderTopics();
        form.reset();
        createTopicBtn.textContent = "Create Topic";
        delete createTopicBtn.dataset.editId;
      }
    } else {
      const response = await fetch("./api/index.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          subject: subject,
          message: message,
          author: "Student",
        }),
      });

      const result = await response.json();

      if (result.success) {
        await loadTopics();
        form.reset();
      }
    }
  } catch (error) {
    console.error("Error saving topic:", error);
  }
}

// ----------------------------
// Delete / Edit Topic
// ----------------------------
async function handleTopicListClick(event) {
  const id = event.target.dataset.id;
  if (!id) return;

  try {
    if (event.target.classList.contains("delete-btn")) {
      const response = await fetch(`./api/index.php?id=${id}`, {
        method: "DELETE",
      });

      const result = await response.json();

      if (result.success) {
        topics = topics.filter((topic) => String(topic.id) !== String(id));
        renderTopics();
      }
    }

    if (event.target.classList.contains("edit-btn")) {
      const topic = topics.find((topic) => String(topic.id) === String(id));
      if (!topic) return;

      const subjectInput = document.getElementById("topic-subject");
      const messageInput = document.getElementById("topic-message");

      if (!subjectInput || !messageInput || !createTopicBtn) return;

      subjectInput.value = topic.subject;
      messageInput.value = topic.message;

      createTopicBtn.textContent = "Update Topic";
      createTopicBtn.dataset.editId = id;
    }
  } catch (error) {
    console.error("Error handling topic action:", error);
  }
}

// ----------------------------
// Initialize
// ----------------------------
if (form) {
  form.addEventListener("submit", handleCreateTopic);
}

if (topicListContainer) {
  topicListContainer.addEventListener("click", handleTopicListClick);
}

if (form && topicListContainer) {
  loadTopics();
}

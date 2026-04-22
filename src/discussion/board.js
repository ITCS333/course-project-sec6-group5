/*
  Requirement: Make the "Discussion Board" page interactive.
*/

// --- Global Data Store ---
let topics = [];

// --- Element Selections ---
const form = document.getElementById("new-topic-form");
const topicListContainer = document.getElementById("topic-list-container");

// --- Functions ---
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

  const editBtn = document.createElement("button");
  editBtn.classList.add("edit-btn");
  editBtn.dataset.id = topic.id;
  editBtn.textContent = "Edit";
  editBtn.type = "button";

  const deleteBtn = document.createElement("button");
  deleteBtn.classList.add("delete-btn");
  deleteBtn.dataset.id = topic.id;
  deleteBtn.textContent = "Delete";
  deleteBtn.type = "button";

  div.appendChild(editBtn);
  div.appendChild(deleteBtn);

  article.appendChild(h3);
  article.appendChild(footer);
  article.appendChild(div);

  return article;
}

function renderTopics() {
  topicListContainer.innerHTML = "";

  topics.forEach((topic) => {
    const article = createTopicArticle(topic);
    topicListContainer.appendChild(article);
  });
}

async function handleCreateTopic(event) {
  event.preventDefault();

  const subjectInput = document.getElementById("topic-subject");
  const messageInput = document.getElementById("topic-message");
  const createBtn = document.getElementById("create-topic");

  const subject = subjectInput.value.trim();
  const message = messageInput.value.trim();

  if (!subject || !message) {
    return;
  }

  const editId = createBtn.dataset.editId;

  if (editId) {
    await handleUpdateTopic(editId, { subject, message });
    createBtn.textContent = "Create Topic";
    delete createBtn.dataset.editId;
    form.reset();
    return;
  }

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

  if (result.success === true) {
    topics.push({
      id: result.id,
      subject: subject,
      message: message,
      author: "Student",
      created_at: new Date().toISOString().slice(0, 19).replace("T", " "),
    });

    renderTopics();
    form.reset();
  }
}

async function handleUpdateTopic(id, fields) {
  const response = await fetch("./api/index.php", {
    method: "PUT",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      id: id,
      subject: fields.subject,
      message: fields.message,
    }),
  });

  const result = await response.json();

  if (result.success === true) {
    topics = topics.map((topic) => {
      if (String(topic.id) === String(id)) {
        return {
          ...topic,
          subject: fields.subject,
          message: fields.message,
        };
      }
      return topic;
    });

    renderTopics();
  }
}

async function handleTopicListClick(event) {
  if (event.target.classList.contains("delete-btn")) {
    const id = event.target.dataset.id;

    const response = await fetch(`./api/index.php?id=${id}`, {
      method: "DELETE",
    });

    const result = await response.json();

    if (result.success === true) {
      topics = topics.filter((topic) => String(topic.id) !== String(id));
      renderTopics();
    }
  }

  if (event.target.classList.contains("edit-btn")) {
    const id = event.target.dataset.id;
    const topic = topics.find((t) => String(t.id) === String(id));

    if (!topic) {
      return;
    }

    document.getElementById("topic-subject").value = topic.subject;
    document.getElementById("topic-message").value = topic.message;

    const createBtn = document.getElementById("create-topic");
    createBtn.textContent = "Update Topic";
    createBtn.dataset.editId = topic.id;
  }
}

async function loadAndInitialize() {
  const response = await fetch("./api/index.php");
  const result = await response.json();

  if (result.success === true) {
    topics = result.data;
    renderTopics();
  }

  form.addEventListener("submit", handleCreateTopic);
  topicListContainer.addEventListener("click", handleTopicListClick);
}

// --- Initial Page Load ---
loadAndInitialize();

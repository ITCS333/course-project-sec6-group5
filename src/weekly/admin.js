/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.

  Instructions:
  1. This file is already linked to `admin.html` via:
         <script src="admin.js" defer></script>

  2. In `admin.html`:
     - The form has id="week-form".
     - The submit button has id="add-week".
     - The <tbody> has id="weeks-tbody".
     - Columns rendered per row: Week Title | Start Date | Description | Actions.

  3. Implement the TODOs below.

  API base URL: ./api/index.php
  All requests and responses use JSON.
  Successful list response shape: { success: true, data: [ ...week objects ] }
  Each week object shape:
    {
      id:          number,
      title:       string,
      start_date:  string,
      description: string,
      links:       string[]
    }
*/

// --- Global Data Store ---
let weeks = [];

// --- Element Selections ---
const form = document.getElementById("week-form");
const tableBody = document.getElementById("weeks-tbody");
const submitBtn = document.getElementById("add-week");

// --- Functions ---

function createWeekRow(week) {
  const tr = document.createElement("tr");

  tr.innerHTML = `
    <td>${week.title}</td>
    <td>${week.start_date}</td>
    <td>${week.description}</td>
    <td>
      <button class="edit-btn" data-id="${week.id}">Edit</button>
      <button class="delete-btn" data-id="${week.id}">Delete</button>
    </td>
  `;

  return tr;
}

function renderTable() {
  tableBody.innerHTML = "";

  weeks.forEach(function (week) {
    const row = createWeekRow(week);
    tableBody.appendChild(row);
  });
}

async function handleAddWeek(event) {
  event.preventDefault();

  const title = document.getElementById("week-title").value.trim();
  const start_date = document.getElementById("week-start-date").value;
  const description = document.getElementById("week-description").value.trim();
  const links = document
    .getElementById("week-links")
    .value
    .split("\n")
    .map(function (link) {
      return link.trim();
    })
    .filter(function (link) {
      return link !== "";
    });

  const editId = submitBtn.dataset.editId;

  if (editId) {
    await handleUpdateWeek(editId, {
      title: title,
      start_date: start_date,
      description: description,
      links: links
    });
    return;
  }

  const response = await fetch("./api/index.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      title: title,
      start_date: start_date,
      description: description,
      links: links
    })
  });

  const result = await response.json();

  if (result.success === true) {
    weeks.push({
      id: result.id,
      title: title,
      start_date: start_date,
      description: description,
      links: links
    });

    renderTable();
    form.reset();
  }
}

async function handleUpdateWeek(id, fields) {
  const response = await fetch("./api/index.php", {
    method: "PUT",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      id: Number(id),
      title: fields.title,
      start_date: fields.start_date,
      description: fields.description,
      links: fields.links
    })
  });

  const result = await response.json();

  if (result.success === true) {
    const index = weeks.findIndex(function (week) {
      return week.id == id;
    });

    if (index !== -1) {
      weeks[index] = {
        id: Number(id),
        title: fields.title,
        start_date: fields.start_date,
        description: fields.description,
        links: fields.links
      };
    }

    renderTable();
    form.reset();
    submitBtn.textContent = "Add Week";
    submitBtn.removeAttribute("data-edit-id");
  }
}

async function handleTableClick(event) {
  const target = event.target;
  const id = target.dataset.id;

  if (target.classList.contains("delete-btn")) {
    const response = await fetch(`./api/index.php?id=${id}`, {
      method: "DELETE"
    });

    const result = await response.json();

    if (result.success === true) {
      weeks = weeks.filter(function (week) {
        return week.id != id;
      });

      renderTable();
    }
  }

  if (target.classList.contains("edit-btn")) {
    const week = weeks.find(function (item) {
      return item.id == id;
    });

    if (!week) return;

    document.getElementById("week-title").value = week.title;
    document.getElementById("week-start-date").value = week.start_date;
    document.getElementById("week-description").value = week.description;
    document.getElementById("week-links").value = week.links.join("\n");

    submitBtn.textContent = "Update Week";
    submitBtn.dataset.editId = week.id;
  }
}

async function loadAndInitialize() {
  const response = await fetch("./api/index.php");
  const result = await response.json();

  if (result.success === true) {
    weeks = result.data;
    renderTable();
  }

  form.addEventListener("submit", handleAddWeek);
  tableBody.addEventListener("click", handleTableClick);
}

// --- Initial Page Load ---
loadAndInitialize();

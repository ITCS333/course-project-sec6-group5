// --- Global Data Store ---
let weeks = [];

// --- Element Selections ---
// TODO: Select the week form by id 'week-form'.
const form = document.getElementById("week-form");

// TODO: Select the weeks table body by id 'weeks-tbody'.
const tableBody = document.getElementById("weeks-tbody");
const submitBtn = document.getElementById("add-week");

// --- Functions ---

function createWeekRow(week) {
  // TODO: Implement createWeekRow.
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
  // TODO: Implement renderTable.
  tableBody.innerHTML = "";

  weeks.forEach(week => {
    const row = createWeekRow(week);
    tableBody.appendChild(row);
  });
}

async function handleAddWeek(event) {
  // TODO: Implement handleAddWeek (async).
  event.preventDefault();

  const title = document.getElementById("week-title").value;
  const start_date = document.getElementById("week-start-date").value;
  const description = document.getElementById("week-description").value;

  const links = document
    .getElementById("week-links")
    .value
    .split("\n")
    .map(link => link.trim())
    .filter(link => link !== "");

  const editId = submitBtn.dataset.editId;

  // If editing → update
  if (editId) {
    await handleUpdateWeek(editId, { title, start_date, description, links });
    return;
  }

  // Otherwise → create new
  const response = await fetch("./api/index.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ title, start_date, description, links })
  });

  const result = await response.json();

  if (result.success) {
    weeks.push({
      id: result.id,
      title,
      start_date,
      description,
      links
    });

    renderTable();
    form.reset();
  }
}

async function handleUpdateWeek(id, fields) {
  // TODO: Implement handleUpdateWeek (async).
  const response = await fetch("./api/index.php", {
    method: "PUT",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      id: Number(id),
      ...fields
    })
  });

  const result = await response.json();

  if (result.success) {
    const index = weeks.findIndex(w => w.id == id);

    if (index !== -1) {
      weeks[index] = { id: Number(id), ...fields };
    }

    renderTable();
    form.reset();

    submitBtn.textContent = "Add Week";
    delete submitBtn.dataset.editId;
  }
}

async function handleTableClick(event) {
  // TODO: Implement handleTableClick (async).
  const target = event.target;

  // DELETE
  if (target.classList.contains("delete-btn")) {
    const id = target.dataset.id;

    const response = await fetch(`./api/index.php?id=${id}`, {
      method: "DELETE"
    });

    const result = await response.json();

    if (result.success) {
      weeks = weeks.filter(w => w.id != id);
      renderTable();
    }
  }

  // EDIT
  if (target.classList.contains("edit-btn")) {
    const id = target.dataset.id;
    const week = weeks.find(w => w.id == id);

    if (!week) return;

    document.getElementById("week-title").value = week.title;
    document.getElementById("week-start-date").value = week.start_date;
    document.getElementById("week-description").value = week.description;
    document.getElementById("week-links").value = week.links.join("\n");

    submitBtn.textContent = "Update Week";
    submitBtn.dataset.editId = id;
  }
}

async function loadAndInitialize() {
  // TODO: Implement loadAndInitialize (async).
  const response = await fetch("./api/index.php");
  const result = await response.json();

  if (result.success) {
    weeks = result.data;
    renderTable();
  }

  form.addEventListener("submit", handleAddWeek);
  tableBody.addEventListener("click", handleTableClick);
}

// --- Initial Page Load ---
loadAndInitialize();

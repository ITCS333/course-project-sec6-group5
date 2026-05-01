let weeks = [];

const form = document.getElementById("week-form");
const tableBody = document.getElementById("weeks-tbody");
const submitBtn = document.getElementById("add-week");

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
  const links = document.getElementById("week-links").value
    .split("\n")
    .map(l => l.trim())
    .filter(l => l !== "");

  const editId = submitBtn.dataset.editId;

  if (editId) {
    await handleUpdateWeek(Number(editId), {
      title,
      start_date,
      description,
      links
    });
    return;
  }

  const response = await fetch("api/index.php", {
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
  const response = await fetch("api/index.php", {
    method: "PUT",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ id, ...fields })
  });

  const result = await response.json();

  if (result.success) {
    const index = weeks.findIndex(w => w.id === id);

    if (index !== -1) {
      weeks[index] = { id, ...fields };
    }

    renderTable();
    form.reset();
    submitBtn.textContent = "Add Week";
    submitBtn.removeAttribute("data-edit-id");
  }
}

async function handleTableClick(event) {
  const target = event.target;
  const id = Number(target.dataset.id);

  if (target.classList.contains("delete-btn")) {
    const response = await fetch(`api/index.php?id=${id}`, {
      method: "DELETE"
    });

    const result = await response.json();

    if (result.success) {
      weeks = weeks.filter(w => w.id !== id);
      renderTable();
    }
  }

  if (target.classList.contains("edit-btn")) {
    const week = weeks.find(w => w.id === id);
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
  const response = await fetch("api/index.php");
  const result = await response.json();

  if (result.success) {
    weeks = result.data || [];
    renderTable();
  }

  form.addEventListener("submit", handleAddWeek);
  tableBody.addEventListener("click", handleTableClick);
}

loadAndInitialize();

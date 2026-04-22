/*
  Requirement: Add interactivity and data management to the Admin Portal.

  Instructions:
  1. This file is loaded by the <script src="manage_users.js" defer> tag in manage_users.html.
     The 'defer' attribute guarantees the DOM is fully parsed before this script runs.
  2. Implement the JavaScript functionality as described in the TODO comments.
  3. All data is fetched from and written to the PHP API at '../api/index.php'.
     The local 'users' array is used only as a client-side cache for search and sort.
*/

// --- Global Data Store ---
let users = [];

// --- Element Selections ---
const userTableBody = document.getElementById("user-table-body");
const addUserForm = document.getElementById("add-user-form");
const passwordForm = document.getElementById("password-form");
const searchInput = document.getElementById("search-input");
const tableHeaders = document.querySelectorAll("#user-table thead th");

// --- Functions ---

/**
 * TODO: Implement the createUserRow function.
 */
function createUserRow(user) {
  const tr = document.createElement("tr");

  tr.innerHTML = `
    <td>${user.name}</td>
    <td>${user.email}</td>
    <td>${user.is_admin == 1 ? "Yes" : "No"}</td>
    <td>
      <button class="edit-btn" data-id="${user.id}">Edit</button>
      <button class="delete-btn" data-id="${user.id}">Delete</button>
    </td>
  `;

  return tr;
}

/**
 * TODO: Implement the renderTable function.
 */
function renderTable(userArray) {
 userTableBody.innerHTML = "";

  userArray.forEach(user => {
    userTableBody.appendChild(createUserRow(user));
  });
}

/**
 * TODO: Implement the handleChangePassword function.
 */
async function handleChangePassword(event) {
   event.preventDefault();

  const current = document.getElementById("current-password").value;
  const newPass = document.getElementById("new-password").value;
  const confirm = document.getElementById("confirm-password").value;

  if (newPass !== confirm) {
    alert("Passwords do not match.");
    return;
  }

  if (newPass.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  try {
    const res = await fetch("../api/index.php?action=change_password", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id: 1,
        current_password: current,
        new_password: newPass
      })
    });

    const data = await res.json();

    if (data.success) {
      alert("Password updated successfully!");

      document.getElementById("current-password").value = "";
      document.getElementById("new-password").value = "";
      document.getElementById("confirm-password").value = "";
    } else {
      alert(data.message);
    }

  } catch (err) {
    alert("Server error");
  }
}

/**
 * TODO: Implement the handleAddUser function.
 */
async function handleAddUser(event) {
  event.preventDefault();

  const name = document.getElementById("user-name").value;
  const email = document.getElementById("user-email").value;
  const password = document.getElementById("default-password").value;
  const is_admin = document.getElementById("is-admin").value;

  if (!name || !email || !password) {
    alert("Please fill out all required fields.");
    return;
  }

  if (password.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  try {
    const res = await fetch("../api/index.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name, email, password, is_admin })
    });

    if (res.status === 201 || res.ok) {
      await loadUsersAndInitialize();
      addUserForm.reset();
    } else {
      const data = await res.json();
      alert(data.message || "Failed to add user");
    }
  } catch (err) {
    alert("Server error");
  }
}

/**
 * TODO: Implement the handleTableClick function.
 */
async function handleTableClick(event) {
  const id = event.target.dataset.id;
  if (!id) return;

  // DELETE
  if (event.target.classList.contains("delete-btn")) {
    try {
      const res = await fetch(`../api/index.php?id=${id}`, {
        method: "DELETE"
      });
      const data = await res.json();
      if (data.success) {
        users = users.filter(u => u.id != id);
        renderTable(users);
      } else {
        alert(data.message);
      }
    } catch (err) {
      alert("Server error");
    }
  }

  // EDIT
  if (event.target.classList.contains("edit-btn")) {
    const user = users.find(u => u.id == id);
    if (!user) return;

    const newName = prompt("Edit name:", user.name);
    if (newName) {
      try {
        const res = await fetch("../api/index.php", {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id, name: newName })
        });
        if (res.ok) {
          await loadUsersAndInitialize();
        }
      } catch (err) {
        alert("Server error");
      }
    }
  }
}

/**
 * TODO: Implement the handleSearch function.
 */
function handleSearch() {
  const term = searchInput.value.toLowerCase();

  if (!term) {
    renderTable(users);
    return;
  }

  const filtered = users.filter(u =>
    u.name.toLowerCase().includes(term) ||
    u.email.toLowerCase().includes(term)
  );

  renderTable(filtered);
}

/**
 * TODO: Implement the handleSort function.
 */
function handleSort(event) {
const th = event.currentTarget;
  const key = th.dataset.key;

  let dir = th.dataset.sortDir === "asc" ? "desc" : "asc";
  th.dataset.sortDir = dir;

  users.sort((a, b) => {
    let valA = a[key];
    let valB = b[key];

    if (key === "name" || key === "email") {
      return dir === "asc"
        ? valA.localeCompare(valB)
        : valB.localeCompare(valA);
    } else {
      return dir === "asc"
        ? Number(valA) - Number(valB)
        : Number(valB) - Number(valA);
    }
  });

  renderTable(users);
}
/**
 * TODO: Implement the loadUsersAndInitialize function.
 */
async function loadUsersAndInitialize() {
 try {
    const res = await fetch("../api/index.php");
    const data = await res.json();

    if (data.success) {
      users = data.data;
      renderTable(users);
    }

  } catch (err) {
    console.error(err);
  }
}
passwordForm.addEventListener("submit", handleChangePassword);
addUserForm.addEventListener("submit", handleAddUser);

// --- Event Listeners Setup ---
// These are outside the function to ensure they only attach once.
userTableBody.addEventListener("click", handleTableClick);
searchInput.addEventListener("input", handleSearch);
tableHeaders.forEach(th => th.addEventListener("click", handleSort));


// Initial page load
loadUsersAndInitialize();

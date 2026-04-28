/*
  Requirement: Make the "Manage Assignments" page interactive.

  Instructions:
  1. This file is already linked to `admin.html` via:
         <script src="admin.js" defer></script>

  2. In `admin.html`:
     - The form has id="assignment-form".
     - The submit button has id="add-assignment".
     - The <tbody> has id="assignments-tbody".
     - Columns rendered per row:
       Title | Due Date | Description | Actions.

  3. Implement the TODOs below.

  API base URL: ./api/index.php
  All requests and responses use JSON.
  Successful list response shape: { success: true, data: [ ...assignment objects ] }
  Each assignment object shape:
    {
      id:          number,   // integer primary key from the assignments table
      title:       string,
      due_date:    string,   // "YYYY-MM-DD" — matches the SQL column name
      description: string,
      files:       string[]  // decoded array of URL strings
    }
*/

// --- Global Data Store ---
// Holds the assignments currently displayed in the table.
let assignments = [];

// --- Element Selections ---
// TODO: Select the assignment form by id 'assignment-form'.

// TODO: Select the assignments table body by id 'assignments-tbody'.

// --- Functions ---

/**
 * TODO: Implement createAssignmentRow.
 *
 * Parameters:
 *   assignment — one assignment object with shape:
 *     { id, title, due_date, description, files }
 *
 * Returns a <tr> element with four <td>s:
 *   1. title
 *   2. due_date   (the "YYYY-MM-DD" string — use due_date, not dueDate)
 *   3. description
 *   4. Actions — two buttons:
 *        <button class="edit-btn"   data-id="{id}">Edit</button>
 *        <button class="delete-btn" data-id="{id}">Delete</button>
 *      The data-id holds the integer primary key from the assignments table.
 */
function createAssignmentRow(assignment) {
  const tr = document.createElement('tr');

  const tdTitle = document.createElement('td');
  tdTitle.textContent = assignment.title;
  tr.appendChild(tdTitle);

  const tdDueDate = document.createElement('td');
  tdDueDate.textContent = assignment.due_date;
  tr.appendChild(tdDueDate);

  const tdDescription = document.createElement('td');
  tdDescription.textContent = assignment.description;
  tr.appendChild(tdDescription);

  const tdActions = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.className = 'edit-btn';
  editBtn.setAttribute('data-id', assignment.id);
  editBtn.textContent = 'Edit';
  tdActions.appendChild(editBtn);

  const deleteBtn = document.createElement('button');
  deleteBtn.className = 'delete-btn';
  deleteBtn.setAttribute('data-id', assignment.id);
  deleteBtn.textContent = 'Delete';
  tdActions.appendChild(deleteBtn);

  tr.appendChild(tdActions);
  return tr;
  
  // ... your implementation here ...
}

/**
 * TODO: Implement renderTable.
 *
 * It should:
 * 1. Clear the assignments table body (set innerHTML to "").
 * 2. Loop through the global `assignments` array.
 * 3. For each assignment, call createAssignmentRow(assignment) and
 *    append the <tr> to the table body.
 */
function renderTable() {
  const tbody = document.getElementById('assignments-body');
  tbody.innerHTML ="";

  assignments.forEach(assignment =>{
    const row = createAssignmentRow(assignment);
    tbody.appendChild(row);
  });
  // ... your implementation here ...
}

/**
 * TODO: Implement handleAddAssignment (async).
 *
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Call event.preventDefault().
 * 2. Read values from:
 *      - #assignment-title       → title (string)
 *      - #assignment-due-date    → due_date (string, "YYYY-MM-DD")
 *      - #assignment-description → description (string)
 *      - #assignment-files       → split by newlines (\n) and filter
 *                                  empty strings to produce a files array.
 * 3. Check if the submit button (#add-assignment) has a data-edit-id
 *    attribute.
 *    - If it does, call handleUpdateAssignment() with that id and the
 *      field values.
 *    - If it does not, send a POST to './api/index.php' with the body:
 *        { title, due_date, description, files }
 *      On success (result.success === true):
 *        - Add the new assignment (with the id from result.id) to the
 *          global `assignments` array.
 *        - Call renderTable().
 *        - Reset the form.
 */
async function handleAddAssignment(event) {
  event.preventDefult();
  const title = document.getElementById('assignment-title').value;
  const due-date = document.getElementById('assignment-due-date').value;
  const description = document.getElementById('assignment-description').value; 
  const filesRaw = document.getElementById('assignment-files').value; 

  const files = filesRaw.split('\n').filter(line=> line.trim() !== "";
  const editId = submitBtn.getAttribute('data-edit-id');

  if(editId){
    await handleUpdateAssignment(editId);
  } else{
    const response = await fetch('./api/index.php',{
      method: 'POST',
      body: JSON.stringify({ title, due-date, description, files}),
      headers: { 'Content-Type': 'application/json'}
    });
const result = await response.json();
if(result.succes === true){
  const newAssignment ={ id: result.id, title, due_date, description, files };
  assignment.push(newAssignment);

  renderTable();
  event.target.reset();
  }
  // ... your implementation here ...
}

/**
 * TODO: Implement handleUpdateAssignment (async).
 *
 * Parameters:
 *   id     — the integer primary key of the assignment being edited.
 *   fields — object with { title, due_date, description, files }.
 *
 * It should:
 * 1. Send a PUT to './api/index.php' with the body:
 *      { id, title, due_date, description, files }
 * 2. On success:
 *    - Update the matching entry in the global `assignments` array.
 *    - Call renderTable().
 *    - Reset the form.
 *    - Restore the submit button text to "Add Assignment" and remove
 *      its data-edit-id attribute.
 */
async function handleUpdateAssignment(id, fields) {
  const response =await fetch('./api/index.php',{
    method:'PUT';
    body: JSON.stringify({ id, ...fields }),
      headers: {'Content-Type': 'application/json'}
});
const result =await response.json();
if(result.success ===true){
  const index = assignments.findIndex(a => a.id == id);
  if(index !== -1){
    assignments[index] = { id, ...fields };
  }
renderTable();
const form = document.getElementById('assignment-form');
form.reset();

const submitBtn = document.getElementById('add-ssignment');
submitBtn.textContent ="Add Assignment";
submitBtn.removeAttribute('data-edit-id');  
    
} 
  // ... your implementation here ...
}

/**
 * TODO: Implement handleTableClick (async).
 *
 * This is a delegated click listener on the assignments table body.
 * It should:
 * 1. If event.target has class "delete-btn":
 *    a. Read the integer id from event.target.dataset.id.
 *    b. Send a DELETE to './api/index.php?id=<id>'.
 *    c. On success, remove the assignment from the global `assignments`
 *       array and call renderTable().
 *
 * 2. If event.target has class "edit-btn":
 *    a. Read the integer id from event.target.dataset.id.
 *    b. Find the matching assignment in the global `assignments` array.
 *    c. Populate the form fields:
 *         #assignment-title       ← assignment.title
 *         #assignment-due-date    ← assignment.due_date
 *         #assignment-description ← assignment.description
 *         #assignment-files       ← assignment.files joined with newlines (\n)
 *    d. Change the submit button (#add-assignment) text to
 *       "Update Assignment" and set its data-edit-id attribute to the
 *       assignment's id.
 */
async function handleTableClick(event) {
  if (event.target.classList.contains('delete-btn')){
    const id = event.target.dataset.id;

    const response =await fetch(`./api/index.php?id=${id}`,{
      method: 'Delete'
    });

    const result = await respone.json();
    if (result.success=== true){
      assignments = assignments.filter(a => a.id != id);
      renderTable();
    }
    if(event.target.classList.contains('edit-btn')){
      const id =event.target.dataset.id;
      const assignment = assignments.find(a => a.id == id);

      if(assignment){
        document.getElementById('assignment-title').value =assignment.title;
        document.getElementById('assignment-due-date').value =assignment.due-date;
        document.getElementById('assignment-description').value =assignment.description;
        document.getElementById('assignment-files').value =assignment.files.join('\n');
        
        const submitBtn = document.getElementById('add-assignment');
        submitBtn.textContent = "Update Assignment";
        submitBtn.setAttribute('data-edit-id', id);
      }
    }
       
    // ... your implementation here ...
}

/**
 * TODO: Implement loadAndInitialize (async).
 *
 * It should:
 * 1. Send a GET to './api/index.php'.
 *    Response shape: { success: true, data: [ ...assignment objects ] }
 * 2. Store the data array in the global `assignments` variable.
 * 3. Call renderTable() to populate the table.
 * 4. Attach the 'submit' event listener to the assignment form
 *    (calls handleAddAssignment).
 * 5. Attach a 'click' event listener to the assignments table body
 *    (calls handleTableClick — event delegation for edit and delete).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
  const response = await fetch('./api/index.php');
  const result = await response.json();

  if(result.success === true){
    assignments =result.data;
    renderTable();

    const form = document.getElementById('assignment-form');
    form.addEventListener('submit' , handleAddAssignment);

    const tbody = document.getElementById('assignment-tbody');
    tbody.addEventListener('click', handleTableClick);
  }
}
  

// --- Initial Page Load ---
loadAndInitialize();

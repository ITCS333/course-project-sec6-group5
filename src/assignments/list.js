/*
  Requirement: Populate the "Course Assignments" list page.

  Instructions:
  1. This file is already linked to `list.html` via:
         <script src="list.js" defer></script>

  2. In `list.html`, the <section id="assignment-list-section"> is the
     container that this script populates.

  3. Implement the TODOs below.

  API base URL: ./api/index.php
  Successful list response shape: { success: true, data: [ ...assignment objects ] }
  Each assignment object shape:
    {
      id:          number,   // integer primary key from the assignments table
      title:       string,
      due_date:    string,   // "YYYY-MM-DD" — matches the SQL column name
      description: string,
      files:       string[]  // already decoded array of URL strings
    }
*/

// --- Element Selections ---
// TODO: Select the section for the assignment list using its
//       id 'assignment-list-section'.

// --- Functions ---

/**
 * TODO: Implement createAssignmentArticle.
 *
 * Parameters:
 *   assignment — one object from the API response with the shape:
 *     {
 *       id:          number,
 *       title:       string,
 *       due_date:    string,   // "YYYY-MM-DD" — use due_date, not dueDate
 *       description: string,
 *       files:       string[]
 *     }
 *
 * Returns:
 *   An <article> element matching the structure shown in list.html:
 *     <article>
 *       <h2>{title}</h2>
 *       <p>Due: {due_date}</p>
 *       <p>{description}</p>
 *       <a href="details.html?id={id}">View Details &amp; Discussion</a>
 *     </article>
 *
 * Important: the href MUST be "details.html?id=<id>" (integer id from
 * the assignments table) so that details.js can read the id from the URL.
 */
function createAssignmentArticle(assignment) {
  // ... your implementation here ...
  // 1. Select the section where the assignments will be displayed
const assignmentListSection = document.getElementById('assignment-list-section');

/**
 * 2. Define the function to create the assignment article
 */
function createAssignmentArticle(assignment) {
  // Create the root <article> element
  const article = document.createElement('article');

  // Create the heading for the title
  const titleHeader = document.createElement('h2');
  titleHeader.textContent = assignment.title;

  // Create the paragraph for the due date
  const dueDatePara = document.createElement('p');
  dueDatePara.textContent = `Due: ${assignment.due_date}`;

  // Create the paragraph for the description
  const descriptionPara = document.createElement('p');
  descriptionPara.textContent = assignment.description;

  // Create the link to the details page
  // We use backticks to inject the assignment.id into the URL string
  const detailsLink = document.createElement('a');
  detailsLink.href = `details.html?id=${assignment.id}`;
  detailsLink.textContent = 'View Details & Discussion';

  // Append (add) all the new elements into the article
  article.appendChild(titleHeader);
  article.appendChild(dueDatePara);
  article.appendChild(descriptionPara);
  article.appendChild(detailsLink);

  // Return the finished article so it can be added to the page
  return article;
}
}

/**
 * TODO: Implement loadAssignments (async).
 *
 * It should:
 * 1. Use fetch() to GET data from './api/index.php'.
 *    The API returns JSON in the shape:
 *      { success: true, data: [ ...assignment objects ] }
 * 2. Parse the JSON response.
 * 3. Clear any existing content from the list section.
 * 4. Loop through the data array. For each assignment object:
 *    - Call createAssignmentArticle(assignment).
 *    - Append the returned <article> to the list section.
 */
async function loadAssignments() {
  // ... your implementation here ...
  /**
 * Implementation of loadAssignments (async).
 */
async function loadAssignments() {
  try {
    // 1. Use fetch() to GET data from the API
    const response = await fetch('./api/index.php');
    
    // 2. Parse the JSON response
    const result = await response.json();

    // Check if the API call was successful and we have data
    if (result.success && Array.isArray(result.data)) {
      
      // 3. Clear any existing content from the list section
      // This prevents assignments from duplicating if the function is called twice
      assignmentListSection.innerHTML = '';

      // 4. Loop through the data array
      result.data.forEach(assignment => {
        // Call your previously created function
        const article = createAssignmentArticle(assignment);
        
        // Append the returned <article> to the list section
        assignmentListSection.appendChild(article);
      });
    } else {
      console.error('Failed to load assignments:', result);
    }
  } catch (error) {
    // It's good practice to catch network errors
    console.error('Error fetching assignments:', error);
  }
}

// Call the function to start the process
loadAssignments();
}

// --- Initial Page Load ---


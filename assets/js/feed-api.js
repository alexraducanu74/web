document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".delete-btn").forEach(btn => {
    btn.addEventListener("click", async (event) => {
      event.preventDefault();
      const bookId = btn.dataset.id;
      if (!bookId) {
        alert("Book ID not found!");
        return;
      }
      await deleteBook(bookId);
    });
  });
});
async function updateProgress(bookId, pagesRead, review, rating) {
const response = await fetch(`index.php?api=1&controller=feed&actiune=updateProgressApi&parametrii=${bookId}`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        pages_read: pagesRead,
        review: review,
        rating: rating
    })
});
  const data = await response.json();

  if (data.success) {
      alert("Progress updated successfully.");
  } else {
      alert("Error: " + (data.error || "Unknown error"));
  }
}

async function deleteBook(bookId) {
  try {
    // Fixed URL structure - removed /api/ prefix since index.php handles API routing
    const response = await fetch(`index.php?api=1&controller=feed&actiune=deleteBookApi&parametrii=${bookId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (data.success) {
        location.reload();
    } else {
        alert("Error: " + (data.error || "Unknown error"));
    }
  } catch (error) {
    console.error('Delete error:', error);
    alert("Error deleting book: " + error.message);
  }
}
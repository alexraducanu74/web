document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", async (event) => {
      event.preventDefault();
      const bookId = btn.dataset.id;
      if (!bookId) {
        alert("Book ID not found!");
        return;
      }
      if (confirm("Are you sure you want to delete this book?")) {
        await deleteBook(bookId);
      }
    });
  });

  const form = document.getElementById("updateBookForm");
  if (form) {
    form.addEventListener("submit", async function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const token = sessionStorage.getItem('jwtToken');

      if (!token) {
        alert("Authentication error. Please log in again.");
        return;
      }

      try {
        const response = await fetch(this.action, {
          method: "POST",
          headers: {
            'Authorization': `Bearer ${token}`
          },
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          window.location.href =
            "index.php?controller=feed&actiune=viewBook&parametri=" +
            data.bookId;
        } else {
          alert("Update failed: " + (data.error || "Unknown error"));
        }
      } catch (error) {
        alert("Network error");
      }
    });
  }

  const insertForm = document.getElementById("insertBookForm");
  if (insertForm) {
    insertForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const token = sessionStorage.getItem('jwtToken');

      if (!token) {
        alert("Authentication error. Please log in again.");
        return;
      }

      const response = await fetch("index.php?api=1&actiune=insertBookApi", {
        method: "POST",
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: formData,
      });

      const result = await response.json();

      if (response.ok) {
        location.reload();
      } else {
        alert(result.error || "Failed to add book.");
      }
    });
  }
});

async function deleteBook(bookId) {
  try {
    const token = sessionStorage.getItem('jwtToken');
    if (!token) {
      alert("Authentication error. Please log in again.");
      return;
    }

    const response = await fetch(
      `index.php?api=1&controller=feed&actiune=deleteBookApi&parametri=${bookId}`,
      {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({}),
      }
    );

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ error: 'Unknown server error' }));
      throw new Error(`HTTP error! status: ${response.status}, message: ${errorData.error}`);
    }

    const data = await response.json();

    if (data.success) {
      location.reload();
    } else {
      alert("Error: " + (data.error || "Unknown error"));
    }
  } catch (error) {
    console.error("Delete error:", error);
    alert("Error deleting book: " + error.message);
  }
}
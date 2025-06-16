document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", async (event) => {
      event.preventDefault()
      const bookId = btn.dataset.id
      if (!bookId) {
        alert("Book ID not found!")
        return
      }
      await deleteBook(bookId)
    })
  })

  const form = document.getElementById("updateBookForm")
  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault()

      const formData = new FormData(this)

      fetch(this.action, {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            window.location.href =
              "index.php?controller=feed&actiune=showBook&parametri=" +
              data.bookId
          } else {
            alert("Update failed.")
          }
        })
        .catch(() => alert("Network error"))
    })
  }

  const insertForm = document.getElementById("insertBookForm")
  if (insertForm) {
    insertForm.addEventListener("submit", async function (e) {
      e.preventDefault()

      const formData = new FormData(this)

      const response = await fetch("index.php?api=1&actiune=insertBookApi", {
        method: "POST",
        body: formData,
      })

      const result = await response.json()

      if (response.ok) {
        location.reload()
      } else {
        alert(result.error || "Failed to add book.")
      }
    })
  }
})

async function deleteBook(bookId) {
  try {
    const response = await fetch(
      `index.php?api=1&controller=feed&actiune=deleteBookApi&parametri=${bookId}`,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({}),
      }
    )

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()

    if (data.success) {
      location.reload()
    } else {
      alert("Error: " + (data.error || "Unknown error"))
    }
  } catch (error) {
    console.error("Delete error:", error)
    alert("Error deleting book: " + error.message)
  }
}

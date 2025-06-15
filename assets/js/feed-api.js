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
})
async function deleteBook(bookId) {
  try {
    const response = await fetch(
      `index.php?api=1&controller=feed&actiune=deleteBookApi&parametrii=${bookId}`,
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
document
  .getElementById("insertBookForm")
  .addEventListener("submit", async function (e) {
    e.preventDefault()

    const form = e.target
    const formData = new FormData(form)

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

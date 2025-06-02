document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", function (event) {
      event.preventDefault()

      const bookId = this.dataset.id
      const deleteUrl = this.dataset.url
      const bookElement = this.closest(".book")

      fetch(deleteUrl, { method: "POST" })
        .then((response) => {
          if (response.ok) return response.text()
          throw new Error("Delete failed")
        })
        .then(() => {
          if (bookElement) bookElement.remove()
        })
        .catch((err) => {
          alert("Failed to delete the book.")
          console.error(err)
        })
    })
  })
})

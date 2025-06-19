<form 
  id="updateBookForm"
  action="index.php?controller=apiFeed&actiune=updateBookApi&parametri={$id}&api=1" 
  method="POST" 
  enctype="multipart/form-data"
  class="edit-book-form"
>
    <label>Title:<input type="text" name="title" value="{$title}" required></label>
    <label>Author:<input type="text" name="author" value="{$author}" required></label>
    <label>Genre:<input type="text" name="genre" value="{$genre}"></label>
    <label>Current Cover Image:
        <img src="assets/{$cover_image}" alt="Current Cover" style="max-width: 200px;">
        Change Cover Image: <input type="file" name="cover_image" accept="image/*">
    </label>
    <button type="submit">Save Changes</button>
</form>

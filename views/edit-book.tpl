<form 
  id="updateBookForm"
  action="index.php?controller=apiFeed&actiune=updateBookApi&parametrii={$id}&api=1" 
  method="POST" 
  enctype="multipart/form-data"
>
    <label>Title:<input type="text" name="title" value="{$title}" required></label><br>
    <label>Author:<input type="text" name="author" value="{$author}" required></label><br>
    <label>Genre:<input type="text" name="genre" value="{$genre}"></label><br>
    <label>Current Cover Image:<br>
        <img src="assets/{$cover_image}" alt="Current Cover" style="max-width: 200px;"><br>
        Change Cover Image: <input type="file" name="cover_image" accept="image/*">
    </label><br>
    <button type="submit">Save Changes</button>
</form>

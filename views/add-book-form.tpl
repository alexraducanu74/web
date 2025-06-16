<div class="form-styled group-form-container">
    <h2>Add Book to "{$group_name}"</h2>
    
    <form action="index.php" method="get" class="search-form" style="margin-bottom: 20px;">
        <input type="hidden" name="controller" value="group">
        <input type="hidden" name="actiune" value="showAddBookForm">
        <input type="hidden" name="parametri" value="{$group_id}">
        <input type="text" name="search" placeholder="Search for books by title or author..." value="{$search_term}">
        <button type="submit">Search</button>
    </form>
    
    {$search_results_html}
</div>
<?php
// Database connection
include "database.php";

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_watch'])) {
    
    // Get form values
    $film_id = mysqli_real_escape_string($conn, $_POST['film_id']);
    $watch_date = !empty($_POST['watch_date']) ? mysqli_real_escape_string($conn, $_POST['watch_date']) : NULL;
    $is_watched = isset($_POST['is_watched']) ? 1 : 0;
    $personal_rating = !empty($_POST['personal_rating']) ? mysqli_real_escape_string($conn, $_POST['personal_rating']) : NULL;
    $notes = !empty($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : NULL;
    
    // Prepare the SQL statement
    if ($watch_date === NULL) {
        $sql = "INSERT INTO watchstatus (film_id, is_watched, personal_rating, notes) 
                VALUES ('$film_id', '$is_watched', '$personal_rating', '$notes')";
    } else {
        $sql = "INSERT INTO watchstatus (film_id, watch_date, is_watched, personal_rating, notes) 
                VALUES ('$film_id', '$watch_date', '$is_watched', '$personal_rating', '$notes')";
    }
    
    // Execute the query
    if (mysqli_query($conn, $sql)) {
        // Set success message
        $_SESSION['message'] = "Film added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        // Set error message
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['message_type'] = "danger";
    }
    
    // Redirect back to the watch status page
    header("Location: watch-status.php");
    exit();
}

// Close the connection
mysqli_close($conn);
?>

<!-- Add New Watch Form -->
<div class="modal fade" id="addWatchModal" tabindex="-1" role="dialog" aria-labelledby="addWatchModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addWatchModalLabel">Add New Watch</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" action="">
        <div class="modal-body">
          <div class="form-group">
            <label for="film_id">Select Film:</label>
            <select class="form-control" id="film_id" name="film_id" required>
              <option value="">Select a film</option>
              <?php
              // Query to get films from your films table
              $film_query = "SELECT id, title FROM films ORDER BY title";
              $film_result = mysqli_query($conn, $film_query);
              
              while ($film = mysqli_fetch_assoc($film_result)) {
                echo "<option value='" . $film['id'] . "'>" . $film['title'] . "</option>";
              }
              ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="watch_date">Watch Date:</label>
            <input type="date" class="form-control" id="watch_date" name="watch_date">
          </div>
          
          <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="is_watched" name="is_watched" value="1" checked>
            <label class="form-check-label" for="is_watched">Watched</label>
          </div>
          
          <div class="form-group">
            <label for="personal_rating">Your Rating (0-10):</label>
            <input type="number" class="form-control" id="personal_rating" name="personal_rating" min="0" max="10" step="0.1">
          </div>
          
          <div class="form-group">
            <label for="notes">Notes:</label>
            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" name="add_watch" class="btn btn-primary">Add Watch</button>
        </div>
      </form>
    </div>
  </div>
</div>
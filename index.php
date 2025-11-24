<!-- FILE 1: index.php - Main Registration Page -->
<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'event_registration_db';

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$name = '';
$email = '';
$phone = '';
$event_id = '';

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $event_id = $_POST['event_id'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($event_id)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO registrations (name, email, phone, event_id, registration_date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssi", $name, $email, $phone, $event_id);
        
        if ($stmt->execute()) {
            $success = "Registration successful! Thank you for signing up.";
            // Clear form
            $name = '';
            $email = '';
            $phone = '';
            $event_id = '';
        } else {
            $error = "Error registering. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch available events
$events_result = $conn->query("SELECT id, name, date, location FROM events ORDER BY date ASC");
$events = $events_result->fetch_all(MYSQLI_ASSOC);

// Fetch all registrations for display
$registrations_result = $conn->query("
    SELECT r.id, r.name, r.email, r.phone, e.name as event_name, r.registration_date 
    FROM registrations r 
    JOIN events e ON r.event_id = e.id 
    ORDER BY r.registration_date DESC
");
$registrations = $registrations_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>🎯 Event Registration System</h1>
        <p class="subtitle">Register for upcoming events</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="form-section">
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="event_id">Select Event:</label>
                    <select id="event_id" name="event_id" required>
                        <option value="">-- Choose an event --</option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?php echo $event['id']; ?>" <?php echo ($event_id == $event['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($event['name']); ?> - <?php echo htmlspecialchars($event['date']); ?> (<?php echo htmlspecialchars($event['location']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit">Register</button>
            </form>
        </div>
        
        <div class="registrations-section">
            <h2>Recent Registrations</h2>
            <?php if (!empty($registrations)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Event</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reg['name']); ?></td>
                                <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                                <td><?php echo htmlspecialchars($reg['event_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($reg['registration_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No registrations yet. Be the first to register!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
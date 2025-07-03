<?php
$conn = new mysqli("localhost", "root", "", "brightsmile_db");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$search = $_GET['search'] ?? '';
$date = $_GET['date'] ?? '';

$patients = $conn->query("SELECT * FROM patients ORDER BY registered_at DESC");

// Search query
$appt_sql = "
  SELECT a.*, p.full_name, p.email 
  FROM appointments a
  JOIN patients p ON a.patient_id = p.id
  WHERE 
    (p.full_name LIKE '%$search%' OR
     p.email LIKE '%$search%' OR
     a.service LIKE '%$search%')
";
if (!empty($date)) {
  $appt_sql .= " AND a.appointment_date = '$date'";
}
$appt_sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
$appointments = $conn->query($appt_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - BrightSmile</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      background: #f8f9fc;
    }
    header {
      background: linear-gradient(to right, #1e3a8a, #3b82f6);
      color: white;
      padding: 30px;
      text-align: center;
    }
    nav {
      display: flex;
      justify-content: center;
      background: #2563eb;
      padding: 12px;
      gap: 14px;
    }
    nav a {
      color: white;
      text-decoration: none;
      font-weight: bold;
      padding: 8px 12px;
      border-radius: 6px;
    }
    nav a.active, nav a:hover {
      background: #1e40af;
    }
    .container {
      max-width: 1100px;
      margin: 40px auto;
      padding: 0 20px;
    }
    h2 {
      color: #1e3a8a;
      margin-top: 30px;
    }
    form.filters {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 20px;
    }
    form input[type="text"],
    form input[type="date"] {
      padding: 10px;
      font-size: 1rem;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #eee;
      text-align: left;
    }
    th {
      background: #f0f4ff;
      color: #1e3a8a;
    }
    tr:hover {
      background: #f9fbff;
    }
    .actions a {
      text-decoration: none;
      margin-right: 10px;
      font-weight: bold;
    }
    .edit { color: #2563eb; }
    .delete { color: red; }
    .empty {
      text-align: center;
      padding: 20px;
      font-style: italic;
      color: #888;
    }
    @media (max-width: 768px) {
      table, thead, tbody, th, td, tr {
        display: block;
      }
      th {
        display: none;
      }
      td {
        border: none;
        padding: 10px 0;
        position: relative;
      }
      td::before {
        content: attr(data-label);
        font-weight: bold;
        display: block;
        color: #1e3a8a;
      }
    }
  </style>
</head>
<body>

<header>
  <h1>BrightSmile Dashboard</h1>
  <p>Staff Overview of Patients & Appointments</p>
</header>

<nav>
  <a href="index.html">Home</a>
  <a href="services.html">Services</a>
  <a href="about.html">About</a>
  <a href="contact.html">Contact</a>
  <a href="register.html">Patient Registration</a>
  <a href="appointment.php">Book Appointment</a>
  <a href="dashboard.php" class="active">Dashboard</a>
</nav>

<div class="container">
  <h2>📋 Registered Patients</h2>
  <table>
    <thead>
      <tr>
        <th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($patients->num_rows > 0): while ($p = $patients->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($p['full_name']) ?></td>
          <td><?= htmlspecialchars($p['email']) ?></td>
          <td><?= htmlspecialchars($p['phone']) ?></td>
          <td><?= htmlspecialchars($p['address']) ?></td>
          <td><?= date("M d, Y", strtotime($p['registered_at'])) ?></td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="5" class="empty">No registered patients.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h2>📅 Appointments</h2>

  <form method="GET" class="filters">
    <input type="text" name="search" placeholder="Search patient/service..." value="<?= htmlspecialchars($search) ?>" />
    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" />
    <button type="submit">Filter</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>Patient</th><th>Email</th><th>Date</th><th>Time</th><th>Service</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($appointments->num_rows > 0): while ($a = $appointments->fetch_assoc()): ?>
        <tr>
          <td data-label="Patient"><?= htmlspecialchars($a['full_name']) ?></td>
          <td data-label="Email"><?= htmlspecialchars($a['email']) ?></td>
          <td data-label="Date"><?= $a['appointment_date'] ?></td>
          <td data-label="Time"><?= $a['appointment_time'] ?></td>
          <td data-label="Service"><?= $a['service'] ?></td>
          <td class="actions">
            <a href="appointment.php?edit=<?= $a['id'] ?>" class="edit">Edit</a>
            <a href="appointment.php?delete=<?= $a['id'] ?>" class="delete" onclick="return confirm('Are you sure to delete this appointment?')">Delete</a>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="6" class="empty">No appointments found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>

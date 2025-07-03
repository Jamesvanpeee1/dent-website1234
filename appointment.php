<?php
$conn = new mysqli("localhost", "root", "", "brightsmile_db");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$message = "";
$edit_mode = false;
$appointment_data = [
  'id' => '',
  'patient_id' => '',
  'service' => '',
  'appointment_date' => '',
  'appointment_time' => ''
];

// ✅ Delete request
if (isset($_GET['delete'])) {
  $delete_id = intval($_GET['delete']);
  $conn->query("DELETE FROM appointments WHERE id = $delete_id");
  header("Location: dashboard.php");
  exit;
}

// ✅ Edit mode
if (isset($_GET['edit'])) {
  $edit_mode = true;
  $edit_id = intval($_GET['edit']);
  $res = $conn->query("SELECT * FROM appointments WHERE id = $edit_id");
  if ($res && $row = $res->fetch_assoc()) {
    $appointment_data = $row;
  } else {
    $message = "Appointment not found.";
  }
}

// ✅ Handle booking form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $patient_id = $_POST['patient_id'];
  $service = $_POST['service'];
  $date = $_POST['appointment_date'];
  $time = $_POST['appointment_time'];
  $update_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

  $check = $conn->prepare("SELECT * FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND id != ?");
  $check->bind_param("ssi", $date, $time, $update_id);
  $check->execute();
  $exists = $check->get_result()->num_rows;

  if ($exists) {
    $message = "<span style='color:red;'>Time slot already booked.</span>";
  } else {
    if ($update_id > 0) {
      $stmt = $conn->prepare("UPDATE appointments SET patient_id=?, service=?, appointment_date=?, appointment_time=? WHERE id=?");
      $stmt->bind_param("isssi", $patient_id, $service, $date, $time, $update_id);
    } else {
      $stmt = $conn->prepare("INSERT INTO appointments (patient_id, service, appointment_date, appointment_time) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("isss", $patient_id, $service, $date, $time);
    }

    if ($stmt->execute()) {
      header("Location: dashboard.php");
      exit;
    } else {
      $message = "<span style='color:red;'>Error saving appointment.</span>";
    }
  }
}

// ✅ Get patients
$patients = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name ASC");

// ✅ Suggested time slots
$suggested_times = [];
if (!empty($_GET['date'])) {
  $date = $_GET['date'];
  $all_times = ["09:00", "10:00", "11:00", "12:00", "14:00", "15:00"];
  $booked = [];
  $res = $conn->prepare("SELECT appointment_time FROM appointments WHERE appointment_date = ?");
  $res->bind_param("s", $date);
  $res->execute();
  $result = $res->get_result();
  while ($r = $result->fetch_assoc()) {
    $booked[] = $r['appointment_time'];
  }
  $suggested_times = array_diff($all_times, $booked);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $edit_mode ? 'Edit' : 'Book' ?> Appointment</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f0f4ff;
      margin: 0;
      color: #333;
    }

    header {
      background: linear-gradient(to right, #1e3a8a, #3b82f6);
      color: white;
      padding: 40px 20px;
      text-align: center;
    }

    nav {
      display: flex;
      justify-content: center;
      background-color: #2563eb;
      padding: 12px;
      gap: 12px;
      flex-wrap: wrap;
    }

    nav a {
      color: white;
      text-decoration: none;
      padding: 8px 14px;
      border-radius: 6px;
      font-weight: bold;
    }

    nav a.active, nav a:hover {
      background-color: #1e40af;
    }

    .container {
      max-width: 600px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    h2 {
      color: #1e3a8a;
      text-align: center;
      margin-bottom: 20px;
    }

    form {
      display: flex;
      flex-direction: column;
    }

    input, select {
      padding: 12px;
      margin-bottom: 20px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }

    button {
      padding: 12px;
      background-color: #1e3a8a;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    button:hover {
      background-color: #2d4dd1;
    }

    .suggestions {
      font-size: 0.95rem;
      color: #1e40af;
      margin-top: -15px;
      margin-bottom: 20px;
    }

    .msg {
      text-align: center;
      color: red;
      margin-bottom: 10px;
    }

    @media (max-width: 600px) {
      .container { margin: 20px; padding: 20px; }
    }
  </style>
</head>
<body>

<header>
  <h1>BrightSmile Dental Clinic</h1>
  <p>Your smile, our commitment</p>
</header>

<nav>
  <a href="index.html">Home</a>
  <a href="services.html">Services</a>
  <a href="about.html">About</a>
  <a href="contact.html">Contact</a>
  <a href="dashboard.php">Dashboard</a>
  <a href="appointment.php" class="active"><?= $edit_mode ? 'Edit' : 'Book' ?> Appointment</a>
</nav>

<div class="container">
  <h2><?= $edit_mode ? 'Edit' : 'Book an' ?> Appointment</h2>

  <?php if ($message): ?><div class="msg"><?= $message ?></div><?php endif; ?>

  <form method="POST">
    <?php if ($edit_mode): ?>
      <input type="hidden" name="id" value="<?= $appointment_data['id'] ?>" />
    <?php endif; ?>

    <label>Patient:</label>
    <select name="patient_id" required>
      <option value="">-- Select Patient --</option>
      <?php while ($row = $patients->fetch_assoc()): ?>
        <option value="<?= $row['id'] ?>" <?= ($row['id'] == $appointment_data['patient_id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($row['full_name']) ?>
        </option>
      <?php endwhile; ?>
    </select>

    <label>Service:</label>
    <select name="service" required>
      <?php
        $services = ["Teeth Cleaning", "Dental Checkup", "Dental Implants", "Emergency Care"];
        foreach ($services as $s):
      ?>
        <option value="<?= $s ?>" <?= ($s == $appointment_data['service']) ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>

    <label>Date:</label>
    <input type="date" name="appointment_date" value="<?= $appointment_data['appointment_date'] ?>" required onchange="this.form.submit()" />

    <label>Time:</label>
    <input type="time" name="appointment_time" value="<?= $appointment_data['appointment_time'] ?>" required />

    <?php if (!empty($suggested_times)): ?>
      <div class="suggestions">
        Suggested Times: <?= implode(", ", $suggested_times) ?>
      </div>
    <?php endif; ?>

    <button type="submit"><?= $edit_mode ? 'Update' : 'Book' ?> Appointment</button>
  </form>
</div>

</body>
</html>

<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['loggedin'])) {
    header("Location: index.html");
    exit;
}

$pesan = "";
if (isset($_SESSION['pesan'])) {
    $pesan = $_SESSION['pesan'];
    unset($_SESSION['pesan']); // Hapus setelah ditampilkan
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Tugas</title>
  <link rel="stylesheet" href="style_dashboard.css">
</head>
<body>
  <nav class="navbar">
    <div class="logo">📚 TugasKu </div>
    <ul class="nav-menu">
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="Tugassaya.html">Tugas Saya</a></li>
      <li><a href="matkul.html">Mata Kuliah</a></li>
      <li><a href="index.html">Logout</a></li>
    </ul>
  </nav>

  <div class="container">
    <aside class="sidebar">
      <center><h1>Semester 2 </h1> </center>
      <h3>Prodi: Data Science</h3>
      <h3>Tahun Ajaran 2025/2026</h3>

  <form method="get" action="dashboard.php">
  <label>Mata Kuliah:</label>
  <select name="filter_mk">
    <option value="">Semua</option>
    <?php
      require 'koneksi.php';
      $mk_query = mysqli_query($conn, "SELECT id_mk, Nama_mk FROM mata_kuliah");
      while ($mk = mysqli_fetch_assoc($mk_query)) {
        $selected = ($_GET['filter_mk'] ?? '') == $mk['id_mk'] ? 'selected' : '';
        echo "<option value='{$mk['id_mk']}' $selected>{$mk['Nama_mk']}</option>";
      }
    ?>
  </select>

  <label>Status Tugas:</label>
  <select name="filter_status">
    <option value="">Semua</option>
    <option value="belum" <?= ($_GET['filter_status'] ?? '') == 'belum' ? 'selected' : '' ?>>Belum Selesai</option>
    <option value="proses" <?= ($_GET['filter_status'] ?? '') == 'proses' ? 'selected' : '' ?>>Sedang Dikerjakan</option>
    <option value="selesai" <?= ($_GET['filter_status'] ?? '') == 'selesai' ? 'selected' : '' ?>>Selesai</option>
  </select>
<br>
  <br>
<button type="submit">Filter</button> 
</form>

<canvas id="statusChart" width="200" height="200"></canvas>
    </aside>

    <main class="main-content">
      <?php if (!empty($pesan)): ?>
        <div class="alert-success"><?= $pesan ?></div>
      <?php endif; ?>

      <h2 id="welcome-message">Selamat Datang, <?= htmlspecialchars($_SESSION['nama']) ?></h2>
      <a href="tambah-tugas.php" class="btn-tambah">+Tambah Tugas</a>

      <!-- Daftar tugas dari database -->
      <?php
      require 'koneksi.php';
      $id_user = $_SESSION['user_id'];

      // Query ambil semua tugas pengguna + nama mata kuliah
$id_user = $_SESSION['user_id'];
$filter_mk = $_GET['filter_mk'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

$sql = "SELECT t.id_tugas, t.judul, m.Nama_mk, t.deadline, t.Status 
        FROM tugas t
        JOIN mata_kuliah m ON t.id_mk = m.id_mk
        WHERE t.id_user = ?";

if ($filter_mk) {
    $sql .= " AND t.id_mk = " . intval($filter_mk);
}
if ($filter_status) {
    $sql .= " AND t.Status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}

$sql .= " ORDER BY t.deadline ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php
$queryChart = "SELECT Status, COUNT(*) as jumlah FROM tugas WHERE id_user = ? GROUP BY Status";
$stmt = $conn->prepare($queryChart);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$resultChart = $stmt->get_result();

$status_labels = [];
$status_values = [];

while ($row = $resultChart->fetch_assoc()) {
    $status_labels[] = $row['Status'];
    $status_values[] = $row['jumlah'];
}
      ?>

      <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()):
              // Hitung sisa hari
              $deadline = new DateTime($row['deadline']);
              $today = new DateTime();
              $interval = $today->diff($deadline);
              $sisa_hari = $interval->format('%r%a');
          ?>

      <div class="card">
          <h4><?= htmlspecialchars($row['judul']) ?></h4>
          <p>Mata Kuliah: <?= htmlspecialchars($row['Nama_mk']) ?></p>

          <!-- Deadline dengan info sisa hari -->
          <p>
            Deadline: <?= date('d F Y', strtotime($row['deadline'])) ?>
            <?php if ($sisa_hari <= 0 && $row['Status'] == 'belum'): ?>
              <span class="sisa-hari">(Lewat)</span>
            <?php elseif ($row['Status'] == 'belum'): ?>
              <span class="sisa-hari">(H-<?= $sisa_hari ?>)</span>
            <?php endif; ?>
          </p>

         <!-- Status Badge -->
          <span class="badge <?= 
              strtolower($row['Status']) == 'belum' ? 'belum' : 
              (strtolower($row['Status']) == 'proses' ? 'proses' : 'selesai') 
          ?>">
            <?= 
              strtolower($row['Status']) == 'belum' ? 'belum' : 
              (strtolower($row['Status']) == 'proses' ? 'Proses' : 'Selesai') 
            ?>
          </span>
      </div>
      <?php endwhile; ?>
      <?php else: ?>
      <div class="card">
          <p>Belum ada tugas.</p>
      </div>
      <?php endif; ?>
    </main>
  </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('statusChart').getContext('2d');
  const statusChart = new Chart(ctx, {
      type: 'pie',
      data: {
          labels: <?= json_encode($status_labels) ?>,
          datasets: [{
              data: <?= json_encode($status_values) ?>,
              backgroundColor: ['#f6c23e', '#36b9cc', '#1cc88a'],
          }]
      },
      options: {
          responsive: true,
          plugins: {
              legend: { position: 'bottom' },
              title: { display: true, text: 'DISTRIBUSI STATUS TUGAS' }
          }
      }
  });
</script>

</body>
</html>
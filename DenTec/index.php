<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - Dental Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container-fluid">
  <div class="row bg-primary text-white p-3">
    <div class="col-md-6">
      <h2>Dental Admin Dashboard</h2>
    </div>
    <div class="col-md-6 text-end align-self-center">
      <a href="logout.php" class="btn btn-light">Logout</a>
    </div>
  </div>

  <div class="row my-4">
    <!-- Cards for stats -->
    <div class="col-md-3">
      <div class="card text-white bg-info mb-3">
        <div class="card-body">
          <h5 class="card-title">Total Patients</h5>
          <p class="card-text fs-3" id="totalPatients">0</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-white bg-success mb-3">
        <div class="card-body">
          <h5 class="card-title">New Patients (This Month)</h5>
          <p class="card-text fs-3" id="newPatients">0</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-white bg-warning mb-3">
        <div class="card-body">
          <h5 class="card-title">Upcoming Appointments</h5>
          <p class="card-text fs-3" id="upcomingAppointments">0</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-white bg-danger mb-3">
        <div class="card-body">
          <h5 class="card-title">Total Revenue</h5>
          <p class="card-text fs-3" id="totalRevenue">₹0</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts Row -->
  <div class="row">
    <div class="col-md-6">
      <canvas id="patientGrowthChart"></canvas>
    </div>
    <div class="col-md-6">
      <canvas id="treatmentPopularityChart"></canvas>
    </div>
  </div>

</div>

<script>
  async function loadDashboard() {
    const response = await fetch('dashboard_data.php');
    const data = await response.json();

    document.getElementById('totalPatients').textContent = data.totalPatients;
    document.getElementById('newPatients').textContent = data.newPatients;
    document.getElementById('upcomingAppointments').textContent = data.upcomingAppointments;
    document.getElementById('totalRevenue').textContent = '₹' + data.totalRevenue.toFixed(2);

    const ctx1 = document.getElementById('patientGrowthChart').getContext('2d');
    new Chart(ctx1, {
      type: 'line',
      data: {
        labels: data.patientGrowth.labels,
        datasets: [{
          label: 'New Patients',
          data: data.patientGrowth.data,
          borderColor: 'rgba(54, 162, 235, 1)',
          backgroundColor: 'rgba(54, 162, 235, 0.2)',
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: true } }
      }
    });

    const ctx2 = document.getElementById('treatmentPopularityChart').getContext('2d');
    new Chart(ctx2, {
      type: 'bar',
      data: {
        labels: data.treatmentPopularity.labels,
        datasets: [{
          label: 'Number of Treatments',
          data: data.treatmentPopularity.data,
          backgroundColor: 'rgba(255, 99, 132, 0.6)'
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  loadDashboard();
</script>

</body>
</html>

<?php
require_once '../../includes/db.php';
requireUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports - <?php echo SITE_NAME; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

  <!-- Header -->
  <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Reports</h1>
    <div class="flex gap-4">
      <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container mx-auto px-6 py-10 max-w-4xl">
    <h2 class="text-2xl font-semibold text-gray-700 mb-8 text-center">Select Report Type</h2>

    <!-- Grid of Report Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
      
      <!-- Daily Report -->
      <a href="daily.php" class="group bg-white rounded-2xl shadow-md hover:shadow-lg border border-gray-200 p-6 transition transform hover:-translate-y-1">
        <div class="flex items-center space-x-4">
          <div class="bg-orange-100 p-3 rounded-full">
            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-orange-600">Daily Report</h3>
            <p class="text-gray-500 text-sm">View today’s egg production and sales data.</p>
          </div>
        </div>
      </a>

      <!-- Weekly Report -->
      <a href="weekly.php" class="group bg-white rounded-2xl shadow-md hover:shadow-lg border border-gray-200 p-6 transition transform hover:-translate-y-1">
        <div class="flex items-center space-x-4">
          <div class="bg-green-100 p-3 rounded-full">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-green-600">Weekly Summary</h3>
            <p class="text-gray-500 text-sm">Review summarized data for the past week.</p>
          </div>
        </div>
      </a>

      <!-- Monthly Report -->
      <a href="monthly.php" class="group bg-white rounded-2xl shadow-md hover:shadow-lg border border-gray-200 p-6 transition transform hover:-translate-y-1">
        <div class="flex items-center space-x-4">
          <div class="bg-blue-100 p-3 rounded-full">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-blue-600">Monthly Report</h3>
            <p class="text-gray-500 text-sm">Analyze trends and performance by month.</p>
          </div>
        </div>
      </a>

      <!-- Export Reports -->
      <a href="export.php" class="group bg-white rounded-2xl shadow-md hover:shadow-lg border border-gray-200 p-6 transition transform hover:-translate-y-1">
        <div class="flex items-center space-x-4">
          <div class="bg-purple-100 p-3 rounded-full">
            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-purple-600">Export Reports</h3>
            <p class="text-gray-500 text-sm">Export your reports to PDF or Excel formats.</p>
          </div>
        </div>
      </a>

    </div>
  </div>

</body>
</html>

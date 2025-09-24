<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .status-healthy { @apply bg-green-100 text-green-800; }
        .status-warning { @apply bg-yellow-100 text-yellow-800; }
        .status-critical { @apply bg-red-100 text-red-800; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Supabase Migration Dashboard</h1>
                <p class="mt-2 text-gray-600">Real-time monitoring of authentication migration progress</p>
            </div>

            <!-- Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Migration Progress</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900" id="migration-percentage">0%</dd>
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" id="progress-bar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Authentication Mode</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900" id="auth-mode">Loading...</dd>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">System Status</dt>
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-healthy" id="system-status">
                                Healthy
                            </span>
                        </dd>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Last Updated</dt>
                        <dd class="mt-1 text-lg font-medium text-gray-900" id="last-updated">Never</dd>
                    </div>
                </div>
            </div>

            <!-- Detailed Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Migration Details -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Migration Details</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Total Users</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="total-users">-</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Migrated Users</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="migrated-users">-</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Pending Migration</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="pending-users">-</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Recent Activity (24h)</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="recent-activity">-</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Errors (24h)</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="errors-24h">-</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Est. Completion</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="est-completion">-</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Authentication Metrics -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Authentication Metrics (24h)</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Total Logins</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="total-logins">-</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Failed Logins</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="failed-logins">-</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">OAuth Logins</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="oauth-logins">-</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Token Refreshes</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="token-refreshes">-</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Active Sessions</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="active-sessions">-</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Recent Failures</dt>
                                <dd class="mt-1 text-sm text-gray-900" id="recent-failures">-</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Activity Chart -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Migration Progress Over Time</h3>
                </div>
                <div class="p-6">
                    <canvas id="migrationChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Recent Activity Log -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activity</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="space-y-3" id="activity-log">
                        <div class="text-sm text-gray-500">Loading activity...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart configuration
        const ctx = document.getElementById('migrationChart').getContext('2d');
        const migrationChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Migration Progress (%)',
                    data: [],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        // Update dashboard
        async function updateDashboard() {
            try {
                // Fetch migration data
                const migrationResponse = await fetch('/health/migration');
                const migrationData = await migrationResponse.json();
                
                // Update migration stats
                document.getElementById('total-users').textContent = migrationData.total;
                document.getElementById('migrated-users').textContent = migrationData.migrated;
                document.getElementById('pending-users').textContent = migrationData.pending;
                document.getElementById('recent-activity').textContent = migrationData.recent_activity || 0;
                document.getElementById('errors-24h').textContent = migrationData.errors_last_24h || 0;
                document.getElementById('est-completion').textContent = migrationData.progress?.estimated_completion || 'Unknown';
                
                // Update progress
                const percentage = migrationData.progress?.percentage || 0;
                document.getElementById('migration-percentage').textContent = percentage.toFixed(1) + '%';
                document.getElementById('progress-bar').style.width = percentage + '%';
                
                // Update chart
                const now = new Date().toLocaleTimeString();
                migrationChart.data.labels.push(now);
                migrationChart.data.datasets[0].data.push(percentage);
                
                // Keep only last 20 data points
                if (migrationChart.data.labels.length > 20) {
                    migrationChart.data.labels.shift();
                    migrationChart.data.datasets[0].data.shift();
                }
                
                migrationChart.update();
                
                // Fetch authentication data
                const authResponse = await fetch('/health/authentication');
                const authData = await authResponse.json();
                
                // Update auth stats
                document.getElementById('auth-mode').textContent = authData.auth_mode;
                document.getElementById('total-logins').textContent = authData.metrics?.logins_last_24h || 0;
                document.getElementById('failed-logins').textContent = authData.metrics?.failed_logins_last_24h || 0;
                document.getElementById('oauth-logins').textContent = authData.metrics?.oauth_logins_last_24h || 0;
                document.getElementById('token-refreshes').textContent = authData.metrics?.token_refreshes_last_24h || 0;
                document.getElementById('active-sessions').textContent = authData.metrics?.active_sessions || 0;
                document.getElementById('recent-failures').textContent = authData.metrics?.recent_failures_24h || 0;
                
                // Update last updated time
                document.getElementById('last-updated').textContent = new Date().toLocaleString();
                
                // Add to activity log
                const activityLog = document.getElementById('activity-log');
                const logEntry = document.createElement('div');
                logEntry.className = 'text-sm';
                logEntry.innerHTML = `
                    <span class="text-gray-500">${new Date().toLocaleTimeString()}</span>
                    <span class="text-gray-900">Migration progress: ${percentage.toFixed(1)}%</span>
                `;
                
                activityLog.insertBefore(logEntry, activityLog.firstChild);
                
                // Keep only last 10 entries
                while (activityLog.children.length > 10) {
                    activityLog.removeChild(activityLog.lastChild);
                }
                
            } catch (error) {
                console.error('Error updating dashboard:', error);
                document.getElementById('system-status').textContent = 'Error';
                document.getElementById('system-status').className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-critical';
            }
        }

        // Initial update
        updateDashboard();
        
        // Update every 10 seconds
        setInterval(updateDashboard, 10000);
    </script>
</body>
</html>
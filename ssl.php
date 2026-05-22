<?php
/**
 * SSL Certificate Generator
 * 
 * Generates a self-signed SSL certificate for recurlog.isoftro.com.
 * Useful for development/testing HTTPS on shared hosting.
 * 
 * For production: use Let's Encrypt (Certbot) or your hosting control panel.
 */

$domain = 'recurlog.isoftro.com';

// Check if openssl extension is available
if (!extension_loaded('openssl')) {
  die('Error: PHP OpenSSL extension is not installed on this server.');
}

$action = $_GET['action'] ?? 'form';

if ($action === 'generate') {
  generateCertificate($domain);
  exit;
}

// Default: show info page
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SSL — Recurlog</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { brand: '#1DB954', navy: '#0B1E3D' }, fontFamily: { sans: ['Poppins', 'sans-serif'] } } } }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Poppins', sans-serif; background: #0B1E3D; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .card { animation: fadeIn 0.6s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>
  <div class="card w-full max-w-2xl px-4">
    <div class="text-center mb-6">
      <div class="w-14 h-14 bg-brand rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg shadow-brand/25">
        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2a6 6 0 0 0-6 6v2H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V11a1 1 0 0 0-1-1h-1V8a6 6 0 0 0-6-6zm-4 8V8a4 4 0 0 1 8 0v2H8zm4 5a1.5 1.5 0 0 1 .5 2.9v1.1a.5.5 0 0 1-1 0v-1.1A1.5 1.5 0 0 1 12 15z"/></svg>
      </div>
      <h1 class="text-2xl font-bold text-white">SSL Certificate</h1>
      <p class="text-white/60 text-sm mt-1"><?= htmlspecialchars($domain) ?></p>
    </div>

    <div class="bg-white rounded-2xl shadow-xl p-6 space-y-4">
      <!-- Self-signed generator -->
      <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
        <h2 class="font-semibold text-navy mb-1">1. Self-Signed Certificate</h2>
        <p class="text-sm text-gray-500 mb-3">For development/testing HTTPS on your shared hosting.</p>
        <a href="?action=generate" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#1DB954;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;text-decoration:none;font-size:14px">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          Generate Self-Signed Certificate
        </a>
      </div>

      <!-- Let's Encrypt instructions -->
      <div class="p-4 bg-blue-50 rounded-xl border border-blue-200">
        <h2 class="font-semibold text-blue-800 mb-1">2. Production SSL (Recommended)</h2>
        <p class="text-sm text-blue-700 mb-2">Use your hosting control panel or Let's Encrypt:</p>
        <div class="bg-white rounded-lg p-3 text-sm font-mono text-gray-700 border border-blue-100 space-y-1">
          <?php if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN'): ?>
          <p># Via SSH (if you have shell access):</p>
          <p class="text-green-700">curl https://get.acme.sh | sh</p>
          <p class="text-green-700">~/.acme.sh/acme.sh --issue -d <?= $domain ?> --webroot /path/to/webroot</p>
          <p class="text-gray-400 mt-2"># Or using Certbot:</p>
          <p class="text-green-700">certbot certonly --webroot -w /path/to/webroot -d <?= $domain ?></p>
          <?php else: ?>
          <p>Use your hosting cPanel &rarr; SSL/TLS &rarr; Let's Encrypt</p>
          <p>Or AutoSSL if your host provides it.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- cPanel guide -->
      <div class="p-4 bg-purple-50 rounded-xl border border-purple-200">
        <h2 class="font-semibold text-purple-800 mb-1">3. cPanel / Shared Hosting</h2>
        <p class="text-sm text-purple-700">Most shared hosts offer free AutoSSL (cPanel &rarr; SSL/TLS &rarr; AutoSSL). Enable it and your subdomain will get a certificate automatically.</p>
      </div>

      <p class="text-xs text-gray-400 text-center pt-2"><?= htmlspecialchars($domain) ?> &mdash; Recurlog</p>
    </div>
  </div>
</body>
</html>

<?php

/**
 * Generate a self-signed SSL certificate.
 */
function generateCertificate($domain) {
  $days = 365;
  $outputDir = __DIR__ . '/ssl';
  if (!is_dir($outputDir)) {
    mkdir($outputDir, 0700, true);
  }

  $keyFile = $outputDir . '/private.key';
  $certFile = $outputDir . '/certificate.crt';

  // Generate private key
  $key = openssl_pkey_new([
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
  ]);

  if (!$key) {
    showError('Failed to generate private key.');
    return;
  }

  // Certificate details
  $dn = [
    'countryName'            => 'NP',
    'stateOrProvinceName'    => 'Province 2',
    'localityName'           => 'Birgunj',
    'organizationName'       => 'iSoftro Solutions',
    'commonName'             => $domain,
    'emailAddress'           => 'admin@isoftro.com',
  ];

  // Subject Alternative Names
  $san = "DNS:$domain";

  // Generate CSR
  $csr = openssl_csr_new($dn, $key, ['digest_alg' => 'sha256']);
  if (!$csr) {
    showError('Failed to generate CSR.');
    return;
  }

  // Generate self-signed cert
  $config = [
    'x509_extensions' => 'v3_req',
    'req_extensions'   => 'v3_req',
  ];
  $crt = openssl_csr_sign($csr, null, $key, $days, $config, time());
  if (!$crt) {
    showError('Failed to sign certificate.');
    return;
  }

  // Export key
  openssl_pkey_export($key, $keyPem);

  // Export cert
  openssl_x509_export($crt, $crtPem);

  // Write files
  file_put_contents($keyFile, $keyPem);
  file_put_contents($certFile, $crtPem);

  // Show success
  showSuccess($domain, $keyFile, $certFile, $crtPem, $keyPem);
}

function showSuccess($domain, $keyFile, $certFile, $certPem, $keyPem) {
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSL Generated — Recurlog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { brand: '#1DB954', navy: '#0B1E3D' }, fontFamily: { sans: ['Poppins', 'sans-serif'] } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
      body { font-family: 'Poppins', sans-serif; background: #0B1E3D; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
      .card { animation: fadeIn 0.6s ease-out; max-width: 700px; }
      @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
      pre { white-space: pre-wrap; word-break: break-all; font-size: 11px; max-height: 200px; overflow-y: auto; }
    </style>
  </head>
  <body>
    <div class="card w-full px-4">
      <div class="text-center mb-6">
        <div class="w-14 h-14 bg-green-500 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg shadow-green-500/25">
          <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-white">Certificate Generated</h1>
        <p class="text-white/60 text-sm mt-1">Self-signed certificate for <?= htmlspecialchars($domain) ?></p>
      </div>

      <div class="bg-white rounded-2xl shadow-xl p-6 space-y-4">
        <div class="p-3 bg-green-50 rounded-lg border border-green-200 text-sm text-green-800">
          Files saved to: <code class="font-mono font-bold">backend/ssl/</code>
        </div>

        <div>
          <h2 class="font-semibold text-navy text-sm mb-2">Private Key</h2>
          <pre class="bg-gray-900 text-green-300 rounded-lg p-3 text-xs font-mono overflow-x-auto"><?= htmlspecialchars(file_get_contents($keyFile)) ?></pre>
        </div>

        <div>
          <h2 class="font-semibold text-navy text-sm mb-2">Certificate</h2>
          <pre class="bg-gray-900 text-green-300 rounded-lg p-3 text-xs font-mono overflow-x-auto"><?= htmlspecialchars(file_get_contents($certFile)) ?></pre>
        </div>

        <div class="p-3 bg-blue-50 rounded-lg border border-blue-200 text-sm text-blue-800">
          <strong>Note:</strong> Browsers will show a warning for self-signed certificates.
          For production, use your hosting control panel's AutoSSL or Let's Encrypt.
        </div>

        <div class="flex gap-2">
          <a href="?" class="btn btn-ghost" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:transparent;color:#6B7280;border:1px solid #E5E7EB;border-radius:8px;font-weight:600;cursor:pointer;text-decoration:none;font-size:14px">&larr; Back</a>
          <a href="?action=generate" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#1DB954;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;text-decoration:none;font-size:14px">Regenerate</a>
        </div>
      </div>
    </div>
  </body>
  </html>
  <?php
}

function showError($msg) {
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSL Error — Recurlog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { brand: '#1DB954', navy: '#0B1E3D' }, fontFamily: { sans: ['Poppins', 'sans-serif'] } } } }
    </script>
  </head>
  <body class="bg-navy min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full text-center">
      <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
      </div>
      <h2 class="font-semibold text-navy mb-1">Error</h2>
      <p class="text-sm text-gray-500"><?= htmlspecialchars($msg) ?></p>
      <a href="?" class="btn btn-ghost mt-4" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:transparent;color:#6B7280;border:1px solid #E5E7EB;border-radius:8px;font-weight:600;cursor:pointer;text-decoration:none;font-size:14px">&larr; Back</a>
    </div>
  </body>
  </html>
  <?php
}

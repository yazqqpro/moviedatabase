<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi REST API - DBMovieLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .docs-section { margin-bottom: 2.5rem; }
        .docs-subsection { margin-bottom: 1.5rem; margin-top: 1.5rem; }
        .code-block {
            background-color: #2d3748; /* bg-gray-800 */
            color: #e2e8f0; /* text-gray-200 */
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.875rem; /* text-sm */
            margin-top: 0.5rem;
            margin-bottom: 1rem;
        }
        .code-block pre { margin: 0; }
        .badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.375rem;
        }
        .badge-sky { background-color: #0ea5e9; color: white; } /* sky-500 */
        .badge-emerald { background-color: #10b981; color: white; } /* emerald-500 */
        .badge-rose { background-color: #f43f5e; color: white; } /* rose-500 */
        .param-table th, .param-table td {
            padding: 0.75rem;
            border: 1px solid #e2e8f0; /* slate-200 */
            text-align: left;
        }
        .tab-container { margin-bottom: 1rem; }
        .tab-buttons button {
            padding: 0.5rem 1rem;
            margin-right: 0.5rem;
            border: 1px solid transparent;
            border-bottom: none;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
            background-color: #f1f5f9; /* slate-100 */
            cursor: pointer;
            font-weight: 500;
        }
        .tab-buttons button.active {
            background-color: white;
            border-color: #e2e8f0; /* slate-200 */
            border-bottom: 1px solid white; /* Untuk menutupi border bawah dari .tab-content */
            position: relative;
            bottom: -1px; /* Menggeser tombol aktif sedikit ke bawah */
        }
        .tab-content {
            border: 1px solid #e2e8f0; /* slate-200 */
            padding: 1rem;
            border-radius: 0.375rem;
            border-top-left-radius: 0; /* Agar menyatu dengan tombol tab */
        }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen p-4 md:p-8">
    <div class="container mx-auto max-w-4xl bg-white p-6 md:p-10 rounded-xl shadow-2xl">
        <header class="mb-8 pb-4 border-b border-slate-200">
            <h1 class="text-4xl font-bold text-sky-600">Dokumentasi REST API DBMovieLink</h1>
            <p class="text-slate-600 mt-2">Panduan untuk menggunakan REST API DBMovieLink untuk mengakses data film.</p>
        </header>

        <section class="docs-section">
            <h2 class="text-2xl font-semibold text-slate-700 mb-3">Informasi Umum</h2>
            <p>API ini memungkinkan Anda untuk mengambil daftar film dari berbagai server yang tersedia. Semua respons data dikembalikan dalam format JSON.</p>
            <p class="mt-2">Kunci API diperlukan untuk semua permintaan.</p>
        </section>

        <section class="docs-section">
            <h2 class="text-2xl font-semibold text-slate-700 mb-3">Kunci API (API Key)</h2>
            <p>Untuk menggunakan API ini, Anda harus menyertakan kunci API dalam setiap permintaan. Kunci API yang valid adalah:</p>
            <div class="code-block"><pre>andriasfilm</pre></div>
            <p>Sertakan kunci ini sebagai parameter query <code class="bg-slate-200 px-1 rounded">apikey</code>.(Sering Update tanpa pemberitauan, jadi sering kunjungi api docs ini)</p>
        </section>

        <section class="docs-section">
            <h2 class="text-2xl font-semibold text-slate-700 mb-3">Endpoint API</h2>
            <p>Berikut adalah endpoint utama untuk API:</p>
            <div class="code-block"><pre>GET /api.php</pre></div>
            <p>Gantilah <code class="bg-slate-200 px-1 rounded">/api.php</code> dengan path absolut ke file <code class="bg-slate-200 px-1 rounded">api.php</code> di server Anda jika diperlukan (misalnya, <code class="bg-slate-200 px-1 rounded">https://yaz.my.id/path/to/api.php</code>).</p>
        </section>

        <section class="docs-section">
            <h2 class="text-2xl font-semibold text-slate-700 mb-3">Parameter Permintaan</h2>
            <p>Endpoint API menerima parameter GET berikut:</p>
            <table class="w-full param-table mt-4">
                <thead class="bg-slate-50">
                    <tr>
                        <th>Parameter</th>
                        <th>Tipe</th>
                        <th>Wajib?</th>
                        <th>Deskripsi</th>
                        <th>Contoh Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code class="font-mono">apikey</code></td>
                        <td>String</td>
                        <td>Ya</td>
                        <td>Kunci API Anda.</td>
                        <td><code class="font-mono">andriasfilm</code></td>
                    </tr>
                    <tr>
                        <td><code class="font-mono">server</code></td>
                        <td>String</td>
                        <td>Ya</td>
                        <td>Kunci server target untuk mengambil data.</td>
                        <td>
                            <span class="badge badge-sky">server1</span>,
                            <span class="badge badge-emerald">server2</span>,
                            <span class="badge badge-rose">adult18</span>
                        </td>
                    </tr>
                    <tr>
                        <td><code class="font-mono">search_name</code></td>
                        <td>String</td>
                        <td>Tidak</td>
                        <td>Kata kunci untuk mencari film berdasarkan nama.</td>
                        <td><code class="font-mono">Mission Impossible</code></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="docs-section">
            <h2 class="text-2xl font-semibold text-slate-700 mb-3">Contoh Permintaan</h2>
            <p>Berikut adalah contoh URL permintaan untuk mengambil data dari <code class="bg-slate-200 px-1 rounded">server1</code>:</p>
            <div class="code-block"><pre>https://yaz.my.id/api.php?apikey=andriasfilm&server=server1</pre></div>
            <p>Contoh dengan pencarian nama:</p>
            <div class="code-block"><pre>https://yaz.my.id/api.php?apikey=andriasfilm&server=server2&search_name=Star Trek</pre></div>
            <p><strong>Catatan:</strong> Ganti <code class="bg-slate-200 px-1 rounded">https://yaz.my.id/</code> dengan domain aktual dan path ke <code class="bg-slate-200 px-1 rounded">api.php</code> Anda.</p>

            <div class="docs-subsection">
                <h3 class="text-xl font-medium text-slate-600 mb-2">Contoh Kode Pemanggilan API</h3>
                <div class="tab-container">
                    <div class="tab-buttons" role="tablist" aria-label="Contoh Kode API">
                        <button role="tab" aria-selected="true" aria-controls="tab-curl" id="btn-curl" class="active" onclick="showTab('curl')">cURL (CLI)</button>
                        <button role="tab" aria-selected="false" aria-controls="tab-javascript" id="btn-javascript" onclick="showTab('javascript')">JavaScript (Fetch)</button>
                        <button role="tab" aria-selected="false" aria-controls="tab-php" id="btn-php" onclick="showTab('php')">PHP (cURL)</button>
                    </div>
                    <div class="tab-content">
                        <div role="tabpanel" id="tab-curl" class="tab-pane active">
                            <h4 class="text-lg font-semibold mb-1">cURL (Command Line)</h4>
                            <p class="text-sm text-slate-500 mb-2">Jalankan perintah ini di terminal Anda.</p>
                            <p>Mengambil semua data dari server1:</p>
                            <div class="code-block"><pre>curl -X GET "https://yaz.my.id/api.php?apikey=andriasfilm&server=server1"</pre></div>
                            <p>Mencari "Action Movie" di server2:</p>
                            <div class="code-block"><pre>curl -X GET "https://yaz.my.id/api.php?apikey=andriasfilm&server=server2&search_name=Action%20Movie"</pre></div>
                        </div>
                        <div role="tabpanel" id="tab-javascript" class="tab-pane">
                            <h4 class="text-lg font-semibold mb-1">JavaScript (Fetch API)</h4>
                            <p class="text-sm text-slate-500 mb-2">Gunakan kode ini di lingkungan JavaScript (browser atau Node.js dengan `node-fetch`).</p>
                            <div class="code-block">
<pre><code class="language-javascript">
async function fetchDataFromServer(serverKey, searchQuery = null) {
    const apiKey = 'andriasfilm';
    let url = \`https://yaz.my.id/api.php?apikey=\${apiKey}&server=\${serverKey}\`;

    if (searchQuery) {
        url += \`&search_name=\${encodeURIComponent(searchQuery)}\`;
    }

    try {
        const response = await fetch(url);
        if (!response.ok) {
            // Mencoba membaca body error jika ada, jika tidak, gunakan statusText
            let errorData;
            try {
                errorData = await response.json();
            } catch (e) {
                // Jika body error bukan JSON atau kosong
                throw new Error(\`HTTP error! status: \${response.status} - \${response.statusText}\`);
            }
            throw new Error(\`API Error: \${errorData.message || response.statusText}\`);
        }
        const data = await response.json();
        console.log('Data diterima:', data);
        return data;
    } catch (error) {
        console.error('Gagal mengambil data:', error);
        // Kembalikan objek error standar jika diinginkan
        return { status: false, message: error.message, data: [] };
    }
}

// Contoh penggunaan:
fetchDataFromServer('server1')
    .then(result => {
        if (result.status) {
            // Proses result.data
            console.log(\`Menemukan \${result.item_count} item di server1.\`);
        }
    });

fetchDataFromServer('server2', 'Star Trek')
    .then(result => {
        if (result.status && result.data.length > 0) {
            console.log(\`Film pertama ditemukan: \${result.data[0].NAME}\`);
        } else if (result.status) {
            console.log(\`Tidak ada film 'Star Trek' ditemukan di server2.\`);
        }
    });
</code></pre>
                            </div>
                        </div>
                        <div role="tabpanel" id="tab-php" class="tab-pane">
                            <h4 class="text-lg font-semibold mb-1">PHP (cURL Extension)</h4>
                            <p class="text-sm text-slate-500 mb-2">Gunakan kode ini di skrip PHP Anda.</p>
                            <div class="code-block">
<pre><code class="language-php">
&lt;?php
function callDbMovieApi($serverKey, $searchQuery = null) {
    $apiKey = 'andriasfilm';
    $baseUrl = 'https://yaz.my.id/api.php'; // Sesuaikan dengan URL API Anda

    $queryParams = [
        'apikey' => $apiKey,
        'server' => $serverKey,
    ];

    if ($searchQuery !== null) {
        $queryParams['search_name'] = $searchQuery;
    }

    $url = $baseUrl . '?' . http_build_query($queryParams);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout dalam detik
    // Untuk HTTPS, jika ada masalah SSL (TIDAK DIREKOMENDASIKAN untuk produksi):
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $responseJson = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        // Error koneksi cURL
        return ['status' => false, 'message' => 'cURL Error: ' . $curlError, 'data' => []];
    }

    if ($httpCode !== 200) {
        // Error HTTP dari API
        $errorData = json_decode($responseJson, true);
        $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'HTTP Error ' . $httpCode;
        return ['status' => false, 'message' => $errorMessage, 'http_code' => $httpCode, 'raw_response' => $responseJson, 'data' => []];
    }
    
    $responseData = json_decode($responseJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Error parsing JSON
        return ['status' => false, 'message' => 'Gagal mem-parsing respons JSON: ' . json_last_error_msg(), 'raw_response' => $responseJson, 'data' => []];
    }

    return $responseData; // Seharusnya berisi ['status' => true, 'data' => [...], ...]
}

// Contoh penggunaan:
echo "&lt;pre&gt;";

$resultServer1 = callDbMovieApi('server1');
echo "Hasil dari server1:\n";
print_r($resultServer1);
echo "\n\n";

$resultSearch = callDbMovieApi('server2', 'Star Trek');
echo "Hasil pencarian 'Star Trek' di server2:\n";
print_r($resultSearch);

echo "&lt;/pre&gt;";
?&gt;
</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="docs-section">
            <h2 class="text-2xl font-semibold text-slate-700 mb-3">Format Respons</h2>
            
            <div class="docs-subsection">
                <h3 class="text-xl font-medium text-slate-600 mb-2">Respons Sukses (HTTP 200 OK)</h3>
                <p>Jika permintaan berhasil, API akan mengembalikan objek JSON dengan status <code class="bg-slate-200 px-1 rounded">true</code> dan array <code class="bg-slate-200 px-1 rounded">data</code> yang berisi daftar film. Setiap item film akan memiliki field <code class="bg-slate-200 px-1 rounded">ID</code>, <code class="bg-slate-200 px-1 rounded">NAME</code>, <code class="bg-slate-200 px-1 rounded">CODE</code> (jika ada), dan <code class="bg-slate-200 px-1 rounded">URL</code>.</p>
                <div class="code-block">
<pre>{
  "status": true,
  "message": "Data berhasil diambil.",
  "server": "server1",
  "search_query": null, // atau string pencarian jika digunakan
  "item_count": 2,
  "data": [
    {
      "ID": "1",
      "CODE": "XYZ123",
      "NAME": "Contoh Film Satu",
      "URL": "https://example.com/film/1"
    },
    {
      "ID": "2",
      "CODE": "ABC789",
      "NAME": "Contoh Film Dua",
      "URL": "https://example.com/film/2"
    }
    // ... item lainnya
  ]
}</pre>
                </div>
            </div>

            <div class="docs-subsection">
                <h3 class="text-xl font-medium text-slate-600 mb-2">Respons Gagal/Error</h3>
                <p>Jika terjadi kesalahan (misalnya, kunci API salah, server tidak valid, atau data tidak ditemukan), API akan mengembalikan objek JSON dengan status <code class="bg-slate-200 px-1 rounded">false</code> dan pesan error yang menjelaskan masalahnya. Kode status HTTP juga akan merefleksikan jenis error (misalnya, 400, 401, 404, 500).</p>
                <p>Contoh Error Kunci API Tidak Valid (HTTP 401 Unauthorized):</p>
                <div class="code-block">
<pre>{
  "status": false,
  "message": "Kunci API tidak valid atau tidak disertakan."
}</pre>
                </div>
                <p>Contoh Error Server Tidak Ditemukan (HTTP 404 Not Found):</p>
                <div class="code-block">
<pre>{
  "status": false,
  "message": "Server 'server_tidak_ada' tidak valid."
}</pre>
                </div>
                 <p>Contoh Error Saat Mengambil Data (HTTP 502 Bad Gateway atau sesuai error cURL):</p>
                <div class="code-block">
<pre>{
  "status": false,
  "message": "Gagal mengambil data dari server target: ...",
  "details": "URL Target: ..., HTTP Code: ..., Final URL: ..., Preview: ..." 
}</pre>
                </div>
            </div>
        </section>

        <footer class="mt-12 pt-6 border-t border-slate-200 text-center text-sm text-slate-500">
            <p>&copy; <?php echo date("Y"); ?> DBMovieLink API. Dibuat dengan ??.</p>
        </footer>
    </div>

    <script>
        function showTab(tabName) {
            // Sembunyikan semua tab-pane
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
                pane.setAttribute('aria-hidden', 'true');
            });
            // Hapus kelas active dari semua tombol tab
            document.querySelectorAll('.tab-buttons button').forEach(button => {
                button.classList.remove('active');
                button.setAttribute('aria-selected', 'false');
            });

            // Tampilkan tab-pane yang dipilih
            const selectedPane = document.getElementById('tab-' + tabName);
            if (selectedPane) {
                selectedPane.classList.add('active');
                selectedPane.setAttribute('aria-hidden', 'false');
            }
            // Tambahkan kelas active ke tombol tab yang dipilih
            const selectedButton = document.getElementById('btn-' + tabName);
            if (selectedButton) {
                selectedButton.classList.add('active');
                selectedButton.setAttribute('aria-selected', 'true');
            }
        }
        // Inisialisasi tab pertama sebagai aktif (jika diperlukan, meskipun sudah di-set di HTML)
        // showTab('curl'); // Atau tab default lainnya
    </script>
</body>
</html>

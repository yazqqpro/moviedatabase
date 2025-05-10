<?php
// Aktifkan display_errors untuk debugging. Hapus atau komentari ini di lingkungan produksi.
// Untuk API, lebih baik log error ke file daripada menampilkannya langsung, kecuali saat development.
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
// error_log("Pesan error di sini", 3, "/path/to/your/php-error.log"); // Contoh logging ke file

error_reporting(E_ALL);
session_start(); // Diperlukan untuk $cookieFile yang menggunakan session_id()

header('Content-Type: application/json; charset=utf-8');

// --- Konfigurasi API ---
$valid_api_key = 'andriasfilm'; // Kunci API yang valid

// --- Konfigurasi dari skrip utama (disesuaikan untuk API) ---
$baseDomain = 'https://dbmovielink.web.id'; // Sesuaikan jika domain API berbeda
$serverUrls = [
    'server1' => $baseDomain . '/server1/',
    'server2' => $baseDomain . '/server2/',
    'adult18' => $baseDomain . '/adult18/',
];
$cookieFileDir = dirname(__FILE__) . '/cookies/'; // Pastikan direktori ini ada dan writable
if (!is_dir($cookieFileDir)) {
    @mkdir($cookieFileDir, 0755, true);
}
// $cookieFile akan didefinisikan di dalam initCurl jika diperlukan, atau gunakan yang sudah ada.
// Untuk API yang idealnya stateless, manajemen cookie mungkin perlu disesuaikan.
// Saat ini, kita akan menggunakan cookie berbasis session_id dari skrip utama.
$cookieFile = $cookieFileDir . 'cookie_dbmovielink_' . session_id() . '.txt';


// --- Fungsi Bantuan JSON Response ---
function outputJsonResponse($data, $httpStatusCode = 200) {
    http_response_code($httpStatusCode);
    echo json_encode($data);
    exit;
}

function outputJsonError($message, $httpStatusCode = 400, $details = null) {
    $response = ['status' => false, 'message' => $message];
    if ($details !== null) {
        $response['details'] = $details;
    }
    outputJsonResponse($response, $httpStatusCode);
}

// --- Fungsi Inti (disalin dan disesuaikan dari skrip utama) ---
// Catatan: Fungsi performLogin tidak disertakan di sini karena API ini
// bergantung pada sesi yang mungkin sudah ada atau target URL tidak memerlukan login.
// Jika login diperlukan per panggilan API, fungsi performLogin harus ditambahkan dan dipanggil.

function initCurlApi() {
    global $cookieFile, $cookieFileDir; // Ambil variabel global
    
    // Pastikan direktori cookie dapat ditulis
    if (!is_writable($cookieFileDir)) {
        // Ini adalah error server, jadi jangan output langsung ke klien API dalam format HTML.
        // Log error ini di sisi server.
        error_log("API Error: Direktori cookies '{$cookieFileDir}' tidak dapat ditulis.");
        outputJsonError("Kesalahan konfigurasi server internal.", 500);
    }
    
    // $cookieFile sudah didefinisikan secara global berdasarkan session_id()
    // Jika file cookie tidak ada, cURL akan membuatnya (jika COOKIEJAR diset).

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36 DBMovieLinkAPI/1.0',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieFile,      // Menyimpan cookie untuk sesi ini
        CURLOPT_COOKIEFILE => $cookieFile,     // Membaca cookie dari sesi ini
        CURLOPT_SSL_VERIFYPEER => true,        // Dianjurkan untuk produksi
        CURLOPT_SSL_VERIFYHOST => 2,           // Dianjurkan untuk produksi
        CURLOPT_TIMEOUT => 45,                 // Timeout API bisa lebih pendek
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    return $ch;
}

function fetchDataApi($ch, $targetDataUrl, $referer) {
    curl_setopt($ch, CURLOPT_URL, $targetDataUrl);
    curl_setopt($ch, CURLOPT_POST, false); curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 
        'Accept-Language: en-US,en;q=0.9,id;q=0.8', 
        'Referer: ' . $referer
    ]);
    $targetPageHtml = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCodeTargetPage = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    if (curl_errno($ch)) { 
        return ['success' => false, 'message' => 'cURL Error: ' . $curlError, 'http_code' => $httpCodeTargetPage, 'final_url' => $finalUrl, 'html_preview' => substr($targetPageHtml,0,200)]; 
    }
    if ($httpCodeTargetPage !== 200) {
        $errorMessage = 'Gagal mengambil halaman data. HTTP Code: ' . $httpCodeTargetPage;
        if (stripos($finalUrl, 'login') !== false) {
            $errorMessage = 'Sesi berakhir atau akses ditolak, diarahkan ke halaman login oleh server target.';
        }
        return ['success' => false, 'message' => $errorMessage, 'http_code' => $httpCodeTargetPage, 'final_url' => $finalUrl, 'html_preview' => substr($targetPageHtml,0,200)];
    }
    return ['success' => true, 'html' => $targetPageHtml, 'final_url_fetch' => $finalUrl];
}

function parseDataFromHtmlApi($html, $baseDomain, $serverKey) {
    $extractedData = [];
    if (empty($html)) return ['data' => [], 'table_found' => false];
    
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $xpath = new DOMXPath($dom);
    $tableNode = $xpath->query("//table[@id='example']")->item(0);
    
    if (!$tableNode) {
        return ['data' => [], 'table_found' => false];
    }

    $rows = $xpath->query(".//tbody/tr", $tableNode);
    foreach ($rows as $row) {
        $rowData = ['ID' => null, 'CODE' => null, 'NAME' => null, 'URL' => null];
        $cells = $xpath->query(".//td", $row);

        if ($serverKey === 'server2') {
            if ($cells->length > 0) $rowData['ID'] = trim($cells->item(0)->nodeValue ?? '');
            if ($cells->length > 1) $rowData['NAME'] = trim($cells->item(1)->nodeValue ?? '');
            if ($cells->length > 2) $rowData['CODE'] = trim($cells->item(2)->nodeValue ?? '');
            if ($cells->length > 5) { 
                $actionCell = $cells->item(5);
                if ($actionCell) {
                    $buttonNode = $xpath->query(".//button[contains(@class, 'copy-btn') and normalize-space(text())='Copy Url']", $actionCell)->item(0);
                    if (!$buttonNode) {
                        $buttonNode = $xpath->query(".//button[contains(@class, 'copy-btn')]", $actionCell)->item(0);
                    }
                    if ($buttonNode) {
                        $rowData['URL'] = trim($buttonNode->getAttribute('data-copy'));
                        if (!empty($rowData['URL']) && !preg_match('/^https?:\/\//i', $rowData['URL'])) {
                            if (substr($rowData['URL'], 0, 1) !== '/') $rowData['URL'] = '/' . $rowData['URL'];
                            $rowData['URL'] = rtrim($baseDomain, '/') . $rowData['URL'];
                        }
                    }
                }
            }
        } else { // server1, adult18, dan default
            if ($cells->length > 0) $rowData['ID'] = trim($cells->item(0)->nodeValue ?? '');
            if ($cells->length > 1) $rowData['CODE'] = trim($cells->item(1)->nodeValue ?? ''); // Original: CODE di kolom 1
            if ($cells->length > 2) $rowData['NAME'] = trim($cells->item(2)->nodeValue ?? ''); // Original: NAME di kolom 2
            if ($cells->length > 4) { // Original: URL di kolom 4
                $actionCell = $cells->item(4);
                if ($actionCell) {
                    $buttonNode = $xpath->query(".//button[contains(@class, 'copy-btn') and normalize-space(text())='Copy Url']", $actionCell)->item(0);
                    if ($buttonNode) {
                        $rowData['URL'] = trim($buttonNode->getAttribute('data-copy'));
                        if (!empty($rowData['URL']) && !preg_match('/^https?:\/\//i', $rowData['URL'])) {
                            if (substr($rowData['URL'], 0, 1) !== '/') $rowData['URL'] = '/' . $rowData['URL'];
                            $rowData['URL'] = rtrim($baseDomain, '/') . $rowData['URL'];
                        }
                    }
                }
            }
        }
        // Hanya tambahkan jika ada nama, untuk menghindari baris kosong jika parsing gagal sebagian
        if (!empty($rowData['NAME'])) {
            $extractedData[] = $rowData;
        }
    }
    return ['data' => $extractedData, 'table_found' => true];
}


// --- Alur Utama API ---

// 1. Validasi Metode Request (opsional, tapi baik untuk REST)
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    outputJsonError("Metode request tidak valid. Hanya GET yang diizinkan.", 405);
}

// 2. Ambil dan Validasi Kunci API
$client_api_key = $_GET['apikey'] ?? null;
if (empty($client_api_key)) {
    outputJsonError("Kunci API tidak disertakan.", 401);
}
if ($client_api_key !== $valid_api_key) {
    outputJsonError("Kunci API tidak valid.", 401);
}

// 3. Ambil dan Validasi Parameter Server
$selectedServerKey = $_GET['server'] ?? null;
if (empty($selectedServerKey)) {
    outputJsonError("Parameter 'server' tidak disertakan.", 400);
}
if (!array_key_exists($selectedServerKey, $serverUrls)) {
    outputJsonError("Server '{$selectedServerKey}' tidak valid. Server yang tersedia: " . implode(', ', array_keys($serverUrls)) . ".", 404);
}

// 4. Ambil Parameter Pencarian (opsional)
$searchQuery = isset($_GET['search_name']) ? trim($_GET['search_name']) : null;

// 5. Inisialisasi cURL
$ch = initCurlApi();

// 6. Fetch Data
$targetDataUrl = $serverUrls[$selectedServerKey];
// Untuk API, referer bisa diatur ke domain API itu sendiri atau domain utama.
// Jika API ini dipanggil dari JavaScript di $baseDomain, maka $baseDomain adalah referer yang baik.
// Jika tidak, mungkin lebih baik tidak mengirim referer atau mengirim $baseDomain.
$refererForFetch = $baseDomain . '/'; // Referer sederhana
$fetchResult = fetchDataApi($ch, $targetDataUrl, $refererForFetch);

if (!$fetchResult['success']) {
    // Error saat fetching data dari server target
    $details = "URL Target: {$targetDataUrl}. HTTP Code: " . ($fetchResult['http_code'] ?? 'N/A') . ". Final URL: " . ($fetchResult['final_url'] ?? 'N/A') . ". Preview: " . ($fetchResult['html_preview'] ?? '');
    outputJsonError("Gagal mengambil data dari server target: " . $fetchResult['message'], 502, $details); // 502 Bad Gateway
}

// 7. Parse Data
$parsingResult = parseDataFromHtmlApi($fetchResult['html'], $baseDomain, $selectedServerKey);
$allExtractedData = $parsingResult['data'];
$tableWasFoundByParser = $parsingResult['table_found'];

if (!$tableWasFoundByParser && empty($allExtractedData)) {
    $details = "Kemungkinan struktur HTML di server target berubah atau tabel tidak ditemukan.";
    if (empty($fetchResult['html'])) {
        $details .= " Tidak ada HTML yang diterima dari server target.";
    } else if (strlen($fetchResult['html']) < 200) { // Jika HTML sangat pendek, mungkin halaman error
        $details .= " HTML yang diterima sangat pendek: " . htmlspecialchars(substr($fetchResult['html'],0,200));
    }
    outputJsonError("Tidak dapat menemukan tabel data di server target atau tabel kosong.", 404, $details);
}


// 8. Filter Data jika ada searchQuery
$filteredData = $allExtractedData;
if (!empty($searchQuery)) {
    $filteredData = array_filter($allExtractedData, function($item) use ($searchQuery) {
        return isset($item['NAME']) && stripos($item['NAME'], $searchQuery) !== false;
    });
    // Re-index array setelah filter untuk output JSON yang bersih
    $filteredData = array_values($filteredData); 
}

// 9. Tutup cURL
if ($ch && is_resource($ch)) {
    curl_close($ch);
}

// 10. Kirim Respons Sukses
$response = [
    'status' => true,
    'message' => 'Data berhasil diambil.',
    'server' => $selectedServerKey,
    'oleh' => yaz.my.id,
    'search_query' => $searchQuery,
    'item_count' => count($filteredData),
    'data' => $filteredData
];
outputJsonResponse($response, 200);

?>

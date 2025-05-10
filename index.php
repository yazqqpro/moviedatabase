<?php
// Aktifkan display_errors untuk debugging. Hapus atau komentari ini di lingkungan produksi.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Diperlukan untuk manajemen sesi antar halaman

// --- Konfigurasi ---
$baseDomain = 'https://dbmovielink.web.id';
$loginPageUrl = $baseDomain . '/login/';
$loginPostUrl = $baseDomain . '/login/'; // Form POST ke URL yang sama

// URL target akan ditentukan oleh pilihan pengguna
$serverUrls = [
    'server1' => $baseDomain . '/server1/',
    'server2' => $baseDomain . '/server2/',
    'adult18' => $baseDomain . '/adult18/',
];

$hardcoded_username = 'yazvip'; // Username sudah ditentukan
$hardcoded_password = 'jawatengah123'; // Password sudah ditentukan

$itemsPerPage = 15; // Jumlah item per halaman untuk paginasi

$cookieFileDir = dirname(__FILE__) . '/cookies/';
if (!is_dir($cookieFileDir)) {
    @mkdir($cookieFileDir, 0755, true);
}
if (!is_writable($cookieFileDir)) {
    die("Error: Direktori cookies '{$cookieFileDir}' tidak dapat ditulis. Harap periksa izin file server.");
}
$cookieFile = $cookieFileDir . 'cookie_dbmovielink_' . session_id() . '.txt';


// --- Fungsi Bantuan ---
function outputErrorPage($title, $message, $details = "") {
    global $cookieFile;
    if (!empty($cookieFile) && file_exists($cookieFile)) { @unlink($cookieFile); }
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    $htmlDetails = !empty($details) ? "<p class='text-sm text-slate-500 bg-slate-50 p-3 rounded-md break-all'>Detail: " . htmlspecialchars($details) . "</p>" : "";
    $logoutUrl = strtok($_SERVER["REQUEST_URI"],'?') . '?action=logout_and_retry'; // Memberi opsi untuk mencoba lagi

    echo <<<HTML
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Error - {$title}</title><script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>body { font-family: 'Inter', sans-serif; }</style></head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 md:p-12 rounded-xl shadow-2xl max-w-lg w-full text-center">
        <svg class="w-20 h-20 text-red-500 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <h1 class="text-3xl font-bold text-slate-800 mb-3">{$title}</h1>
        <p class="text-slate-600 mb-4">{$message}</p>
        {$htmlDetails}
        <a href="{$logoutUrl}" class="mt-8 inline-block bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">Coba Login Ulang</a>
    </div></body></html>
HTML;
    exit;
}

function initCurl() {
    global $cookieFile;
    if (empty($cookieFile) || !is_writable(dirname($cookieFile))) { // Periksa juga direktori cookieFile
        outputErrorPage("Kesalahan Konfigurasi Kritis", "Path file cookie tidak dapat ditentukan atau direktori tidak dapat ditulis: " . dirname($cookieFile));
    }
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile, // Ini memastikan cookie dibaca untuk permintaan berikutnya
        CURLOPT_SSL_VERIFYPEER => true, // Lebih aman untuk diaktifkan di produksi
        CURLOPT_SSL_VERIFYHOST => 2,   // Lebih aman untuk diaktifkan di produksi
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 20,
    ]);
    return $ch;
}

function performLogin($ch, $loginPageUrl, $loginPostUrl, $username, $password, $baseDomain) {
    global $cookieFile;
    if (file_exists($cookieFile)) { @unlink($cookieFile); } // Mulai dengan cookie bersih untuk login baru

    curl_setopt($ch, CURLOPT_URL, $loginPageUrl);
    curl_setopt($ch, CURLOPT_POST, false); curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: en-US,en;q=0.9,id;q=0.8']);
    $initialResponse = curl_exec($ch);
    if (curl_errno($ch)) { return ['success' => false, 'message' => 'cURL Error (GET Login Page): ' . curl_error($ch), 'response' => $initialResponse]; }
    $httpCodeInitial = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCodeInitial !== 200) { return ['success' => false, 'message' => 'Gagal mengambil halaman login. HTTP Code: ' . $httpCodeInitial, 'response' => $initialResponse]; }

    $postDataLogin = ['username' => $username, 'password' => $password, 'login' => 'Login'];
    curl_setopt($ch, CURLOPT_URL, $loginPostUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postDataLogin));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded', 'Origin: ' . $baseDomain, 'Referer: ' . $loginPageUrl, 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8']);
    $loginResponse = curl_exec($ch);
    if (curl_errno($ch)) { return ['success' => false, 'message' => 'cURL Error (POST Login): ' . curl_error($ch), 'response' => $loginResponse]; }
    
    $httpCodeLogin = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrlAfterLogin = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    $loginSuccessful = false;
    // Kondisi login berhasil yang lebih robust: tidak di halaman login DAN tidak ada pesan error spesifik
    if (($finalUrlAfterLogin !== $loginPageUrl && $finalUrlAfterLogin !== $loginPostUrl) &&
        (stripos($finalUrlAfterLogin, '/login/') === false) && // Tidak mengandung /login/ di URL akhir
        (stripos($loginResponse, 'username or password incorrect') === false) &&
        (stripos($loginResponse, 'kata sandi salah') === false)) {
        $loginSuccessful = true;
    // Alternatif: jika HTTP 200 dan tidak ada field password lagi (sudah login)
    } elseif ($httpCodeLogin === 200 && stripos($loginResponse, 'name="password"') === false && stripos($loginResponse, 'forgot password') === false) {
        $loginSuccessful = true;
    }

    if ($loginSuccessful) {
        $_SESSION['dbm_logged_in'] = true;
        $_SESSION['dbm_cookie_file_path'] = $cookieFile; 
        $_SESSION['dbm_final_url_after_login'] = $finalUrlAfterLogin; // Simpan URL setelah login untuk referer
        return ['success' => true, 'message' => 'Login berhasil.'];
    } else {
        $errorMessage = 'Login gagal. Periksa username/password atau respons server tidak terduga.';
        if (stripos($loginResponse, 'username or password incorrect') !== false) $errorMessage = 'Login gagal: Username atau password salah.';
        return ['success' => false, 'message' => $errorMessage, 'details' => 'URL Akhir: ' . ($finalUrlAfterLogin ?? 'N/A'), 'response' => $loginResponse];
    }
}

function fetchData($ch, $targetDataUrl, $referer) {
    global $cookieFile; 
    curl_setopt($ch, CURLOPT_URL, $targetDataUrl);
    curl_setopt($ch, CURLOPT_POST, false); curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: en-US,en;q=0.9,id;q=0.8', 'Referer: ' . $referer]);
    $targetPageHtml = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCodeTargetPage = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    if (curl_errno($ch)) { return ['success' => false, 'message' => 'cURL Error (GET Data Page): ' . $curlError, 'html_preview' => substr($targetPageHtml,0,500), 'final_url' => $finalUrl]; }
    if ($httpCodeTargetPage !== 200) {
         if (stripos($finalUrl, 'login') !== false) { // Jika diarahkan kembali ke halaman login
            return ['success' => false, 'message' => 'Sesi berakhir atau akses ditolak. Diarahkan ke login.', 'redirect_to_login' => true, 'html_preview' => substr($targetPageHtml,0,500), 'final_url' => $finalUrl];
        }
        return ['success' => false, 'message' => 'Gagal mengambil halaman data. HTTP Code: ' . $httpCodeTargetPage, 'html_preview' => substr($targetPageHtml,0,500), 'final_url' => $finalUrl];
    }
    return ['success' => true, 'html' => $targetPageHtml, 'final_url_fetch' => $finalUrl];
}

/**
 * Mem-parsing data dari HTML yang diberikan.
 *
 * @param string $html Konten HTML.
 * @param string $baseDomain Domain dasar untuk melengkapi URL relatif.
 * @param string $serverKey Kunci server (misalnya 'server1', 'server2') untuk logika parsing kondisional.
 * @return array Array berisi data yang diekstrak dan status penemuan tabel.
 */
function parseDataFromHtml($html, $baseDomain, $serverKey) {
    $extractedData = [];
    if (empty($html)) return ['data' => [], 'table_found' => false];
    
    $dom = new DOMDocument();
    // Menambahkan encoding XML dan menekan error parsing HTML bawaan PHP
    // libxml_use_internal_errors(true); // Alternatif untuk @
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    // libxml_clear_errors(); // Alternatif untuk @

    $xpath = new DOMXPath($dom);
    // Mencari tabel dengan ID 'example'
    $tableNode = $xpath->query("//table[@id='example']")->item(0);
    
    if (!$tableNode) {
        return ['data' => [], 'table_found' => false];
    }

    $rows = $xpath->query(".//tbody/tr", $tableNode);
    foreach ($rows as $row) {
        $rowData = ['ID' => null, 'CODE' => null, 'NAME' => null, 'URL' => null];
        $cells = $xpath->query(".//td", $row);

        if ($serverKey === 'server2') {
            // Logika parsing khusus untuk server2
            // ID: kolom 0 (indeks 0)
            // NAME: kolom 1 (indeks 1)
            // CODE: kolom 2 (indeks 2)
            // URL: dari tombol di kolom 5 (indeks 5)
            if ($cells->length > 0) $rowData['ID'] = trim($cells->item(0)->nodeValue ?? '');
            if ($cells->length > 1) $rowData['NAME'] = trim($cells->item(1)->nodeValue ?? '');
            if ($cells->length > 2) $rowData['CODE'] = trim($cells->item(2)->nodeValue ?? '');
            
            if ($cells->length > 5) { 
                $actionCell = $cells->item(5);
                if ($actionCell) {
                    // Mencari tombol dengan class 'copy-btn' dan teks 'Copy Url' (sesuai contoh HTML server2)
                    $buttonNode = $xpath->query(".//button[contains(@class, 'copy-btn') and normalize-space(text())='Copy Url']", $actionCell)->item(0);
                    if (!$buttonNode) {
                        // Fallback jika teks tombol berbeda atau tidak ada, tapi class 'copy-btn' ada
                        $buttonNode = $xpath->query(".//button[contains(@class, 'copy-btn')]", $actionCell)->item(0);
                    }

                    if ($buttonNode) {
                        $rowData['URL'] = trim($buttonNode->getAttribute('data-copy'));
                        // Proses URL untuk memastikan absolut
                        if (!empty($rowData['URL']) && !preg_match('/^https?:\/\//i', $rowData['URL'])) {
                            if (substr($rowData['URL'], 0, 1) !== '/') $rowData['URL'] = '/' . $rowData['URL'];
                            $rowData['URL'] = rtrim($baseDomain, '/') . $rowData['URL'];
                        }
                    }
                }
            }
        } else {
            // Logika parsing original untuk server1, adult18, dan lainnya
            // ID: kolom 0 (indeks 0)
            // CODE: kolom 1 (indeks 1)
            // NAME: kolom 2 (indeks 2)
            // URL: dari tombol di kolom 4 (indeks 4)
            if ($cells->length > 0) $rowData['ID'] = trim($cells->item(0)->nodeValue ?? '');
            if ($cells->length > 1) $rowData['CODE'] = trim($cells->item(1)->nodeValue ?? '');
            if ($cells->length > 2) $rowData['NAME'] = trim($cells->item(2)->nodeValue ?? '');
            
            if ($cells->length > 4) { 
                $actionCell = $cells->item(4);
                if ($actionCell) {
                    $buttonNode = $xpath->query(".//button[contains(@class, 'copy-btn') and normalize-space(text())='Copy Url']", $actionCell)->item(0);
                    if ($buttonNode) {
                        $rowData['URL'] = trim($buttonNode->getAttribute('data-copy'));
                        // Proses URL untuk memastikan absolut
                        if (!empty($rowData['URL']) && !preg_match('/^https?:\/\//i', $rowData['URL'])) {
                            if (substr($rowData['URL'], 0, 1) !== '/') $rowData['URL'] = '/' . $rowData['URL'];
                            $rowData['URL'] = rtrim($baseDomain, '/') . $rowData['URL'];
                        }
                    }
                }
            }
        }
        $extractedData[] = $rowData;
    }
    return ['data' => $extractedData, 'table_found' => true];
}

// --- Alur Utama Skrip ---
$ch = null; 
$searchQuery = isset($_GET['search_name']) ? trim($_GET['search_name']) : ''; 

// Jika belum login, lakukan login otomatis
if (!isset($_SESSION['dbm_logged_in']) || !$_SESSION['dbm_logged_in']) {
    $ch = initCurl();
    $loginResult = performLogin($ch, $loginPageUrl, $loginPostUrl, $hardcoded_username, $hardcoded_password, $baseDomain);
    if (!$loginResult['success']) {
        if ($ch) curl_close($ch);
        outputErrorPage("Login Otomatis Gagal", $loginResult['message'], ($loginResult['details'] ?? '') . (isset($loginResult['response']) ? ' Server Response Preview: '. substr(htmlspecialchars($loginResult['response']),0,200) : ''));
    }
    // Tidak perlu menutup cURL di sini jika login berhasil dan akan dilanjutkan
    // if ($ch) curl_close($ch); // Ditutup nanti setelah selesai semua operasi cURL
    
    // Redirect ke halaman pemilihan server setelah login berhasil
    $redirectUrl = '?action=select_server';
    if (!empty($searchQuery)) { // Bawa query pencarian jika ada
        $redirectUrl .= '&search_name=' . urlencode($searchQuery);
    }
    // Jika cURL masih terbuka dari login, tutup sebelum header redirect
    if ($ch && is_resource($ch)) curl_close($ch); 
    header('Location: ' . $redirectUrl); 
    exit;
}

$action = $_GET['action'] ?? 'select_server'; 
$selectedServerKey = $_GET['server'] ?? null;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage); 

// Handle logout
if ($action === 'logout' || $action === 'logout_and_retry') {
    if (isset($_SESSION['dbm_cookie_file_path']) && file_exists($_SESSION['dbm_cookie_file_path'])) {
        @unlink($_SESSION['dbm_cookie_file_path']);
    }
    session_unset();
    session_destroy();
    if ($ch && is_resource($ch)) curl_close($ch); 
    header('Location: ' . strtok($_SERVER["REQUEST_URI"],'?')); 
    exit;
}

// Jika server sudah dipilih, ambil dan tampilkan data
if ($selectedServerKey && array_key_exists($selectedServerKey, $serverUrls)) {
    $ch = initCurl(); 
    $targetDataUrl = $serverUrls[$selectedServerKey];
    // Gunakan URL setelah login sebagai referer, atau URL pemilihan server jika tidak ada
    $refererForFetch = $_SESSION['dbm_final_url_after_login'] ?? (strtok($_SERVER["REQUEST_URI"],'?') . '?action=select_server');
    $fetchResult = fetchData($ch, $targetDataUrl, $refererForFetch);

    if ($fetchResult['success']) {
        // Panggil parseDataFromHtml dengan $selectedServerKey
        $parsingResult = parseDataFromHtml($fetchResult['html'], $baseDomain, $selectedServerKey);
        $allExtractedData = $parsingResult['data']; 
        $tableWasFoundByParser = $parsingResult['table_found'];

        $filteredData = $allExtractedData;
        if (!empty($searchQuery)) {
            $filteredData = array_filter($allExtractedData, function($item) use ($searchQuery) {
                return isset($item['NAME']) && stripos($item['NAME'], $searchQuery) !== false;
            });
        }

        $totalItems = count($filteredData);
        $totalPages = ($itemsPerPage > 0) ? ceil($totalItems / $itemsPerPage) : 1;
        $totalPages = max(1, $totalPages); 
        $currentPage = max(1, min($currentPage, $totalPages)); 
        $offset = ($currentPage - 1) * $itemsPerPage;
        $paginatedData = ($itemsPerPage > 0) ? array_slice($filteredData, $offset, $itemsPerPage) : $filteredData;

        $serverDisplayName = ucwords(str_replace(['_','-'], ' ', $selectedServerKey));
        $selectServerUrl = strtok($_SERVER["REQUEST_URI"],'?') . '?action=select_server';
        // $logoutUrl = strtok($_SERVER["REQUEST_URI"],'?') . '?action=logout'; // Sudah ada di header
        
        $baseSearchFormAction = strtok($_SERVER["REQUEST_URI"],'?') . '?server=' . urlencode($selectedServerKey);
        $basePaginationUrl = $baseSearchFormAction . (!empty($searchQuery) ? '&search_name=' . urlencode($searchQuery) : '') . '&page=';

        // Output bagian utama HTML halaman
        echo <<<HTML
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Film: {$serverDisplayName} (Hal {$currentPage}) - DBMovieLink</title><script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>body { font-family: 'Inter', sans-serif; } .table-fixed-layout { table-layout: fixed; width: 100%; } .table-fixed-layout th, .table-fixed-layout td { overflow-wrap: break-word; word-wrap: break-word; }</style></head>
<body class="bg-slate-100 min-h-screen p-4 md:p-8"><div class="container mx-auto">
<header class="mb-8 flex flex-col sm:flex-row justify-between items-center"><div>
<h1 class="text-3xl md:text-4xl font-bold text-slate-800">Data Film: {$serverDisplayName}</h1>
<p class="text-slate-600 mt-1">Menampilkan data dari sumber yang dipilih. (Total : $totalItems Data)</p></div><div>
<a href="{$selectServerUrl}" class="mt-4 sm:mt-0 inline-block bg-sky-500 hover:bg-sky-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 text-sm mr-2">&larr; Pilih Server Lain</a>
</div></header>

<div class="mb-6 bg-white p-4 rounded-lg shadow">
  <form action="{$baseSearchFormAction}" method="GET" class="flex gap-2 items-center">
    <input type="hidden" name="server" value="{$selectedServerKey}">
    <input type="text" name="search_name" value="{$searchQuery}" placeholder="Cari berdasarkan Nama Film..." class="flex-grow p-2 border border-slate-300 rounded-md focus:ring-sky-500 focus:border-sky-500">
    <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white font-semibold py-2 px-4 rounded-md transition duration-200">Cari</button>
    <a href="{$baseSearchFormAction}" class="bg-slate-300 hover:bg-slate-400 text-slate-700 font-semibold py-2 px-4 rounded-md transition duration-200">Reset</a>
  </form>
</div>
<div class="bg-white p-6 md:p-8 rounded-xl shadow-xl overflow-x-auto">
HTML;
        // Output tabel data
        if (!empty($paginatedData)) { 
            echo '<table class="min-w-full divide-y divide-slate-200 table-fixed-layout"><thead class="bg-slate-50"><tr>
            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-slate-700 w-1/12">ID</th>
            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-slate-700 w-6/12">NAME</th>
            <th scope="col" class="px-4 py-3.5 text-center text-sm font-semibold text-slate-700 w-3/12">URL</th>
            </tr></thead><tbody class="divide-y divide-slate-200 bg-white">';
            foreach ($paginatedData as $item) { 
                echo '<tr>
                <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-600">' . htmlspecialchars($item['ID'] ?? 'N/A') . '</td>
                <td class="px-4 py-4 text-sm text-slate-800 font-medium">' . htmlspecialchars($item['NAME'] ?? 'N/A') . '</td>
                <td class="whitespace-nowrap px-4 py-4 text-sm text-center">';
                if (!empty($item['URL'])) {
                    echo '<button onclick="showAdModal(\'' . htmlspecialchars($item['URL']) . '\')" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition duration-150">Lihat Film</button>';
                } else { echo '<span class="text-slate-400">N/A</span>'; }
                echo '</td></tr>';
            }
            echo '</tbody></table>';

            // Output navigasi paginasi
            if ($totalPages > 1) {
                echo '<nav class="mt-6 flex items-center justify-between border-t border-slate-200 px-4 sm:px-0" aria-label="Pagination">';
                echo '<div class="hidden sm:block"><p class="text-sm text-slate-700">Menampilkan <span class="font-medium">' . (($totalItems > 0) ? ($offset + 1) : 0) . '</span> sampai <span class="font-medium">' . min($offset + $itemsPerPage, $totalItems) . '</span> dari <span class="font-medium">' . $totalItems . '</span> hasil</p></div>';
                echo '<div class="flex flex-1 justify-between sm:justify-end space-x-1">';
                if ($currentPage > 1) {
                    echo '<a href="' . $basePaginationUrl . ($currentPage - 1) . '" class="inline-flex items-center px-3 py-2 border border-slate-300 text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 transition">Sebelumnya</a>';
                } else {
                    echo '<span class="inline-flex items-center px-3 py-2 border border-slate-200 text-sm font-medium rounded-md text-slate-400 bg-slate-50 cursor-not-allowed">Sebelumnya</span>';
                }
                if ($currentPage < $totalPages) {
                    echo '<a href="' . $basePaginationUrl . ($currentPage + 1) . '" class="inline-flex items-center px-3 py-2 border border-slate-300 text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 transition">Berikutnya</a>';
                } else {
                     echo '<span class="inline-flex items-center px-3 py-2 border border-slate-200 text-sm font-medium rounded-md text-slate-400 bg-slate-50 cursor-not-allowed">Berikutnya</span>';
                }
                echo '</div></nav>';
            }
        } else { // Data kosong atau tidak ada hasil pencarian
            echo '<div class="text-center py-12"><svg class="w-16 h-16 text-slate-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>'; 
            if (!empty($searchQuery)) {
                 echo '<p class="text-slate-600 text-lg">Tidak ada hasil yang ditemukan untuk pencarian: "<strong>' . htmlspecialchars($searchQuery) . '</strong>".</p>';
            } else if ($tableWasFoundByParser) { // Tabel ditemukan tapi kosong
                echo '<p class="text-slate-600 text-lg">Tabel data ditemukan, tetapi tidak ada baris data di dalamnya untuk server ini.</p>';
            } else { // Tabel tidak ditemukan sama sekali
                echo '<p class="text-slate-600 text-lg">Tidak ada data yang ditemukan untuk server ini.</p>';
                echo '<p class="text-sm text-slate-500 mt-1">Kemungkinan tabel data (<tt>id="example"</tt>) tidak ditemukan pada halaman server yang dipilih, atau halaman tidak dapat diakses dengan benar.</p>';
            }
            echo '<p class="text-sm text-slate-500 mt-1">Silakan coba kata kunci lain, reset pencarian, atau pilih server lain.</p>';
            // Debugging output untuk HTML yang diterima jika tabel tidak ditemukan dan tidak ada pencarian
            if (isset($fetchResult['html']) && !empty($fetchResult['html']) && empty($searchQuery) && !$tableWasFoundByParser) { 
                echo '<p class="mt-4 text-sm font-semibold text-slate-700">Debugging: HTML Awal Diterima (2000 karakter pertama):</p>';
                echo '<textarea class="w-full h-60 mt-2 p-2 border rounded text-xs text-left bg-gray-50" readonly>';
                echo htmlspecialchars(substr($fetchResult['html'], 0, 2000));
                echo '</textarea>';
            } else if (isset($fetchResult['html_preview'])) { // Jika ada preview dari error fetch
                echo '<p class="mt-4 text-sm font-semibold text-slate-700">Debugging: HTML Awal Diterima (Preview dari Error):</p>';
                echo '<textarea class="w-full h-60 mt-2 p-2 border rounded text-xs text-left bg-gray-50" readonly>';
                echo htmlspecialchars($fetchResult['html_preview']);
                echo '</textarea>';
            }
             echo '</div>'; // Menutup div text-center
        }
        // Menutup div bg-white dan container
        echo '</div><footer class="text-center mt-12 text-sm text-slate-500"><p>&copy; ' . date("Y") . ' DBMovieLink Data Viewer.</p></footer></div>';

        // Modal untuk iklan/countdown sebelum redirect
        echo <<<HTML
<div id="adModalOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div id="adModalContent" class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full">
        <div class="text-center">
            <div id="countdownTimer" class="text-2xl font-bold mb-3 text-slate-700"></div>
            <div id="xx" class="mb-4 h-60 bg-slate-200 rounded flex items-center justify-center overflow-hidden">
                <img src="https://placehold.co/400x240/E2E8F0/475569?text=Banner+Anda+Disini" alt="[Image of Kustom]" class="max-h-full max-w-full object-contain rounded">
            </div>
            <p class="text-sm text-slate-500">Anda akan diarahkan dalam beberapa detik...</p>
        </div>
    </div>
</div>

<script>
    const adModalOverlay = document.getElementById('adModalOverlay');
    const adModalContent = document.getElementById('adModalContent');
    const countdownTimerDisplay = document.getElementById('countdownTimer');
    let countdownInterval;
    let redirectToUrlGlobal = ''; 

    function showAdModal(movieUrl) {
        redirectToUrlGlobal = movieUrl;
        let seconds = 5;
        countdownTimerDisplay.textContent = seconds + ' detik';
        
        adModalOverlay.classList.remove('hidden');
        adModalOverlay.classList.add('flex');

        clearInterval(countdownInterval); // Hapus interval sebelumnya jika ada

        countdownInterval = setInterval(() => {
            seconds--;
            if (seconds >= 0) {
                countdownTimerDisplay.textContent = seconds + (seconds === 1 ? ' detik' : ' detik');
            }
            if (seconds < 0) { 
                clearInterval(countdownInterval);
                window.open(redirectToUrlGlobal, '_blank'); 
                hideAdModal();
            }
        }, 1000);
    }

    function hideAdModal() {
        adModalOverlay.classList.add('hidden');
        adModalOverlay.classList.remove('flex');
        clearInterval(countdownInterval); 
    }

    // Mencegah modal tertutup jika diklik di luar konten modal (overlay)
    // Pengguna harus menunggu countdown selesai.
    adModalOverlay.addEventListener('click', function(event) {
        if (event.target === adModalOverlay) {
            // Tidak melakukan apa-apa, biarkan modal tetap terbuka
        }
    });
</script>
HTML;
        echo '</body></html>';
        
    } else { 
        // Handle error saat fetch data
        if (isset($fetchResult['redirect_to_login']) && $fetchResult['redirect_to_login']) {
            outputErrorPage('Sesi Berakhir', $fetchResult['message'], 'Silakan login kembali. Preview: ' . htmlspecialchars(substr($fetchResult['html_preview'] ?? '',0,200)) . ' URL Diakses: ' . ($fetchResult['final_url'] ?? 'N/A'));
        } else {
            outputErrorPage('Gagal Mengambil Data', $fetchResult['message'], 'Preview: ' . htmlspecialchars(substr($fetchResult['html_preview'] ?? '',0,200)) . (isset($fetchResult['final_url'])? ' URL Diakses: '.$fetchResult['final_url']:''));
        }
    }
    if ($ch && is_resource($ch)) curl_close($ch); // Tutup handle cURL setelah selesai
    exit;
}

// Halaman pemilihan server (jika sudah login dan tidak ada server dipilih atau action=select_server)
if (isset($_SESSION['dbm_logged_in']) && ($action === 'select_server' || !$selectedServerKey)) {
    $currentScriptUrl = strtok($_SERVER["REQUEST_URI"],'?');
    $logoutUrl = $currentScriptUrl . '?action=logout';
    $selectServerBaseUrl = $currentScriptUrl;

    echo <<<HTML
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pilih Server Pencarian Data - DBMovieLink</title><script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>body { font-family: 'Inter', sans-serif; }  .logo-container { display: flex; align-items: center; justify-content: center; gap: 12px; } .logo-container img { height: 50px; } .logo-container span { font-size: 1.8rem; font-weight: bold; letter-spacing: 1px; }</style></head>
<body class="bg-slate-100 min-h-screen p-4 md:p-8"><div class="container mx-auto max-w-3xl">
<header class="mb-10 text-center"><div class="logo-container"><span>IDBMOVIE LINK</span></div>
<p class="text-slate-600 mt-2">Silakan pilih server untuk memulai pencarian data film.</p></header>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
HTML;
    $serverDisplayNames = ['server1' => 'Server 1', 'server2' => 'Server 2', 'adult18' => 'Adult 18+'];
    $serverIcons = [ // SVG ikon untuk setiap server
        'server1' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>',
        'server2' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>', // Ikon berbeda untuk server2
        'adult18' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16m-7-7h2.586a1 1 0 00.707-.293l3.414-3.414a1 1 0 00.293-.707V6a1 1 0 00-1-1h-4a1 1 0 00-1 1v1" /></svg>'
    ];
    foreach ($serverUrls as $key => $url) {
        $displayName = $serverDisplayNames[$key] ?? ucfirst($key);
        $icon = $serverIcons[$key] ?? $serverIcons['server1']; // Fallback ikon
        $searchParam = !empty($searchQuery) ? '&search_name=' . urlencode($searchQuery) : '';
        $serverLink = $selectServerBaseUrl . '?server=' . $key . $searchParam;
        echo <<<HTML
<a href="{$serverLink}" class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300 flex flex-col items-center text-center">
{$icon}<h3 class="text-xl font-semibold text-slate-700 mb-2">{$displayName}</h3>
<p class="text-sm text-slate-500">Lihat data dari {$displayName}.</p></a>
HTML;
    }
    echo <<<HTML
</div><div class="mt-12 text-center">
<a href="/api_docs.php" class="text-slate-600 hover:text-red-600 font-medium transition duration-200">
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" /></svg>Rest API</a>
</div></div></body></html>
HTML;
    if ($ch && is_resource($ch)) curl_close($ch);
    exit;
}

// Fallback jika tidak ada kondisi yang cocok (seharusnya tidak terjadi jika logika di atas benar)
if ($ch && is_resource($ch)) curl_close($ch);
outputErrorPage("Kesalahan Alur Aplikasi", "Halaman yang diminta tidak valid atau terjadi kesalahan internal.");
?>
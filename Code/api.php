<?php
header('Content-Type: application/json');

$remoteUser = $_SERVER['HTTP_REMOTE_USER'] ?? '';
$remoteGroups = $_SERVER['HTTP_REMOTE_GROUPS'] ?? '';
$groups = array_map('trim', explode(',', $remoteGroups));

if (empty($remoteUser) || (!in_array('admin', $groups) && !in_array('petugas', $groups))) {
    http_response_code(403);
    echo json_encode(["error" => "Akses ditolak: Otorisasi peran tidak valid."]);
    exit;
}

$isAdmin = in_array('admin', $groups);

$host = getenv('DB_HOST') ?: 'bapenda-db';
$db   = getenv('DB_NAME') ?: 'db_bapenda_pajak';
$user = getenv('DB_USER') ?: 'bapenda_app_user';
$pass = getenv('DB_PASS');
$key  = getenv('ENCRYPTION_KEY');

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT no_transaksi, jenis_pajak, status_pembayaran,
               pgp_sym_decrypt(nik_encrypted, :key) AS nik_decoded
        FROM wajib_pajak
    ");
    $stmt->execute([':key' => $key]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$isAdmin) {
        foreach ($data as &$row) {
            $nik = $row['nik_decoded'];
            $row['nik_decoded'] = substr($nik, 0, 6) . str_repeat('*', max(0, strlen($nik) - 10)) . substr($nik, -4);
        }
    }

    echo json_encode($data);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Terjadi kesalahan pemrosesan data."]);
}
?>
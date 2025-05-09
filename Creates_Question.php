<?php
include 'config/database.php';

if (!file_exists('vendor/autoload.php')) {
    die('Thư viện Dompdf chưa được cài đặt. Vui lòng chạy: <code>composer require dompdf/dompdf</code>');
}
require 'vendor/autoload.php';

use Dompdf\Dompdf;

// Lấy danh sách ngành
function layDanhSachNganh($conn) {
    $sql = "SELECT DISTINCT department FROM subjects";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Lấy danh sách môn theo ngành
function layDanhSachMon($conn, $nganh) {
    $sql = "SELECT id, name FROM subjects WHERE department = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nganh);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Lấy tên bộ môn từ subject_id
function layTenMon($conn, $subject_id) {
    $sql = "SELECT name FROM subjects WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['name']; 
    }
    return null;
}

// Lấy câu hỏi theo mức độ
function layCauHoi($conn, $muc_do, $nganh, $mon, $so_luong) {
    $sql = "SELECT * FROM Create_Questions WHERE difficulty = ? AND category = ? AND subject_id = ? ORDER BY RAND() LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $muc_do, $nganh, $mon, $so_luong);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Chọn ngành để hiển thị môn học
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nganh']) && !isset($_POST['de'])) {
    $nganh = trim($_POST['nganh']);
    $mon_hoc_list = layDanhSachMon($conn, $nganh);
}

// Xử lý tạo đề
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['de'], $_POST['kha'], $_POST['kho'], $_POST['nganh'], $_POST['mon'])) {
    $de = (int)$_POST['de'];
    $kha = (int)$_POST['kha'];
    $kho = (int)$_POST['kho'];
    $nganh = trim($_POST['nganh']);
    $mon = (int)$_POST['mon'];

    // Lấy tên bộ môn từ ID
    $ten_mon = layTenMon($conn, $mon);

    $ds_de = layCauHoi($conn, "Dễ", $nganh, $mon, $de);
    $ds_kha = layCauHoi($conn, "Khá", $nganh, $mon, $kha);
    $ds_kho = layCauHoi($conn, "Khó", $nganh, $mon, $kho);

    // Kiểm tra từng loại
    if (count($ds_de) < $de || count($ds_kha) < $kha || count($ds_kho) < $kho) {
        echo "<script>
                alert('Không đủ câu hỏi trong cơ sở dữ liệu.\\nYêu cầu: $de Dễ, $kha Khá, $kho Khó.\\nHiện có: " . count($ds_de) . " Dễ, " . count($ds_kha) . " Khá, " . count($ds_kho) . " Khó.');
                window.history.back();
              </script>";
        exit();
    }

    $tat_ca = array_merge($ds_de, $ds_kha, $ds_kho);
    shuffle($tat_ca);

    // Tạo nội dung đề
    $noi_dung = "
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 16px; }
            h2 { text-align: center; }
            ol { margin-top: 20px; }
        </style>
        <h2>Đề Thi Ngẫu Nhiên - Ngành: <strong>$nganh</strong> - Bộ Môn: <strong>$ten_mon</strong></h2>
        <ol>";
    foreach ($tat_ca as $cau) {
        $noi_dung .= "<li>{$cau['question_text']}<br>
            A. {$cau['answer_a']}<br>
            B. {$cau['answer_b']}<br>
            C. {$cau['answer_c']}<br>
            D. {$cau['answer_d']}<br><br></li>";
    }
    $noi_dung .= "</ol>";

    // Xuất PDF
    $dompdf = new Dompdf([ 
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true,
    ]);
    $dompdf->loadHtml($noi_dung);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("De_Thi_Ngau_Nhien.pdf");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo Đề Thi Ngẫu Nhiên</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">
</head>
<body>
    <div class="container">
        <h1>Tạo Đề Thi Ngẫu Nhiên</h1>

        <!-- Form chọn ngành -->
        <form method="POST">
            <label for="nganh">Ngành:</label>
            <select name="nganh" id="nganh" required>
                <?php
                $nganh_list = layDanhSachNganh($conn);
                foreach ($nganh_list as $nganh_item) {
                    echo "<option value='{$nganh_item['department']}'>{$nganh_item['department']}</option>";
                }
                ?>
            </select>
            <button type="submit">Chọn Ngành</button>
        </form>

        <?php if (isset($mon_hoc_list)): ?>
        <!-- Form tạo đề -->
        <form method="POST">
            <label for="mon">Bộ Môn:</label>
            <select name="mon" id="mon" required>
                <?php foreach ($mon_hoc_list as $mon_item): ?>
                    <option value="<?= $mon_item['id'] ?>"><?= $mon_item['name'] ?></option>
                <?php endforeach; ?>
            </select>

            <label for="de">Số câu Dễ:</label>
            <input type="number" name="de" required>

            <label for="kha">Số câu Khá:</label>
            <input type="number" name="kha" required>

            <label for="kho">Số câu Khó:</label>
            <input type="number" name="kho" required>

            <input type="hidden" name="nganh" value="<?= htmlspecialchars($nganh) ?>">

            <button type="submit">Tạo Đề</button>
        </form>
        <?php endif; ?>

        <a href="dashboard.php" class="button">Quay lại trang chủ</a>
    </div>
</body>
</html>

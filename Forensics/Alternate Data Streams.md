1 file có 2 luồng dữ liệu chính:
- Unnamed Stream: nội dung hiển thị ngay khi mở file, nằm ở ngăn chính 
- Alternate Data Streams: nội dung ẩn, nằm trong ngăn phụ

Ví dụ:

Bước 1: Tạo một tệp văn bản bình thường
Chúng ta sẽ tạo một file có tên binhthuong.txt và ghi vào đó nội dung cơ bản.


`echo "Day la noi dung chinh, ai cung co the nhin thay" > binhthuong.txt`

Bước 2: Giấu dữ liệu vào ngăn bí mật (Tạo ADS)
Bây giờ, chúng ta sẽ tạo một luồng ẩn có tên là bimat đính kèm vào file binhthuong.txt. Cú pháp là TenFileGoc:TenLuongAn.


`echo "DAY LA THONG TIN MAT, DANG BI GIAU!" > binhthuong.txt:bimat`

Bước 3: Kiểm tra sự tàng hình
Hãy dùng lệnh dir để xem thông tin file gốc.


`dir binhthuong.txt`

Kết quả: Bạn sẽ thấy kích thước của file chỉ khoảng vài chục byte (bằng đúng kích thước của dòng chữ ở Bước 1). Dữ liệu giấu ở Bước 2 hoàn toàn không làm tăng dung lượng file gốc. Nếu bạn mở file binhthuong.txt bằng Notepad, bạn cũng chỉ thấy nội dung Bước 1.

Bước 4: Phát hiện luồng dữ liệu ẩn
Nếu không có công cụ streams.exe của Sysinternals, Windows vẫn có một tham số ẩn trong lệnh dir để soi ra ADS. Đó là tham số /r.


`dir /r binhthuong.txt`

Kết quả: Lúc này, bên dưới file gốc, bạn sẽ thấy xuất hiện một dòng ghi là binhthuong.txt:bankinh:$DATA. Đây chính là luồng ẩn bạn đã tạo.

Bước 5: Đọc nội dung bị giấu
Như mình đã đề cập ở câu trả lời trước, bạn không thể dùng lệnh type hay mở Notepad bình thường để đọc luồng ẩn. Bạn phải ép Windows đọc chính xác luồng đó thông qua lệnh more:


`more < binhthuong.txt:bimat`

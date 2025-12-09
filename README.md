# -----------------------------------------------
# 🚀 SETUP DỰ ÁN LARAVEL SAU KHI CLONE
# -----------------------------------------------

# Bước 1: Cài đặt thư viện PHP qua Composer
composer install

# Bước 2: Tạo file .env từ .env.example
cp .env.example .env

# Bước 3: Tạo khóa ứng dụng Laravel
php artisan key:generate

# Bước 4: (Thực hiện thủ công)
echo "➡️ Hãy mở file .env và cấu hình kết nối cơ sở dữ liệu (DB_DATABASE, DB_USERNAME, DB_PASSWORD...)"

# Bước 5: Tạo bảng trong cơ sở dữ liệu
php artisan migrate

# Bước 6: Build frontend (nếu có dùng Vite hoặc Mix)
npm install
npm run dev-all

# Bước 7: Truy cập ứng dụng
echo "✅ Truy cập ứng dụng tại: http://localhost:8000"

# Bước 8: (Tuỳ chọn) Tạo lại tài liệu API nếu có thay đổi
php artisan scribe:generate
echo "📘 Tài liệu API có tại: http://localhost:8000/docs"

# Import tỉnh thành phố bằng lệnh command 
php artisan import:provinces 2025-08-20
có thể bỏ phần optional ngày tháng năm 
php artisan import:provinces
# -----------------------------------------------
Thay đổi theo ngày tháng năm hiện tại để lấy dữ liệu mới nhất { yyyy-mm-dd }

# -----------------------------------------------
seeders data
php artisan db:seed 

# -----------------------------------------------
php artisan app:import-location-into-competition-location

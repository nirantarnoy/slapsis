RewriteEngine On

# ส่งทุกคำขอไปยัง backend/web ยกเว้น asset จริงๆ
RewriteCond %{REQUEST_URI} !^/backend/web/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ backend/web/$1 [L]

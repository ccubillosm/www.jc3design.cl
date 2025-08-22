#!/bin/bash
# activate-jc3design.sh

echo "🌐 Activando dominio jc3design.localhost..."

# Variables
PROJECT_PATH="/Users/acidlabs/Desktop/escritorio/www.jc3design.cl"
SITE_NAME="jc3design"

# Crear directorios si no existen
sudo mkdir -p /usr/local/etc/nginx/sites-available
sudo mkdir -p /usr/local/etc/nginx/sites-enabled
sudo mkdir -p /usr/local/var/log/nginx

# Crear configuración del sitio
sudo tee /usr/local/etc/nginx/sites-available/$SITE_NAME > /dev/null <<EOF
server {
    listen 80;
    server_name $SITE_NAME.localhost;
    
    root $PROJECT_PATH;
    index index.html index.php;
    
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|webp)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    location / {
        try_files \$uri \$uri/ /index.html;
    }
    
    access_log /usr/local/var/log/nginx/${SITE_NAME}_access.log;
    error_log /usr/local/var/log/nginx/${SITE_NAME}_error.log;
}
EOF

# Crear enlace simbólico
sudo ln -sf /usr/local/etc/nginx/sites-available/$SITE_NAME /usr/local/etc/nginx/sites-enabled/

# Agregar a hosts
echo "127.0.0.1 $SITE_NAME.localhost" | sudo tee -a /etc/hosts

# Verificar configuración
sudo nginx -t

# Recargar Nginx
sudo nginx -s reload

echo "✅ Dominio activado!"
echo "�� Accede a: http://$SITE_NAME.localhost"

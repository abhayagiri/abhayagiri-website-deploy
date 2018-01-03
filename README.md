# Abhayagiri Website Deployment

## Install and Deploy Website

Staging:

```sh
vendor/bin/dep deploy staging
```

Production:

```sh
vendor/bin/dep deploy production
```

## Import Database on Staging

```sh
vendor/bin/dep deploy:import-database staging
```

## Add User (on deploy.abhayagiri.org)

```sh
cd /opt/deploy
sudo -u www-data php artisan app:add-user <email> "<name>"
```

## Install Deployer (on deploy.abhayagiri.org)

As root:

```sh
apt-get update
apt-get install -y curl software-properties-common dirmngr
apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xF1656F24C74CD1D8
add-apt-repository 'deb [arch=amd64] http://sfo1.mirrors.digitalocean.com/mariadb/repo/10.2/debian stretch main'
apt-get update
apt-get install -y git mariadb-client mariadb-server nodejs \
  php7.0 php7.0-bz2 php7.0-curl php7.0-gd php7.0-opcache \
  php7.0-mbstring php7.0-mysql php7.0-xml php7.0-zip
curl -sL https://deb.nodesource.com/setup_8.x | bash -
if ! test -f /usr/local/bin/composer; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
else
  /usr/local/bin/composer self-update
fi
apt-get install -y nginx php7.0-fpm supervisor
if ! test -d /root/.acme.sh; then
  curl https://get.acme.sh | sh
fi
systemctl stop nginx
/root/.acme.sh/acme.sh --issue --standalone -d deploy.abhayagiri.org
mkdir -p /etc/nginx/certs/deploy.abhayagiri.org
chmod 700 /etc/nginx/certs/deploy.abhayagiri.org
acme.sh --install-cert -d deploy.abhayagiri.org \
  --key-file       /etc/nginx/certs/deploy.abhayagiri.org/key \
  --fullchain-file /etc/nginx/certs/deploy.abhayagiri.org/fullchain \
  --reloadcmd      "systemctl reload nginx"
cat <<'EOF' > /etc/nginx/sites-available/deploy
server {
    listen 80;
    listen [::]:80;
    server_name deploy.abhayagiri.org;
    return 301 https://deploy.abhayagiri.org$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name deploy.abhayagiri.org;
    ssl_certificate /etc/nginx/certs/deploy.abhayagiri.org/fullchain;
    ssl_certificate_key /etc/nginx/certs/deploy.abhayagiri.org/key;
    root /opt/deploy/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri /index.php =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF
ln -sf ../sites-available/deploy /etc/nginx/sites-enabled/deploy
systemctl start nginx
mkdir /opt/deploy
chown www-data:www-data /opt/deploy
sudo -u www-data git clone https://github.com/abhayagiri/abhayagiri-website-deploy /opt/deploy
cd /opt/deploy
for d in .composer .config .npm .ssh; do
  mkdir -p /var/www/$d
  chown www-data:www-data /var/www/$d
done
chmod 700 /var/www/.ssh
sudo -u www-data ssh-keygen -t rsa -N '' -C '' -f /var/www/.ssh/id_rsa
echo "DROP DATABASE IF EXISTS deploy; CREATE DATABASE deploy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON deploy.* TO 'deploy'@'localhost' IDENTIFIED BY 'deploy'; FLUSH PRIVILEGES;" | mysql -u root
sudo -u www-data composer install
sudo -u www-data php artisan migrate --force
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate
```

Modify `.env` accordingly. Then:

```
cat <<-EOF > /etc/supervisor/conf.d/deploy.conf
[program:deploy]
process_name=%(program_name)s_%(process_num)02d
command=php /opt/deploy/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/opt/deploy/storage/logs/worker.log
EOF
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart deploy:*
```

## Upgrade (on deploy.abhayagiri.org)

```sh
cd /opt/deploy
sudo -u www-data git pull
sudo -u www-data composer install
sudo -u www-data php artisan migrate --force
sudo supervisorctl restart deploy:*
```

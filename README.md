# Abhayagiri Website Deployment

Commands to be run on `deploy.abhayagiri.org`.

## Manual Deploy

Deploy to https://staging.abhayagiri.org/ (Staging):

```sh
cd /opt/abhayagiri-website-deploy
sudo -u www-data vendor/bin/dep deploy staging
```

Deploy to https://www.abhayagiri.org/ (Production):

```sh
cd /opt/abhayagiri-website-deploy
sudo -u www-data vendor/bin/dep deploy production
```

## Import Database on Staging

```sh
cd /opt/abhayagiri-website-deploy
sudo -u www-data vendor/bin/dep deploy:import-database staging
```

## Add User

```sh
cd /opt/abhayagiri-website-deploy
sudo -u www-data php artisan app:add-user <email> "<name>"
```

## Upgrade

```sh
cd /opt/abhayagiri-website-deploy
sudo -u www-data git pull
sudo -u www-data composer install
sudo -u www-data php artisan migrate --force
sudo supervisorctl restart abhayagiri-website-deploy:*
```

## First Deploy to `*.abhayagiri.org`

Currently the PHP version is 7.3. This is mostly defined in the [Dreamhost
panel](https://panel.dreamhost.com). In addition, you need to set the `PATH` in
`$HOME/.bash_profile for the PHP binaries and composer:

```sh
export PATH=/usr/local/php73/bin:$PATH
export PATH=$HOME/.php/composer:$PATH
```

Finally, due to some incompatibility in `preg` and 7.3 (see
https://github.com/composer/composer/issues/7836), you will also need to add
the following to `$HOME/.php/7.3/phprc`:

```
pcre.jit=0
```

## First Install on `deploy.abhayagiri.org`

The following are run to setup the server on `deploy.abhayagiri.org`.  As root:

```sh
# Install Dependencies
sudo apt-get update
sudo apt-get install -y apt-transport-https ca-certificates dirmngr git \
    lsb-release software-properties-common unzip zip

# Install MariaDB
sudo apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xF1656F24C74CD1D8
distribution="$(lsb_release -si | tr '[:upper:]' '[:lower:]')"
codename="$(lsb_release -sc)"
echo "deb [arch=amd64,i386,ppc64el] http://sfo1.mirrors.digitalocean.com/mariadb/repo/10.3/$distribution $codename main" | sudo tee /etc/apt/sources.list.d/mariadb.list > /dev/null
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y mariadb-client mariadb-server

# Install NodeJS
wget -O - -q https://deb.nodesource.com/setup_12.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install PHP 7.3
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
sudo bash -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
sudo apt-get update
sudo apt-get install -y \
    php7.3 php7.3-bz2 php7.3-curl php7.3-gd php7.3-opcache \
    php7.3-mbstring php7.3-mysql php7.3-xml php7.3-zip

# Install Composer
wget -O - -q https://getcomposer.org/installer | sudo php7.3 -- --install-dir=/usr/local/bin --filename=composer

# Install nginx and supervisor
sudo apt-get install -y nginx php7.3-fpm supervisor

# Install acme.sh
wget -O - -q https://get.acme.sh | sudo -i sh
sudo mkdir -p /etc/nginx/certs/deploy.abhayagiri.org
sudo chmod 700 /etc/nginx/certs/deploy.abhayagiri.org
sudo /root/.acme.sh/acme.sh --issue --nginx \
    --key-file       /etc/nginx/certs/deploy.abhayagiri.org/key \
    --fullchain-file /etc/nginx/certs/deploy.abhayagiri.org/fullchain \
    --reloadcmd      "systemctl reload nginx" \
    --domain         deploy.abhayagiri.org

# Configure nginx
cat <<'EOF' | sudo tee /etc/nginx/sites-available/deploy.abhayagiri.org > /dev/null
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
    root /opt/abhayagiri-website-deploy/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri /index.php =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF

sudo ln -sf ../sites-available/deploy.abhayagiri.org /etc/nginx/sites-enabled/deploy.abhayagiri.org
sudo systemctl restart nginx

for d in .composer .config .npm .ssh; do
  sudo -u www-data mkdir -p /var/www/$d
done
sudo -u www-data chmod 700 /var/www/.ssh

sudo mkdir /opt/abhayagiri-website-deploy
sudo chown www-data:www-data /opt/abhayagiri-website-deploy
sudo -u www-data git clone https://github.com/abhayagiri/abhayagiri-website-deploy /opt/abhayagiri-website-deploy
cd /opt/abhayagiri-website-deploy
sudo -u www-data ssh-keygen -t rsa -N '' -C '' -f /var/www/.ssh/id_rsa
echo "DROP DATABASE IF EXISTS deploy; CREATE DATABASE deploy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON deploy.* TO 'deploy'@'localhost' IDENTIFIED BY 'deploy'; FLUSH PRIVILEGES;" | sudo mysql -u root
sudo -u www-data composer install
sudo -u www-data php artisan migrate --force
sudo -u www-data cp .env.example .env
sudo -u www-data chmod 640 .env
sudo -u www-data php artisan key:generate
```

Modify `.env` accordingly. Then:

```sh
cat <<-EOF | sudo tee /etc/supervisor/conf.d/abhayagiri-website-deploy.conf > /dev/null
[program:abhayagiri-website-deploy]
process_name=%(program_name)s_%(process_num)02d
command=php7.3 /opt/abhayagiri-website-deploy/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/opt/abhayagiri-website-deploy/storage/logs/worker.log
EOF
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart abhayagiri-website-deploy:*
```

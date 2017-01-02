# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/xenial64"

  # forward port 80 of the VM to port 8080 of the host
  config.vm.network "forwarded_port", guest: 80, host: 8080

  config.vm.synced_folder ".", "/vagrant", :mount_options => ["dmode=777", "fmode=666"]

  # provision the box
  config.vm.provision "shell", inline: <<-SHELL
apt-get update
apt-get install -y apache2 php7.0 php7.0-cli php7.0-mysql libapache2-mod-php7.0

echo "mysql-server-5.6 mysql-server/root_password password root" | debconf-set-selections
echo "mysql-server-5.6 mysql-server/root_password_again password root" | debconf-set-selections
apt-get -y install mysql-server-5.7

mysqladmin -u root -proot create overseer
mysql -u root -proot -e "CREATE USER 'overseer'@'localhost' IDENTIFIED BY 'overseer'"
mysql -u root -proot -e "GRANT ALL ON overseer.* TO 'overseer'@'localhost'"

pushd /vagrant
cp includes/database.php.dist includes/database.php
sed -i "s/^\(\$conn_user\s*=\s*\).*\$/\1'overseer';/" includes/database.php
sed -i "s/^\(\$conn_pass\s*=\s*\).*\$/\1'overseer';/" includes/database.php
sed -i "s/^\(\$conn_db\s*=\s*\).*\$/\1'overseer';/" includes/database.php
popd

echo "SELINUX=disabled" > /etc/selinux/config

if ! [ -L /var/www ]; then
  rm -rf /var/www/
  ln -fs /vagrant /var/www
fi

systemctl reload apache2
    SHELL
end

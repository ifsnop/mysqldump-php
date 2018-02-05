#!/bin/bash
echo "[client]" > /tmp/travismy.cnf
echo "user=travis" >> /tmp/travismy.cnf
echo "password=M6s677xygWjR2Lw9" >> /tmp/travismy.cnf

mysql -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'travis'@'%' IDENTIFIED BY 'M6s677xygWjR2Lw9';"
mysql -u root -e "CREATE DATABASE test001;"
mysql -u root -e "CREATE DATABASE test002;"
mysql -u root -e "CREATE DATABASE test005;"
mysql -u root -e "CREATE DATABASE test006a;"
mysql -u root -e "CREATE DATABASE test006b;"
mysql -u root -e "CREATE DATABASE test008;"
mysql -u root -e "CREATE DATABASE test009;"
mysql -u root -e "CREATE DATABASE test010;"
mysql -u root -e "CREATE DATABASE test011;"
mysql -u root -e "CREATE DATABASE test012;"

mysql -u root -e "FLUSH PRIVILEGES;"

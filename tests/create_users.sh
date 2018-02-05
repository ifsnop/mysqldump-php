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
#mysql -u root -e "GRANT ALL PRIVILEGES ON test001.* TO 'travis'@'%' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test002.* TO 'travis'@'%' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test005.* TO 'travis'@'%' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test006a.* TO 'travis'@'%' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test006b.* TO 'travis'@'%' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test008.* TO 'travis'@'%' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test009.* TO 'travis'@'%' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test010.* TO 'travis'@'%' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test011.* TO 'travis'@'%' WITH GRANT OPTION;"
#mysql -u root -e "GRANT SUPER,LOCK TABLES ON *.* TO 'travis'@'%';"
#mysql -u root -e "GRANT SELECT ON mysql.proc to 'travis'@'%';"


#mysql -u root -e "GRANT ALL PRIVILEGES ON test001.* TO 'travis'@'localhost' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test002.* TO 'travis'@'localhost' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test005.* TO 'travis'@'localhost' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test006a.* TO 'travis'@'localhost' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test006b.* TO 'travis'@'localhost' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test008.* TO 'travis'@'localhost' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test009.* TO 'travis'@'localhost' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test010.* TO 'travis'@'localhost' WITH GRANT OPTION;"
#mysql -u root -e "GRANT ALL PRIVILEGES ON test011.* TO 'travis'@'localhost' WITH GRANT OPTION;"
#mysql -u root -e "GRANT SUPER,LOCK TABLES ON *.* TO 'travis'@'localhost';"
#mysql -u root -e "GRANT SELECT ON mysql.proc to 'travis'@'localhost';"

mysql -u root -e "FLUSH PRIVILEGES;"

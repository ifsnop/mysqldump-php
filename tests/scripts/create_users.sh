#!/bin/bash

HOST=${1:-db}
MYSQL_ROOT_PASSWORD=drupal
MYSQL_CMD="mysql -h $HOST -u root -p$MYSQL_ROOT_PASSWORD"
USER=travis

major=`$MYSQL_CMD -e "SELECT @@version\G" | grep version |awk '{print $2}' | awk -F"." '{print $1}'`
medium=`$MYSQL_CMD -e "SELECT @@version\G" | grep version |awk '{print $2}' | awk -F"." '{print $2}'`
minor=`$MYSQL_CMD -e "SELECT @@version\G" | grep version |awk '{print $2}' | awk -F"." '{print $3}'`

printf "\nCreating users in MySQL server version $major.$medium.$minor on host '$HOST' with user '$USER'\n\n"

$MYSQL_CMD -e "CREATE USER IF NOT EXISTS '$USER'@'%';"
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS test001;"
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS test002;"
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS test005;"
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS test006a;"
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS test006b;"
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS test008;"
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS test009;"
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS test010;"
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS test011;"
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS test012;"
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS test014;"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON test001.* TO '$USER'@'%' WITH GRANT OPTION;"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON test002.* TO '$USER'@'%' WITH GRANT OPTION;"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON test005.* TO '$USER'@'%' WITH GRANT OPTION;"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON test006a.* TO '$USER'@'%' WITH GRANT OPTION;"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON test006b.* TO '$USER'@'%' WITH GRANT OPTION;"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON test008.* TO '$USER'@'%' WITH GRANT OPTION;"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON test009.* TO '$USER'@'%' WITH GRANT OPTION;"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON test010.* TO '$USER'@'%' WITH GRANT OPTION;"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON test011.* TO '$USER'@'%' WITH GRANT OPTION;"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON test012.* TO '$USER'@'%' WITH GRANT OPTION;"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON test014.* TO '$USER'@'%' WITH GRANT OPTION;"
$MYSQL_CMD -e "GRANT PROCESS,SUPER,LOCK TABLES ON *.* TO '$USER'@'%';"

if [[ $major -eq 5 && $medium -ge 7 ]]; then
  $MYSQL_CMD -e "GRANT SELECT ON mysql.proc to '$USER'@'%';"
fi

if [[ $major -eq 5 && $medium -ge 7 ]]; then
  $MYSQL_CMD -e "use mysql; update user set authentication_string=PASSWORD('') where User='$USER'; update user set plugin='mysql_native_password';"
fi

$MYSQL_CMD -e "FLUSH PRIVILEGES;"

echo "Listing created databases with user '$USER'"
mysql -h $HOST -u $USER -e "SHOW databases;"

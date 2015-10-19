#!/bin/bash

function checksum_test001() {
for i in 000 001 002 003 010 011 015 027 029 033 200; do
    mysql -B -e "CHECKSUM TABLE test${i}" test001 | grep -v -i checksum
done
}

function checksum_test002() {
for i in 201; do
    mysql --default-character-set=utf8mb4 -B -e "CHECKSUM TABLE test${i}" test002 | grep -v -i checksum
done
}

function checksum_test005() {
for i in 000; do
    mysql -B -e "CHECKSUM TABLE test${i}" test001 | grep -v -i checksum
done
}

for i in $(seq 0 20) ; do
    ret[$i]=0
done

index=0

mysql -e "CREATE USER 'travis'@'localhost' IDENTIFIED BY '';" 2> /dev/null
mysql -e "CREATE DATABASE test001;" 2> /dev/null
mysql -e "CREATE DATABASE test002;" 2> /dev/null
mysql -e "CREATE DATABASE test005;" 2> /dev/null
mysql -e "CREATE DATABASE test006a;" 2> /dev/null
mysql -e "CREATE DATABASE test006b;" 2> /dev/null
mysql -e "GRANT ALL PRIVILEGES ON test001.* TO 'travis'@'localhost';" 2> /dev/null
mysql -e "GRANT SELECT ON mysql.proc to 'travis'@'localhost';" 2> /dev/null
mysql -e "GRANT ALL PRIVILEGES ON test002.* TO 'travis'@'localhost';" 2> /dev/null
mysql -e "GRANT ALL PRIVILEGES ON test005.* TO 'travis'@'localhost';" 2> /dev/null
mysql -e "GRANT ALL PRIVILEGES ON test006a.* TO 'travis'@'localhost';" 2> /dev/null
mysql -e "GRANT ALL PRIVILEGES ON test00ba.* TO 'travis'@'localhost';" 2> /dev/null
mysql -e "FLUSH PRIVILEGES;" 2> /dev/null

mysql -uroot < test001.src.sql; ret[((index++))]=$?
mysql -uroot --default-character-set=utf8mb4 < test002.src.sql; ret[((index++))]=$?
mysql -uroot < test005.src.sql; ret[((index++))]=$?
mysql -uroot < test006.src.sql; ret[((index++))]=$?

checksum_test001 > test001.src.checksum
checksum_test002 > test002.src.checksum
checksum_test005 > test005.src.checksum

mysqldump -uroot test001 \
    --no-autocommit \
    --extended-insert=false \
    --hex-blob=true \
    --routines=true \
    > mysqldump_test001.sql
ret[((index++))]=$?

mysqldump -uroot test002 \
    --no-autocommit \
    --extended-insert=false \
    --hex-blob=true \
    --default-character-set=utf8mb4 \
    > mysqldump_test002.sql
ret[((index++))]=$?

mysqldump -uroot test005 \
    --no-autocommit \
    --extended-insert=false \
    --hex-blob=true \
    > mysqldump_test005.sql
ret[((index++))]=$?

php test.php
ret[((index++))]=$?

mysql -uroot test001 < mysqldump-php_test001.sql
ret[((index++))]=$?
mysql -uroot test002 < mysqldump-php_test002.sql
ret[((index++))]=$?
mysql -uroot test005 < mysqldump-php_test005.sql
ret[((index++))]=$?

mysql -uroot test006b < mysqldump-php_test006.sql
ret[((index++))]=$?

checksum_test001 > mysqldump-php_test001.checksum
checksum_test002 > mysqldump-php_test002.checksum
checksum_test005 > mysqldump-php_test005.checksum

cat test001.src.sql | grep ^INSERT > test001.filtered.sql
cat test002.src.sql | grep ^INSERT > test002.filtered.sql
cat test005.src.sql | grep ^INSERT > test005.filtered.sql
cat mysqldump_test001.sql | grep ^INSERT > mysqldump_test001.filtered.sql
cat mysqldump_test002.sql | grep ^INSERT > mysqldump_test002.filtered.sql
cat mysqldump_test005.sql | grep ^INSERT > mysqldump_test005.filtered.sql
cat mysqldump-php_test001.sql | grep ^INSERT > mysqldump-php_test001.filtered.sql
cat mysqldump-php_test002.sql | grep ^INSERT > mysqldump-php_test002.filtered.sql
cat mysqldump-php_test005.sql | grep ^INSERT > mysqldump-php_test005.filtered.sql

diff test001.filtered.sql mysqldump_test001.filtered.sql
ret[((index++))]=$?
diff test002.filtered.sql mysqldump_test002.filtered.sql
ret[((index++))]=$?

diff test001.filtered.sql mysqldump-php_test001.filtered.sql
ret[((index++))]=$?
diff test002.filtered.sql mysqldump-php_test002.filtered.sql
ret[((index++))]=$?

diff test001.src.checksum mysqldump-php_test001.checksum
ret[((index++))]=$?
diff test002.src.checksum mysqldump-php_test002.checksum
ret[((index++))]=$?
diff test005.src.checksum mysqldump-php_test005.checksum
ret[((index++))]=$?

diff mysqldump_test005.filtered.sql mysqldump-php_test005.filtered.sql
ret[((index++))]=$?
rm *.checksum 2> /dev/null
rm *.filtered.sql 2> /dev/null
rm mysqldump* 2> /dev/null

echo "Done $index tests"

total=0
for i in $(seq 0 20) ; do
    total=$((${ret[$i]} + $total))
done

echo "Exiting with code $total"

exit $total

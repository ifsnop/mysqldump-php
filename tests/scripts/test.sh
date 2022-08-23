#!/bin/bash

HOST=${1:-db}
USER=travis
MYSQL_CMD="mysql -h $HOST -u $USER"
MYSQLDUMP_CMD="mysqldump -h $HOST -u $USER"

major=`$MYSQL_CMD -e "SELECT @@version\G" | grep version |awk '{print $2}' | awk -F"." '{print $1}'`
medium=`$MYSQL_CMD -e "SELECT @@version\G" | grep version |awk '{print $2}' | awk -F"." '{print $2}'`
minor=`$MYSQL_CMD -e "SELECT @@version\G" | grep version |awk '{print $2}' | awk -F"." '{print $3}'`

printf "\nTesting against MySQL server version $major.$medium.$minor on host '$HOST' with user '$USER'\n"

function checksum_test001() {
for i in 000 001 002 003 010 011 015 027 029 033 200; do
    $MYSQL_CMD -B -e "CHECKSUM TABLE test${i}" test001 | grep -v -i checksum
done
}

function checksum_test002() {
for i in 201; do
    $MYSQL_CMD --default-character-set=utf8mb4 -B -e "CHECKSUM TABLE test${i}" test002 | grep -v -i checksum
done
}

function checksum_test005() {
for i in 000; do
    $MYSQL_CMD -B -e "CHECKSUM TABLE test${i}" test001 | grep -v -i checksum
done
}

for i in $(seq 0 40) ; do
    ret[$i]=0
done

index=0

printf "\nImport source SQL dumps:\n\n"

printf "Import test001.src.sql"
$MYSQL_CMD < test001.src.sql && echo " - done."; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test001.src.sql"; fi

printf "Import test002.src.sql"
$MYSQL_CMD --default-character-set=utf8mb4 < test002.src.sql && echo " - done."; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test002.src.sql"; fi

printf "Import test005.src.sql"
$MYSQL_CMD < test005.src.sql && echo " - done."; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test005.src.sql"; fi

printf "Import test006.src.sql"
$MYSQL_CMD < test006.src.sql && echo " - done."; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test006.src.sql"; fi

printf "Import test008.src.sql"
$MYSQL_CMD < test008.src.sql && echo " - done."; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test008.src.sql"; fi

printf "Import test009.src.sql"
$MYSQL_CMD < test009.src.sql && echo " - done."; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test001.src.sql"; fi

if [[ $major -eq 5 && $medium -ge 7 ]]; then
  printf "Import test010.src.sql"
  $MYSQL_CMD < test010.src.sql && echo " - done."; errCode=$?; ret[((index++))]=$errCode
  if [[ $errCode -ne 0 ]]; then echo "error test010.src.sql"; fi
else
  printf "Import test010.8.src.sql"
  $MYSQL_CMD < test010.8.src.sql && echo " - done."; errCode=$?; ret[((index++))]=$errCode
  if [[ $errCode -ne 0 ]]; then echo "error test010.8.src.sql"; fi
fi

if [[ $major -eq 5 && $medium -ge 7 ]]; then
    printf "Import test011.src.sql"
    # test virtual column support, with simple inserts forced to complete (a) and complete inserts (b)
    $MYSQL_CMD < test011.src.sql && echo " - done."; errCode=$?; ret[((index++))]=$errCode
else
    echo "test011 disabled, only valid for mysql server version 5.7.x"
fi

printf "Import test012.src.sql"
$MYSQL_CMD < test012.src.sql && echo " - done."; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test012.src.sql"; fi
#$MYSQL_CMD < test013.src.sql; errCode=$?; ret[((index++))]=$errCode

printf "\nRun checksum tests:\n\n"

printf "Create checksum: test001.src.checksum"
checksum_test001 > output/test001.src.checksum && echo " - done."
printf "Create checksum: test002.src.checksum"
checksum_test002 > output/test002.src.checksum && echo " - done."
printf "Create checksum: test005.src.checksum"
checksum_test005 > output/test005.src.checksum && echo " - done."

printf "\nRun native mysqldump:\n\n"

printf "Create dump: mysqldump_test001.sql"
$MYSQLDUMP_CMD test001 \
    --no-autocommit \
    --skip-extended-insert \
    --hex-blob \
    --routines \
    > output/mysqldump_test001.sql && echo " - done."
errCode=$?; ret[((index++))]=$errCode

printf "Create dump: mysqldump_test001_complete.sql"
$MYSQLDUMP_CMD test001 \
    --no-autocommit \
    --skip-extended-insert \
    --complete-insert=true \
    --hex-blob \
    --routines \
    > output/mysqldump_test001_complete.sql && echo " - done."
errCode=$?; ret[((index++))]=$errCode

printf "Create dump: mysqldump_test002.sql"
$MYSQLDUMP_CMD test002 \
    --no-autocommit \
    --skip-extended-insert \
    --complete-insert \
    --hex-blob \
    --default-character-set=utf8mb4 \
    > output/mysqldump_test002.sql && echo " - done."
errCode=$?; ret[((index++))]=$errCode

printf "Create dump: mysqldump_test005.sql"
$MYSQLDUMP_CMD test005 \
    --no-autocommit \
    --skip-extended-insert \
    --hex-blob \
    > output/mysqldump_test005.sql && echo " - done."
errCode=$?; ret[((index++))]=$errCode

printf "Create dump: mysqldump_test012.sql"
$MYSQLDUMP_CMD test012 \
    --no-autocommit \
    --skip-extended-insert \
    --hex-blob \
    --events \
    --routines \
    > output/mysqldump_test012.sql && echo " - done."
errCode=$?; ret[((index++))]=$errCode

printf "Create dump: mysqldump_test013.sql"
$MYSQLDUMP_CMD test001 \
    --no-autocommit \
    --skip-extended-insert \
    --hex-blob \
    --insert-ignore \
    > output/mysqldump_test013.sql && echo " - done."
errCode=$?; ret[((index++))]=$errCode

printf "\nRun mysqldump with PHP:\n\n"
php test.php $HOST || { echo "ERROR running test.php" && exit -1; }
errCode=$?; ret[((index++))]=$errCode

printf "\nImport generated SQL dumps...\n\n"

printf "Import mysqldump-php_test001.sql"
$MYSQL_CMD test001 < output/mysqldump-php_test001.sql && echo " - done."
errCode=$?; ret[((index++))]=$errCode
printf "Import mysqldump-php_test002.sql"
$MYSQL_CMD test002 < output/mysqldump-php_test002.sql && echo " - done."
errCode=$?; ret[((index++))]=$errCode
printf "Import mysqldump-php_test005.sql"
$MYSQL_CMD test005 < output/mysqldump-php_test005.sql && echo " - done."
errCode=$?; ret[((index++))]=$errCode
printf "Import mysqldump-php_test006.sql"
$MYSQL_CMD test006b < output/mysqldump-php_test006.sql && echo " - done."
errCode=$?; ret[((index++))]=$errCode
printf "Import mysqldump-php_test009.sql"
$MYSQL_CMD test009 < output/mysqldump-php_test009.sql && echo " - done."
errCode=$?; ret[((index++))]=$errCode

printf "\nRun checksum tests:\n\n"

printf "Create checksum: mysqldump-php_test001.checksum"
checksum_test001 > output/mysqldump-php_test001.checksum && echo " - done."
printf "Create checksum: mysqldump-php_test002.checksum"
checksum_test002 > output/mysqldump-php_test002.checksum && echo " - done."
printf "Create checksum: mysqldump-php_test005.checksum"
checksum_test005 > output/mysqldump-php_test005.checksum && echo " - done."

printf "\nCreate filtered sql files:\n\n"

cat test001.src.sql | grep ^INSERT > output/test001.filtered.sql && echo "Created test001.filtered.sql"
cat test002.src.sql | grep ^INSERT > output/test002.filtered.sql && echo "Created test002.filtered.sql"
cat test005.src.sql | grep ^INSERT > output/test005.filtered.sql && echo "Created test005.filtered.sql"
cat test008.src.sql | grep FOREIGN > output/test008.filtered.sql && echo "Created test008.filtered.sql"
cat test010.src.sql | grep CREATE | grep EVENT > output/test010.filtered.sql && echo "Created test010.filtered.sql"

if [[ $major -eq 5 && $medium -ge 7 ]]; then
    # test virtual column support, with simple inserts forced to complete (a) and complete inserts (b)
    cat test011.src.sql | egrep "INSERT|GENERATED" > output/test011.filtered.sql && echo "Created test011.filtered.sql"
else
    echo "test011 disabled, only valid for mysql server version > 5.7.0"
fi

cat output/mysqldump_test001.sql | grep ^INSERT > output/mysqldump_test001.filtered.sql && echo "Created mysqldump_test001.filtered.sql"
cat output/mysqldump_test001_complete.sql | grep ^INSERT > output/mysqldump_test001_complete.filtered.sql && echo "Created mysqldump_test001_complete.filtered.sql"
cat output/mysqldump_test002.sql | grep ^INSERT > output/mysqldump_test002.filtered.sql && echo "Created mysqldump_test002.filtered.sql"
cat output/mysqldump_test005.sql | grep ^INSERT > output/mysqldump_test005.filtered.sql && echo "Created mysqldump_test005.filtered.sql"
cat output/mysqldump_test012.sql | grep -E -e '50001 (CREATE|VIEW)' -e '50013 DEFINER' -e 'TRIGGER' -e 'FUNCTION' -e 'PROCEDURE' | grep -v -e 'TABLE' -e 'CREATE VIEW' > output/mysqldump_test012.filtered.sql && echo "Created mysqldump_test012.filtered.sql"
cat output/mysqldump_test013.sql | grep "INSERT" > output/mysqldump_test013.filtered.sql && echo "Created mysqldump_test013.filtered.sql"
cat output/mysqldump-php_test001.sql | grep ^INSERT > output/mysqldump-php_test001.filtered.sql && echo "Created mysqldump-php_test001.filtered.sql"
cat output/mysqldump-php_test001_complete.sql | grep ^INSERT > output/mysqldump-php_test001_complete.filtered.sql && echo "Created mysqldump-php_test001_complete.filtered.sql"
cat output/mysqldump-php_test002.sql | grep ^INSERT > output/mysqldump-php_test002.filtered.sql && echo "Created mysqldump-php_test002.filtered.sql"
cat output/mysqldump-php_test005.sql | grep ^INSERT > output/mysqldump-php_test005.filtered.sql && echo "Created mysqldump-php_test005.filtered.sql"
cat output/mysqldump-php_test008.sql | grep FOREIGN > output/mysqldump-php_test008.filtered.sql && echo "Created mysqldump-php_test008.filtered.sql"
cat output/mysqldump-php_test010.sql | grep CREATE | grep EVENT > output/mysqldump-php_test010.filtered.sql && echo "Created mysqldump-php_test010.filtered.sql"

if [[ $major -eq 5 && $medium -ge 7 ]]; then
    # test virtual column support, with simple inserts forced to complete (a) and complete inserts (b)
    cat output/mysqldump-php_test011a.sql | egrep "INSERT|GENERATED" > output/mysqldump-php_test011a.filtered.sql && echo "Created mysqldump-php_test011a.filtered.sql"
    cat output/mysqldump-php_test011b.sql | egrep "INSERT|GENERATED" > output/mysqldump-php_test011b.filtered.sql && echo "Created mysqldump-php_test011b.filtered.sql"
else
    echo "test011 disabled, only valid for mysql server version > 5.7.0"
fi

cat output/mysqldump-php_test012.sql | grep -E -e '50001 (CREATE|VIEW)' -e '50013 DEFINER' -e 'CREATE.*TRIGGER' -e 'FUNCTION' -e 'PROCEDURE' > output/mysqldump-php_test012.filtered.sql && echo "Created mysqldump-php_test012.filtered.sql"
cat output/mysqldump-php_test013.sql | grep INSERT > output/mysqldump-php_test013.filtered.sql && echo "Created mysqldump-php_test013.filtered.sql"

printf "\nRun diff tests:\n\n"

test="Test#$index diff test001.filtered.sql mysqldump_test001.filtered.sql"
diff output/test001.filtered.sql output/mysqldump_test001.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

#
# THERE IS DIFF HERE!
#
test="Test#$index diff mysqldump_test001_complete.filtered.sql mysqldump-php_test001_complete.filtered.sql"
diff output/mysqldump_test001_complete.filtered.sql output/mysqldump-php_test001_complete.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

test="Test#$index diff test002.filtered.sql mysqldump_test002.filtered.sql"
diff output/test002.filtered.sql output/mysqldump_test002.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

test="Test#$index diff test001.filtered.sql mysqldump-php_test001.filtered.sql"
diff output/test001.filtered.sql output/mysqldump-php_test001.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

test="Test#$index diff test002.filtered.sql mysqldump-php_test002.filtered.sql"
diff output/test002.filtered.sql output/mysqldump-php_test002.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

test="Test#$index diff test001.src.checksum mysqldump-php_test001.checksum"
diff output/test001.src.checksum output/mysqldump-php_test001.checksum
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

test="Test#$index diff test002.src.checksum mysqldump-php_test002.checksum"
diff output/test002.src.checksum output/mysqldump-php_test002.checksum
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

test="Test#$index diff test005.src.checksum mysqldump-php_test005.checksum"
diff output/test005.src.checksum output/mysqldump-php_test005.checksum
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

test="Test#$index diff mysqldump_test005.filtered.sql mysqldump-php_test005.filtered.sql"
diff output/mysqldump_test005.filtered.sql output/mysqldump-php_test005.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

test="Test#$index diff test008.filtered.sql mysqldump-php_test008.filtered.sql"
diff output/test008.filtered.sql output/mysqldump-php_test008.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

#test reset-auto-increment, make sure we don't find an AUTO_INCREMENT
test="Test#$index cat mysqldump-php_test009.sql \| grep -i ENGINE \| grep AUTO_INCREMENT"
cat output/mysqldump-php_test009.sql | grep -i ENGINE | (! grep AUTO_INCREMENT)
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

# test backup events
test="Test#$index diff test010.filtered.sql mysqldump-php_test010.filtered.sql"
diff output/test010.filtered.sql output/mysqldump-php_test010.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

if [[ $major -eq 5 && $medium -ge 7 ]]; then
    # test virtual column support, with simple inserts forced to complete (a) and complete inserts (b)
    test="Test#$index diff test011.filtered.sql mysqldump-php_test011a.filtered.sql"
    diff output/test011.filtered.sql output/mysqldump-php_test011a.filtered.sql
    errCode=$?; ret[((index++))]=$errCode
    if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi
    test="Test#$index diff test011.filtered.sql mysqldump-php_test011b.filtered.sql"
    diff output/test011.filtered.sql output/mysqldump-php_test011b.filtered.sql
    errCode=$?; ret[((index++))]=$errCode
    if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi
else
    echo test011 disabled, only valid for mysql server version > 5.7.0
fi

# Test create views, events, trigger
test="Test#$index diff mysqldump_test012.filtered.sql mysqldump-php_test012.filtered.sql"
diff output/mysqldump_test012.filtered.sql output/mysqldump-php_test012.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

# Make sure we do not find a DEFINER
test="Test#$index grep 'DEFINER' mysqldump-php_test012_no-definer.sql"
! grep 'DEFINER' output/mysqldump-php_test012_no-definer.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

# test INSERT IGNORE
test="Test#$index diff mysqldump_test013.filtered.sql mysqldump-php_test013.filtered.sql"
diff output/mysqldump_test013.filtered.sql output/mysqldump-php_test013.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo -e "\n$test\n"; fi

echo -e "\nDone $index tests\n"

retvalue=0
for i in $(seq 0 $index) ; do
    if [[ ${ret[$i]} -ne 0 ]]; then
        echo "Test#$i failed with ${ret[$i]}"
        retvalue=${ret[$i]}
    fi
    # total=$((${ret[$i]} + $total))
done

if [[ $retvalue -eq 0 ]]; then
    rm output/*.checksum 2> /dev/null
    rm output/*.filtered.sql 2> /dev/null
    rm output/mysqldump* 2> /dev/null

    echo -e "\nAll tests were successfully"
else
    echo -e "\nThere are errors. Exiting with code $retvalue"
fi

exit $retvalue

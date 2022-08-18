#!/bin/bash

major=`mysql -u travis -e "SELECT @@version\G" | grep version |awk '{print $2}' | awk -F"." '{print $1}'`
medium=`mysql -u travis -e "SELECT @@version\G" | grep version |awk '{print $2}' | awk -F"." '{print $2}'`
minor=`mysql -u travis -e "SELECT @@version\G" | grep version |awk '{print $2}' | awk -F"." '{print $3}'`

echo Testing against mysql server version $major.$medium.$minor

function checksum_test001() {
for i in 000 001 002 003 010 011 015 027 029 033 200; do
    mysql -utravis -B -e "CHECKSUM TABLE test${i}" test001 | grep -v -i checksum
done
}

function checksum_test002() {
for i in 201; do
    mysql -utravis --default-character-set=utf8mb4 -B -e "CHECKSUM TABLE test${i}" test002 | grep -v -i checksum
done
}

function checksum_test005() {
for i in 000; do
    mysql -utravis -B -e "CHECKSUM TABLE test${i}" test001 | grep -v -i checksum
done
}

for i in $(seq 0 40) ; do
    ret[$i]=0
done

index=0

mysql -utravis < test001.src.sql; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test001.src.sql"; fi

mysql -utravis --default-character-set=utf8mb4 < test002.src.sql; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test002.src.sql"; fi

mysql -utravis < test005.src.sql; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test005.src.sql"; fi

mysql -utravis < test006.src.sql; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test006.src.sql"; fi

mysql -utravis < test008.src.sql; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test008.src.sql"; fi

mysql -utravis < test009.src.sql; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test001.src.sql"; fi

mysql -utravis < test010.src.sql; errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo "error test010.src.sql"; fi

if [[ $major -eq 5 && $medium -ge 7 ]]; then
    # test virtual column support, with simple inserts forced to complete (a) and complete inserts (b)
    mysql -utravis < test011.src.sql; errCode=$?; ret[((index++))]=$errCode
else
    echo "test011 disabled, only valid for mysql server version > 5.7.0"
fi
mysql -utravis < test012.src.sql; errCode=$?; ret[((index++))]=$errCode
#mysql -utravis < test013.src.sql; errCode=$?; ret[((index++))]=$errCode

checksum_test001 > test001.src.checksum
checksum_test002 > test002.src.checksum
checksum_test005 > test005.src.checksum
mysqldump -utravis test001 \
    --no-autocommit \
    --skip-extended-insert \
    --hex-blob \
    --routines \
    > mysqldump_test001.sql
errCode=$?; ret[((index++))]=$errCode

mysqldump -utravis test001 \
    --no-autocommit \
    --skip-extended-insert \
    --complete-insert=true \
    --hex-blob \
    --routines \
    > mysqldump_test001_complete.sql
errCode=$?; ret[((index++))]=$errCode

mysqldump -utravis test002 \
    --no-autocommit \
    --skip-extended-insert \
    --complete-insert \
    --hex-blob \
    --default-character-set=utf8mb4 \
    > mysqldump_test002.sql
errCode=$?; ret[((index++))]=$errCode

mysqldump -utravis test005 \
    --no-autocommit \
    --skip-extended-insert \
    --hex-blob \
    > mysqldump_test005.sql
errCode=$?; ret[((index++))]=$errCode

mysqldump -utravis test012 \
    --no-autocommit \
    --skip-extended-insert \
    --hex-blob \
    --events \
    --routines \
    > mysqldump_test012.sql
errCode=$?; ret[((index++))]=$errCode

mysqldump -utravis test001 \
    --no-autocommit \
    --skip-extended-insert \
    --hex-blob \
    --insert-ignore \
    > mysqldump_test013.sql
errCode=$?; ret[((index++))]=$errCode

php test.php || { echo "ERROR running test.php" && exit -1; }
errCode=$?; ret[((index++))]=$errCode

mysql -utravis test001 < mysqldump-php_test001.sql
errCode=$?; ret[((index++))]=$errCode
mysql -utravis test002 < mysqldump-php_test002.sql
errCode=$?; ret[((index++))]=$errCode
mysql -utravis test005 < mysqldump-php_test005.sql
errCode=$?; ret[((index++))]=$errCode
mysql -utravis test006b < mysqldump-php_test006.sql
errCode=$?; ret[((index++))]=$errCode
mysql -utravis test009 < mysqldump-php_test009.sql
errCode=$?; ret[((index++))]=$errCode

checksum_test001 > mysqldump-php_test001.checksum
checksum_test002 > mysqldump-php_test002.checksum
checksum_test005 > mysqldump-php_test005.checksum

cat test001.src.sql | grep ^INSERT > test001.filtered.sql
cat test002.src.sql | grep ^INSERT > test002.filtered.sql
cat test005.src.sql | grep ^INSERT > test005.filtered.sql
cat test008.src.sql | grep FOREIGN > test008.filtered.sql
cat test010.src.sql | grep CREATE | grep EVENT > test010.filtered.sql

if [[ $major -eq 5 && $medium -ge 7 ]]; then
    # test virtual column support, with simple inserts forced to complete (a) and complete inserts (b)
    cat test011.src.sql | egrep "INSERT|GENERATED" > test011.filtered.sql
else
    echo "test011 disabled, only valid for mysql server version > 5.7.0"
fi

cat mysqldump_test001.sql | grep ^INSERT > mysqldump_test001.filtered.sql
cat mysqldump_test001_complete.sql | grep ^INSERT > mysqldump_test001_complete.filtered.sql
cat mysqldump_test002.sql | grep ^INSERT > mysqldump_test002.filtered.sql
cat mysqldump_test005.sql | grep ^INSERT > mysqldump_test005.filtered.sql
cat mysqldump_test012.sql | grep -E -e '50001 (CREATE|VIEW)' -e '50013 DEFINER' -e 'TRIGGER' -e 'FUNCTION' -e 'PROCEDURE' | grep -v -e 'TABLE' -e 'CREATE VIEW' > mysqldump_test012.filtered.sql
cat mysqldump_test013.sql | grep "INSERT" > mysqldump_test013.filtered.sql
cat mysqldump-php_test001.sql | grep ^INSERT > mysqldump-php_test001.filtered.sql
cat mysqldump-php_test001_complete.sql | grep ^INSERT > mysqldump-php_test001_complete.filtered.sql
cat mysqldump-php_test002.sql | grep ^INSERT > mysqldump-php_test002.filtered.sql
cat mysqldump-php_test005.sql | grep ^INSERT > mysqldump-php_test005.filtered.sql
cat mysqldump-php_test008.sql | grep FOREIGN > mysqldump-php_test008.filtered.sql
cat mysqldump-php_test010.sql | grep CREATE | grep EVENT > mysqldump-php_test010.filtered.sql

if [[ $major -eq 5 && $medium -ge 7 ]]; then
    # test virtual column support, with simple inserts forced to complete (a) and complete inserts (b)
    cat mysqldump-php_test011a.sql | egrep "INSERT|GENERATED" > mysqldump-php_test011a.filtered.sql
    cat mysqldump-php_test011b.sql | egrep "INSERT|GENERATED" > mysqldump-php_test011b.filtered.sql
else
    echo "test011 disabled, only valid for mysql server version > 5.7.0"
fi

cat mysqldump-php_test012.sql | grep -E -e '50001 (CREATE|VIEW)' -e '50013 DEFINER' -e 'CREATE.*TRIGGER' -e 'FUNCTION' -e 'PROCEDURE' > mysqldump-php_test012.filtered.sql
cat mysqldump-php_test013.sql | grep INSERT > mysqldump-php_test013.filtered.sql

test="test $index diff test001.filtered.sql mysqldump_test001.filtered.sql"
diff test001.filtered.sql mysqldump_test001.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

test="test $index diff mysqldump_test001_complete.filtered.sql mysqldump-php_test001_complete.filtered.sql"
diff mysqldump_test001_complete.filtered.sql mysqldump-php_test001_complete.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

test="test $index diff test002.filtered.sql mysqldump_test002.filtered.sql"
diff test002.filtered.sql mysqldump_test002.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

test="test $index diff test001.filtered.sql mysqldump-php_test001.filtered.sql"
diff test001.filtered.sql mysqldump-php_test001.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

test="test $index diff test002.filtered.sql mysqldump-php_test002.filtered.sql"
diff test002.filtered.sql mysqldump-php_test002.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

test="test $index diff test001.src.checksum mysqldump-php_test001.checksum"
diff test001.src.checksum mysqldump-php_test001.checksum
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

test="test $index diff test002.src.checksum mysqldump-php_test002.checksum"
diff test002.src.checksum mysqldump-php_test002.checksum
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

test="test $index diff test005.src.checksum mysqldump-php_test005.checksum"
diff test005.src.checksum mysqldump-php_test005.checksum
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

test="test $index diff mysqldump_test005.filtered.sql mysqldump-php_test005.filtered.sql"
diff mysqldump_test005.filtered.sql mysqldump-php_test005.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

test="test $index diff test008.filtered.sql mysqldump-php_test008.filtered.sql"
diff test008.filtered.sql mysqldump-php_test008.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

#test reset-auto-increment, make sure we don't find an AUTO_INCREMENT
test="test $index cat mysqldump-php_test009.sql \| grep -i ENGINE \| grep AUTO_INCREMENT"
cat mysqldump-php_test009.sql | grep -i ENGINE | (! grep AUTO_INCREMENT)
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

# test backup events
test="test $index diff test010.filtered.sql mysqldump-php_test010.filtered.sql"
diff test010.filtered.sql mysqldump-php_test010.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

if [[ $major -eq 5 && $medium -ge 7 ]]; then
    # test virtual column support, with simple inserts forced to complete (a) and complete inserts (b)
    test="test $index diff test011.filtered.sql mysqldump-php_test011a.filtered.sql"
    diff test011.filtered.sql mysqldump-php_test011a.filtered.sql
    errCode=$?; ret[((index++))]=$errCode
    if [[ $errCode -ne 0 ]]; then echo $test; fi
    test="test $index diff test011.filtered.sql mysqldump-php_test011b.filtered.sql"
    diff test011.filtered.sql mysqldump-php_test011b.filtered.sql
    errCode=$?; ret[((index++))]=$errCode
    if [[ $errCode -ne 0 ]]; then echo $test; fi
else
    echo test011 disabled, only valid for mysql server version > 5.7.0
fi

# Test create views, events, trigger
test="test $index diff mysqldump_test012.filtered.sql mysqldump-php_test012.filtered.sql"
diff mysqldump_test012.filtered.sql mysqldump-php_test012.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

# Make sure we do not find a DEFINER
test="test $index grep 'DEFINER' mysqldump-php_test012_no-definer.sql"
! grep 'DEFINER' mysqldump-php_test012_no-definer.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

# test INSERT IGNORE
test="test $index diff mysqldump_test013.filtered.sql mysqldump-php_test013.filtered.sql"
diff mysqldump_test013.filtered.sql mysqldump-php_test013.filtered.sql
errCode=$?; ret[((index++))]=$errCode
if [[ $errCode -ne 0 ]]; then echo $test; fi

echo "Done $index tests"

retvalue=0
for i in $(seq 0 $index) ; do
    if [[ ${ret[$i]} -ne 0 ]]; then
        echo "test $i returned ${ret[$i]}"
        retvalue=${ret[$i]}
    fi
    # total=$((${ret[$i]} + $total))
done

echo "Exiting with code $retvalue"

if [[ $retvalue -eq 0 ]]; then
    rm *.checksum 2> /dev/null
    rm *.filtered.sql 2> /dev/null
    rm mysqldump* 2> /dev/null
fi

exit $retvalue

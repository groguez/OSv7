# Start PHP script
printenv
/usr/local/bin/php -d variables_order=EGPCS /var/www/html/index.php background index >/dev/null 2>&1 &
# Start Apache
exec apache2-foreground
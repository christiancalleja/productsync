# Bash ready for cronjob

# Copy files from dropbox directory
cp -a /root/Dropbox/CATALOGUE/. /var/www/[YOUR-SITE-NAME]/wp-content/uploads/catalogue/


# Curl the magic at 4:05am every day 
5 4 * * *  /usr/bin/curl --silent http://[YOUR-SITE-URL]/wp-json/productsync/v1/syncnow &>/dev/null


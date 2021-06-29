echo --------------Schutdown Apache------------------------------------------
#service apache2 stop

echo ------------ install latest packages-------------
php composer.phar install
echo --------------------------------------------------------------------------
echo ----------------Create Database-------------------------------------------
echo ----------------Please Backup your database-------------------------------
echo --------------------------------------------------------------------------
php bin/console cache:clear
php bin/console doctrine:migrations:migrate
echo --------------------------------------------------------------------------
echo -----------------Clear Cache----------------------------------------------
echo --------------------------------------------------------------------------
php bin/console cache:clear
php bin/console cache:warmup
echo --------------------------------------------------------------------------
echo ----------------Setting Permission-----------------------------------------
echo --------------------------------------------------------------------------
chown -R www-data:www-data var/cache
chmod -R 775 var/cache
echo --------------------------------------------------------------------------
echo ----------------Create Upload Folder and Set permissions------------------
echo --------------------------------------------------------------------------
chown -R www-data:www-data public/uploads/images
chmod -R 775 public/uploads/images
echo --------------------------------------------------------------------------
echo -----------------------Install NPM and Assets----------------------------
echo --------------------------------------------------------------------------
npm install
npm run build
echo --------------------------------------------------------------------------
echo -----------------------Updated the Jitsi-Admin correct------------------
echo --------------------------------------------------------------------------




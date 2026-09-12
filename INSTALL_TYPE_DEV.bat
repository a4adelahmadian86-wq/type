@echo off
setlocal EnableExtensions EnableDelayedExpansion
title FARAST TYPE - DEV INSTALLER

cls
echo ============================================================
echo             FARAST TYPE - DEV ENVIRONMENT
echo ============================================================
echo.
echo MAIN PROJECT:
echo C:\xampp\htdocs\digitalshop
echo.
echo DEV PROJECT:
echo %USERPROFILE%\Desktop\type
echo.
echo BRANCH:
echo main
echo.
echo MAIN PROJECT WILL NOT BE MODIFIED.
echo MAIN DATABASE WILL ONLY BE READ.
echo ============================================================
echo.

set "MAIN=C:\xampp\htdocs\digitalshop"
set "DEV=%USERPROFILE%\Desktop\type"
set "TOKEN_FILE=%USERPROFILE%\Desktop\github_token.txt"
set "GEMINI_KEY_FILE=%USERPROFILE%\Desktop\gemini_api_key.txt"
set "OWNER=a4adelahmadian86-wq"
set "REPO=type"
set "BRANCH=main"
set "MYSQLBIN=C:\xampp\mysql\bin"
set "MYSQL=%MYSQLBIN%\mysql.exe"
set "MYSQLDUMP=%MYSQLBIN%\mysqldump.exe"
set "DEV_DB=digitalshop_dev"
set "PORT=8001"
set "GITHUB_API=https://api.github.com/repos/%OWNER%/%REPO%/branches/%BRANCH%"
set "ZIP_URL=https://github.com/%OWNER%/%REPO%/archive/refs/heads/%BRANCH%.zip"

REM ============================================================
REM 1 - MAIN PROJECT
REM ============================================================
echo [1/12] Checking MAIN project...
if not exist "%MAIN%\artisan" (
    echo.
    echo ERROR: MAIN Laravel project not found:
    echo %MAIN%
    pause
    exit /b 1
)
echo OK - MAIN project found.
echo.

REM ============================================================
REM 2 - GITHUB TOKEN
REM ============================================================
echo [2/12] Checking GitHub token...
if not exist "%TOKEN_FILE%" (
    echo.
    echo ERROR: github_token.txt not found:
    echo %TOKEN_FILE%
    pause
    exit /b 1
)
set "GITHUB_TOKEN="
for /f "usebackq delims=" %%T in ("%TOKEN_FILE%") do if not defined GITHUB_TOKEN set "GITHUB_TOKEN=%%T"
if not defined GITHUB_TOKEN (
    echo ERROR: GitHub token file is empty.
    pause
    exit /b 1
)
echo OK - GitHub token found.
echo Token value will NOT be displayed.
echo.

REM ============================================================
REM 3 - POWERSHELL
REM ============================================================
echo [3/12] Checking PowerShell...
where powershell.exe >nul 2>&1
if errorlevel 1 (
    echo ERROR: PowerShell was not found.
    pause
    exit /b 1
)
echo OK - PowerShell found.
echo.

REM ============================================================
REM 4 - GITHUB BRANCH
REM ============================================================
echo [4/12] Checking GitHub branch...
echo.
echo Repository: %OWNER%/%REPO%
echo Branch:     %BRANCH%
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
 "$ErrorActionPreference='Stop';" ^
 "$token=(Get-Content -LiteralPath '%TOKEN_FILE%' -Raw).Trim();" ^
 "$headers=@{'Authorization'=('Bearer '+$token);'Accept'='application/vnd.github+json';'User-Agent'='FARAST-TYPE-DEV-Updater'};" ^
 "$r=Invoke-RestMethod -Uri '%GITHUB_API%' -Headers $headers -Method Get;" ^
 "Write-Host ('BRANCH_OK|'+$r.name+'|'+$r.commit.sha);"
if errorlevel 1 (
    echo ERROR: Could not access GitHub branch.
    pause
    exit /b 1
)
echo.
echo GitHub branch is accessible.
echo.

REM ============================================================
REM 5 - PREPARE DEV DIRECTORY
REM ============================================================
echo [5/12] Preparing DEV project...
if not exist "%DEV%" mkdir "%DEV%"
if not exist "%DEV%" (
    echo ERROR: Could not create DEV directory.
    pause
    exit /b 1
)
echo DEV directory:
echo %DEV%
echo.

REM ============================================================
REM 6 - DOWNLOAD BRANCH
REM ============================================================
echo [6/12] Downloading latest branch from GitHub...
echo.
set "ZIP=%TEMP%\farast-type-!RANDOM!!RANDOM!.zip"
set "EXTRACT=%TEMP%\farast-type-extract-!RANDOM!!RANDOM!"
if exist "!ZIP!" del /f /q "!ZIP!" >nul 2>&1
if exist "!EXTRACT!" rmdir /s /q "!EXTRACT!" >nul 2>&1
mkdir "!EXTRACT!" >nul 2>&1
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
 "$ErrorActionPreference='Stop';" ^
 "$token=(Get-Content -LiteralPath '%TOKEN_FILE%' -Raw).Trim();" ^
 "$headers=@{'Authorization'=('Bearer '+$token);'Accept'='application/vnd.github+json';'User-Agent'='FARAST-TYPE-DEV-Updater'};" ^
 "Invoke-WebRequest -Uri '%ZIP_URL%' -Headers $headers -OutFile '%ZIP%';"
if errorlevel 1 (
    echo ERROR: GitHub branch download failed.
    pause
    exit /b 1
)
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
 "$ErrorActionPreference='Stop'; Expand-Archive -LiteralPath '%ZIP%' -DestinationPath '%EXTRACT%' -Force;"
if errorlevel 1 (
    echo ERROR: Could not extract GitHub ZIP.
    pause
    exit /b 1
)
set "SOURCE="
for /d %%D in ("!EXTRACT!\*") do if exist "%%D\artisan" set "SOURCE=%%D"
if not defined SOURCE (
    echo ERROR: Laravel project was not found inside the GitHub ZIP.
    pause
    exit /b 1
)
echo Source:
echo !SOURCE!
echo.

REM ============================================================
REM PRESERVE DEV ENV + STORAGE
REM ============================================================
set "OLD_ENV=!TEMP!\farast-old-env-!RANDOM!.env"
set "OLD_STORAGE=!TEMP!\farast-old-storage-!RANDOM!"
if exist "%DEV%\.env" copy /y "%DEV%\.env" "!OLD_ENV!" >nul
if exist "%DEV%\storage" (
    mkdir "!OLD_STORAGE!" >nul 2>&1
    robocopy "%DEV%\storage" "!OLD_STORAGE!" /E /COPY:DAT /R:1 /W:1 /NFL /NDL /NJH /NJS >nul
)

REM ============================================================
REM 7 - COPY SOURCE
REM ============================================================
echo [7/12] Updating DEV project files...
robocopy "!SOURCE!" "%DEV%" /E /COPY:DAT /DCOPY:DAT /R:2 /W:1 /NFL /NDL /NP /XD ".git" "node_modules" "vendor" "storage" >nul
if errorlevel 8 (
    echo ERROR: DEV project copy failed.
    pause
    exit /b 1
)
if exist "!OLD_STORAGE!" robocopy "!OLD_STORAGE!" "%DEV%\storage" /E /COPY:DAT /DCOPY:DAT /R:1 /W:1 /NFL /NDL /NJH /NJS >nul
if exist "!OLD_ENV!" copy /y "!OLD_ENV!" "%DEV%\.env" >nul
if not exist "%DEV%\.env" if exist "%MAIN%\.env" copy /y "%MAIN%\.env" "%DEV%\.env" >nul
if not exist "%DEV%\.env" (
    echo ERROR: DEV .env could not be created.
    pause
    exit /b 1
)
echo DEV source files updated.
echo.

REM ============================================================
REM 8 - SAFE DEV ENVIRONMENT
REM ============================================================
echo [8/12] Creating safe DEV environment...
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
 "$p='%DEV%\.env';" ^
 "$s=Get-Content -LiteralPath $p -Raw;" ^
 "function Set-Env([string]$k,[string]$v){$pattern='(?m)^'+[regex]::Escape($k)+'=.*$';if($s -match $pattern){$script:s=[regex]::Replace($script:s,$pattern,($k+'='+$v))}else{$script:s=$script:s.TrimEnd()+[Environment]::NewLine+($k+'='+$v)+[Environment]::NewLine}};" ^
 "Set-Env 'APP_ENV' 'local'; Set-Env 'APP_DEBUG' 'true'; Set-Env 'APP_URL' 'http://127.0.0.1:%PORT%'; Set-Env 'DB_HOST' '127.0.0.1'; Set-Env 'DB_PORT' '3306'; Set-Env 'DB_DATABASE' '%DEV_DB%'; Set-Env 'CACHE_STORE' 'file'; Set-Env 'SESSION_DRIVER' 'file'; Set-Env 'QUEUE_CONNECTION' 'sync'; Set-Env 'GEMINI_MODEL' 'gemini-3.8-flash';" ^
 "[IO.File]::WriteAllText($p,$s,(New-Object System.Text.UTF8Encoding($false)));"

REM Optional: if Desktop\gemini_api_key.txt exists, use it without printing it.
if exist "%GEMINI_KEY_FILE%" (
    set "GEMINI_KEY="
    for /f "usebackq delims=" %%K in ("%GEMINI_KEY_FILE%") do if not defined GEMINI_KEY set "GEMINI_KEY=%%K"
    if defined GEMINI_KEY powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
     "$p='%DEV%\.env';$s=Get-Content -LiteralPath $p -Raw;$pattern='(?m)^GEMINI_API_KEY=.*$';$v=$env:GEMINI_KEY;if($s -match $pattern){$s=[regex]::Replace($s,$pattern,('GEMINI_API_KEY='+$v))}else{$s=$s.TrimEnd()+[Environment]::NewLine+'GEMINI_API_KEY='+$v+[Environment]::NewLine};[IO.File]::WriteAllText($p,$s,(New-Object System.Text.UTF8Encoding($false)))"
)

echo DEV .env configured.
echo DEV DATABASE: %DEV_DB%
echo.

REM ============================================================
REM 9 - PHP + COMPOSER
REM ============================================================
echo [9/12] Checking PHP and Composer...
if not exist "C:\xampp\php\php.exe" (
    echo ERROR: C:\xampp\php\php.exe was not found.
    pause
    exit /b 1
)
set "PATH=C:\xampp\php;%PATH%"
php -v
echo.
where composer.bat >nul 2>&1
if errorlevel 1 where composer.exe >nul 2>&1
if errorlevel 1 (
    echo ERROR: Composer was not found.
    pause
    exit /b 1
)

cd /d "%DEV%"
set "COMPOSER_IPRESOLVE=4"
set "COMPOSER_PROCESS_TIMEOUT=900"
set "COMPOSER_NO_INTERACTION=1"
set "COMPOSER_NO_AUDIT=1"
set "COMPOSER_OK=0"
for /L %%R in (1,1,3) do (
    if "!COMPOSER_OK!"=="0" (
        echo.
        echo Composer attempt %%R of 3...
        call composer install --no-interaction --prefer-dist --no-progress
        if not errorlevel 1 set "COMPOSER_OK=1"
        if "!COMPOSER_OK!"=="0" timeout /t 3 /nobreak >nul
    )
)
if "!COMPOSER_OK!"=="0" if not exist "%DEV%\vendor\autoload.php" (
    echo.
    echo ERROR: Composer could not install the dependencies.
    echo The DEV project cannot start without vendor\autoload.php.
    echo Check your internet/SSL connection and run this installer again.
    pause
    exit /b 1
)
echo Composer dependencies are ready.
echo.

REM ============================================================
REM MYSQL
REM ============================================================
echo Checking XAMPP MySQL...
if not exist "%MYSQL%" (
    echo ERROR: MySQL executable not found: %MYSQL%
    pause
    exit /b 1
)
if not exist "%MYSQLDUMP%" (
    echo ERROR: mysqldump executable not found: %MYSQLDUMP%
    pause
    exit /b 1
)
echo MySQL tools found.
echo.

REM ============================================================
REM DETECT MAIN DATABASE FROM MAIN .ENV
REM ============================================================
set "MAIN_DB="
if exist "%MAIN%\.env" (
    for /f "tokens=1,* delims==" %%A in ('findstr /b /c:"DB_DATABASE=" "%MAIN%\.env" 2^>nul') do if not defined MAIN_DB set "MAIN_DB=%%B"
)
set "MAIN_DB=%MAIN_DB:"=%"
if not defined MAIN_DB set "MAIN_DB=digitalshop"

echo MAIN DATABASE DETECTED:
echo %MAIN_DB%
echo.

REM ============================================================
REM 10 - DEV DATABASE
REM ============================================================
echo [10/12] Preparing DEV database...
"%MYSQL%" -u root -e "CREATE DATABASE IF NOT EXISTS %DEV_DB% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 (
    echo ERROR: Could not create/access DEV database.
    echo Make sure MySQL is running in XAMPP.
    pause
    exit /b 1
)
echo DEV database is ready: %DEV_DB%
echo.

set "MAIN_EXISTS="
for /f "usebackq delims=" %%D in (`"%MYSQL%" -u root -N -B -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='%MAIN_DB%';" 2^>nul`) do set "MAIN_EXISTS=%%D"
if /I not "!MAIN_EXISTS!"=="%MAIN_DB%" (
    echo.
    echo ERROR: MAIN database does not exist in this XAMPP MySQL server.
    echo MAIN DATABASE REQUESTED: %MAIN_DB%
    echo.
    echo The installer will NOT guess another database and will NOT modify anything.
    echo.
    pause
    exit /b 1
)

set "DB_MARKER=%DEV%\.main_database_imported"
if not exist "!DB_MARKER!" (
    echo Main database has not yet been copied to DEV.
    echo Creating READ-ONLY dump of MAIN database...
    set "MAIN_SQL=!TEMP!\farast-main-!RANDOM!!RANDOM!.sql"
    if exist "!MAIN_SQL!" del /f /q "!MAIN_SQL!" >nul 2>&1
    "%MYSQLDUMP%" -u root "%MAIN_DB%" --routines --triggers --single-transaction --result-file="!MAIN_SQL!"
    if errorlevel 1 (
        echo ERROR: Could not dump MAIN database.
        echo MAIN DATABASE WAS NOT MODIFIED.
        pause
        exit /b 1
    )
    if not exist "!MAIN_SQL!" (
        echo ERROR: Database dump file was not created.
        pause
        exit /b 1
    )
    echo MAIN database dump created successfully.
    echo Importing into DEV database...
    "%MYSQL%" -u root "%DEV_DB%" < "!MAIN_SQL!"
    if errorlevel 1 (
        echo ERROR: DEV database import failed.
        echo MAIN DATABASE WAS NOT MODIFIED.
        pause
        exit /b 1
    )
    echo imported > "!DB_MARKER!"
    del /f /q "!MAIN_SQL!" >nul 2>&1
    echo MAIN DATABASE COPIED TO DEV SUCCESSFULLY.
) else (
    echo DEV database has already been initialized.
    echo MAIN database will NOT be copied again.
)
echo.

REM ============================================================
REM 11 - LARAVEL
REM ============================================================
echo [11/12] Preparing Laravel...
cd /d "%DEV%"
php artisan key:generate --force
if errorlevel 1 (
    echo ERROR: Could not generate DEV APP_KEY.
    pause
    exit /b 1
)
php artisan optimize:clear
if errorlevel 1 echo WARNING: optimize:clear returned an error. Continuing...
php artisan migrate --force
if errorlevel 1 (
    echo.
    echo ERROR: DEV migrations failed.
    echo MAIN DATABASE WAS NOT TOUCHED.
    pause
    exit /b 1
)
if exist "%DEV%\public" php artisan storage:link >nul 2>&1

echo.
echo Checking Node.js / NPM...
where npm.cmd >nul 2>&1
if errorlevel 1 (
    echo WARNING: npm was not found. Front-end build was skipped.
) else (
    if exist "%DEV%\package-lock.json" (
        call npm ci
        if errorlevel 1 call npm install
    ) else (
        call npm install
    )
)

if exist "!ZIP!" del /f /q "!ZIP!" >nul 2>&1
if exist "!EXTRACT!" rmdir /s /q "!EXTRACT!" >nul 2>&1
if exist "!OLD_ENV!" del /f /q "!OLD_ENV!" >nul 2>&1
if exist "!OLD_STORAGE!" rmdir /s /q "!OLD_STORAGE!" >nul 2>&1

REM ============================================================
REM 12 - START
REM ============================================================
echo.
echo [12/12] Starting DEV server...
echo.
echo ============================================================
echo              DEV ENVIRONMENT READY
echo ============================================================
echo.
echo PROJECT:  %DEV%
echo BRANCH:   %BRANCH%
echo DATABASE: %DEV_DB%
echo URL:      http://127.0.0.1:%PORT%
echo.
echo MAIN PROJECT: C:\xampp\htdocs\digitalshop
echo MAIN PROJECT WAS NOT MODIFIED.
echo MAIN DATABASE WAS ONLY READ.
echo ============================================================
echo.
echo Starting Laravel...
echo Press Ctrl+C to stop the server.
echo.
cd /d "%DEV%"
php artisan serve --host=127.0.0.1 --port=%PORT%

echo.
echo ============================================================
echo Laravel server stopped.
echo ============================================================
pause
endlocal

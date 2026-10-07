# How to Run the Adishiv Hotel System on Your Computer

If you are trying to run this project and getting stuck, follow these steps exactly. This guide assumes you are using **Windows** and have **XAMPP** installed.

---

## Step 1: Install XAMPP (If you don't have it)
1. Download **XAMPP for Windows** from [apachefriends.org](https://www.apachefriends.org/index.html).
2. Install it in the default location (`C:\xampp`).

---

## Step 2: Start the Database
*This is the most common mistake! The website needs the database running to work.*
1. Open the **XAMPP Control Panel** (Search for XAMPP in your Windows Start Menu).
2. Click the **Start** button next to **MySQL**. 
3. *Note: You do NOT need to start Apache.*

---

## Step 3: Set Up the Environment File
The website looks for a file named `.env` to know how to connect to the database.
1. Open the project folder (`Hotel B&M System`).
2. Find the file named `.env.example`.
3. Copy it, and rename the copy to exactly **`.env`**
   *(Make sure Windows didn't name it `.env.txt`. It must be just `.env`)*.

---

## Step 4: Import the Database
We need to create the database and fill it with the hotel's rooms. We will use full paths so it works even if your computer isn't configured perfectly.

1. Open **Command Prompt** (cmd.exe).
2. Type `cd ` followed by the path to where you saved the project, and press Enter. For example:
   ```cmd
   cd C:\Users\YourName\Desktop\Hotel B&M System
   ```
3. Run this command to create the empty database:
   ```cmd
   C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS adishiv_hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```
4. Run this command to build the tables:
   ```cmd
   C:\xampp\mysql\bin\mysql.exe -u root adishiv_hotel < database\schema.sql
   ```
5. Run this command to add the rooms and data:
   ```cmd
   C:\xampp\mysql\bin\mysql.exe -u root adishiv_hotel < database\seed.sql
   ```

---

## Step 5: Create Your Admin Account
You need an admin account to log into the staff portal.
1. Still in the same Command Prompt window, run:
   ```cmd
   C:\xampp\php\php.exe bin\create-admin.php
   ```
2. It will ask you for an email and a password. Type them in and press Enter. Remember these!

---

## Step 6: Start the Website!
Now you turn on the server.
1. Run this command:
   ```cmd
   C:\xampp\php\php.exe -S localhost:8000
   ```
2. **Leave this Command Prompt window open!** If you close it, the website turns off.
3. Open your web browser (Chrome, Edge, etc.) and go to:
   * **Main Website:** [http://localhost:8000](http://localhost:8000)
   * **Admin Portal:** [http://localhost:8000/admin/login.php](http://localhost:8000/admin/login.php)

---

## 🛠️ Common Errors & Fixes
* **Error: "Inventory availability service is temporarily unavailable. Please try again shortly."**
  * **Fix:** The website cannot connect to the database! Open **XAMPP Control Panel** and click **Start** next to **MySQL** (Step 2). Also verify that you ran the SQL import commands (Step 4).
* **Error: "Connection refused" or blank white screen**
  * **Fix:** You forgot to start MySQL in the XAMPP Control Panel (Step 2).
* **Error: "SQLSTATE[HY000] [1049] Unknown database"**
  * **Fix:** You didn't run the database creation commands in Step 4.
* **Error: "php is not recognized as an internal or external command"**
  * **Fix:** Always use the full path `C:\xampp\php\php.exe` instead of just typing `php`.
* **Error: "Cannot find .env file"**
  * **Fix:** You didn't rename `.env.example` to `.env` (Step 3). Make sure file extensions are visible in Windows Explorer so you aren't accidentally naming it `.env.txt`.


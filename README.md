# 🔍 Hidden Item Studio

**Hidden Item Studio** is a lightweight, interactive web application designed for creating and playing "Find the Hidden Objects" puzzle games. Built with pure HTML5, CSS3, and modern JavaScript, it runs entirely in the browser without requiring any backend servers, external game engines, or third-party audio dependencies.

---

## 🌟 Key Features

### 📝 1. Editor Mode (Puzzle Creator)
* **Single-Click Hotspot Creation:** Click anywhere on the image to drop a target spot instantly.
* **Draggable & Resizable Targets:** Move hotspots by dragging their central area, or resize them using the handle on the perimeter.
* **Custom Naming & Management:** Name targets upon creation, rename existing targets, or delete them from the sidebar.
* **Percentage-Based Coordinates:** Hotspots maintain pixel-perfect precision across all screen sizes, mobile screens, and aspect ratios.

### ▶️ 2. Play Mode (Puzzle Game)
* **Interactive Guessing:** Click on the image to locate hidden objects.
* **Visual & Audio Feedback:** Correct guesses highlight the object in green with a cheerful chime tone; incorrect clicks play a soft buzzer sound.
* **Progress Tracking:** Dynamic sidebar badge updates your real-time score (`found / total`).
* **Clean Gameplay Interface:** Hotspots and edit buttons are hidden during gameplay to prevent spoilers.
* **Restart / Play Again Loop:** Play again seamlessly without needing to reload or refresh the browser.

### 🎉 3. Celebration & Victory System
* **Victory Fanfare:** A success chime triggers upon finding the final hidden item.
* **Particle Confetti:** High-performance HTML5 Canvas confetti explosion celebrates game completion.
* **Success Modal Dialog:** Displays a victory message with direct options to **"Play Again"** or **"Cancel"**.

### 🖼️ 4. Image Upload & Validation
* **Local Default Image:** Automatically loads `default.jpg` from the project root directory.
* **Custom Image Upload:** Supports `.png`, `.jpg`, and `.jpeg` formats.
* **File Type Safeguard:** Strict MIME-type and file extension validation rejects non-image formats.

### 🎨 5. User Interface & Controls
* **Responsive Layout:** Optimized card-based design tailored for mobile, tablet, and desktop screens.
* **Dark / Light Theme Toggle:** Instant theme switching with CSS variables.
* **Native Fullscreen Mode:** Seamless toggle for full-screen immersive play using the HTML5 Fullscreen API.
* **Zero External Dependencies:** Audio is dynamically synthesized using the browser's native **Web Audio API** (no missing sound files or broken audio loops).

---

## 📁 File Structure

```text
hidden-item-studio/
├── default.jpg     # Default puzzle image
├── index.html      # Complete app (HTML, CSS, JavaScript in one file)
└── README.md       # Project documentation


---

## 🗄️ Server Setup (shared PHP hosting + phpMyAdmin)

1. In phpMyAdmin, select your database and import `schema.sql`.
2. Copy `api/config.example.php` to `api/config.php` and enter your DB credentials.
3. Upload all files to your `html` directory. Ensure `assets/image/image_sets/` is writable (chmod 755/775).
4. Open the site and click **Upload**. The first password you enter (min 8 chars) becomes the admin password (stored as a bcrypt hash in the `settings` table; delete the `admin_hash` row to reset).
5. Admin: Upload image → add targets → **Save**. Images go to `assets/image/image_sets/`; the DB stores the path and target coordinates.
6. Players choose a puzzle from the dropdown (or share `?puzzle=ID`) and can only play; editing requires the admin password. Concurrent edits are detected via a version number.

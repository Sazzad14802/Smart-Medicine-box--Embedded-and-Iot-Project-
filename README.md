# Smart Medicine Box

An IoT-based Smart Medicine Box powered by an ESP32 microcontroller and a modern Laravel web application. This project ensures patients never miss a dose by providing physical alerts and a beautifully designed web interface for easy configuration.

## Features

- **Two Operating Modes:**
  - **Dose Mode:** Schedule reminders based on specific compartments (e.g., Morning Dose, Evening Dose).
  - **Medicine Mode:** Group specific compartments under a single medicine name and schedule it across multiple times.
- **Physical Alerts:** Built-in buzzer and compartment-specific LEDs guide the user exactly to the medicine they need to take.
- **Smart Sensors:** 
  - A Reed switch detects when the medicine box lid is opened.
  - IR sensors in each compartment detect when the medicine has been physically removed.
- **Missed Dose Tracking:** Automatically logs and tracks any doses that were not taken within the configured timeout period, complete with specific missed compartment numbers.
- **Real-time Web App:** A sleek, Tailwind CSS powered Laravel application to set schedules, view live box status, and monitor missed doses.

## Hardware Requirements
- ESP32 Microcontroller
- 6x IR Obstacle Sensors
- 6x LEDs
- 1x Magnetic Reed Switch (Lid sensor)
- 1x Active Buzzer

## Getting Started
1. Flash the `esp_32.ino` code to your ESP32 device using the Arduino IDE. Be sure to configure your WiFi credentials inside the script.
2. Install Laravel dependencies via `composer install` and `npm install`.
3. Set up your `.env` file and run `php artisan migrate` to build the database.
4. Start the server using `php artisan serve`.
5. Open the web app, connect to your ESP32's IP address, and configure your first schedule!

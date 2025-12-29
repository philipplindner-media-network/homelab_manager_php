# Homelab Manager (PHP)

Kurze Beschreibung
------------------
Homelab Manager ist ein in PHP geschriebenes Tool zur Verwaltung und Inventarisierung deines Homelabs. Das System verwaltet Geräte, Racks, Kabel, Patch‑Panels und Speicher und bietet einfache Überwachungs‑ und Verwaltungsfunktionen. Optional kann für lokale Abfragen ein Python‑Script gestartet werden.

Was das System verwaltet
------------------------
- Geräte (Server, Router, Switches, Appliances)
- Racks (Positionen, Höhe, Zuordnung)
- Kabel (Patchkabel, Längen, Querverbindungen)
- Patch‑Panels (Ports, Labeling)
- Speicher (Volume, Freigaben, Kapazitäten)

Features
--------
- Gerätedaten erfassen und verwalten
- Rack‑ und Verkabelungsübersicht
- Patch‑Panel‑Management
- Basis‑Monitoring / Healthchecks (Ping, Status)
- Dashboard für schnelle Übersicht
- Möglichkeit, lokale Abfragen / Scans per Python‑Script zu starten

Technische Angaben
------------------
- Sprache: PHP
- Unterstützte Webserver: Apache / Nginx
- Datenbank: MySQL / MariaDB (optional)
- Empfohlene PHP‑Version: 8.0+
- Optionales Python‑Script für lokale Abfragen (Python 3.8+ empfohlen)

Schnellstart — Installation
---------------------------
1. Repository klonen:
   ```bash
   git clone https://github.com/philipplindner-media-network/homelab_manager_php.git
   cd homelab_manager_php
   ```
2. PHP‑Abhängigkeiten (falls Composer verwendet wird):
   ```bash
   composer install
   ```
3. Konfigurationsdatei erstellen:
   - Kopiere `config/config.example.php` → `config/config.php`
   - Passe DB‑Zugangsdaten, Base‑URL und andere Einstellungen an.
4. Webserver konfigurieren:
   - DocumentRoot auf das `public/`‑Verzeichnis setzen (oder entsprechend anpassen).
5. Datenbank (optional):
   - Lege eine Datenbank an und importiere ggf. das Schema aus `db/schema.sql`.

Lokale Abfrage / Python‑Script
------------------------------
Das Projekt unterstützt eine optionale lokale Abfrage/Scan, die per Python ausgeführt wird. Beispiel:

- Beispiel‑Pfad (anpassen, falls Datei anders liegt): `scripts/local_query.py`
- Beispiel: Installation der Python‑Abhängigkeiten
  ```bash
  python3 -m venv .venv
  source .venv/bin/activate
  pip install -r scripts/requirements.txt
  ```
- Beispielaufruf:
  ```bash
  python3 scripts/local_query.py --target 192.168.1.1 --type scan
  ```
Hinweis: Die tatsächlichen Parameter und der Script‑Name können im Repository variieren. Passe den Pfad und die Optionen an deine Implementierung an.

Konfiguration
-------------
- `config/config.example.php` enthält alle konfigurierbaren Parameter.
- Für Umgebungsvariablen kannst du eine `.env`‑Lösung ergänzen oder die config‑Datei direkt anpassen.

Screenshots / Bilder (Platzhalter)
---------------------------------
Lege deine Screenshots in `docs/images/` ab. Im README verwenden wir Platzhalter‑Bilder:

- Dashboard (Platzhalter):
  ![Dashboard Placeholder](./docs/images/dashboard-placeholder.png)

- Rack‑Übersicht (Platzhalter):
  ![Racks Placeholder](./docs/images/racks-placeholder.png)

- Verkabelung / Patch‑Panel (Platzhalter):
  ![Patchpanel Placeholder](./docs/images/patchpanel-placeholder.png)

Online‑Platzhalter (nur Demo):
- `https://via.placeholder.com/1200x600?text=Dashboard+Placeholder`
- `https://via.placeholder.com/800x450?text=Racks+Placeholder`

Empfohlene Dateinamen (lokal)
- docs/images/dashboard-placeholder.png
- docs/images/racks-placeholder.png
- docs/images/patchpanel-placeholder.png

Tipps für Bilder
- Breite: 800–1400 px für volle Breite (Dashboard)
- Format: PNG oder JPG
- Seitenverhältnis: 16:9 für Screenshots

Kontribution
-----------
Beiträge sind willkommen! Ablauf:
1. Issue anlegen, wenn du ein größeres Feature planst.
2. Branch erstellen: `git checkout -b feature/mein-feature`
3. PR mit Beschreibung öffnen.

Beispiel für einen Issue‑Titel:
- Titel: "Feature: Verkabelungsvisualisierung erweitern"
- Beschreibung: Warum benötigt, Vorschlag zur Umsetzung, Bildschirmfotos/Mockups

Lizenz
------
Dieses Projekt steht unter der MIT‑Lizenz. Bitte füge eine LICENSE‑Datei hinzu (z. B. MIT) oder nutze die vorhandene [LICENSE](./LICENSE).

Kontakt
-------
Bei Fragen oder Feedback: philipplindner-media-network auf GitHub

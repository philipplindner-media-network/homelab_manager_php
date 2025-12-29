import argparse
import json
import os
import sys
import requests # <-- NEU: für API-Anfragen

# ------------------------------------------------------------------
# KONFIGURATION
# ------------------------------------------------------------------

# URL zu deiner neuen API-Datei
# ERSETZE DIESE URL durch die tatsächliche Adresse deines Homelab Managers!
HOMELAB_API_URL = 'https://_YOUR_URL_api.php'

# Name der lokalen Datei für Notizen
DATA_FILE = 'quick_data.json'

# ... (load_data, save_data Funktionen bleiben unverändert) ...
def load_data():
    """Lädt Daten aus der JSON-Datei oder gibt ein leeres Dictionary zurück."""
    if not os.path.exists(DATA_FILE):
        return {}
    try:
        with open(DATA_FILE, 'r') as f:
            return json.load(f)
    except json.JSONDecodeError:
        print(f"Fehler: '{DATA_FILE}' ist beschädigt. Erstelle eine neue leere Datei.")
        return {}
    except Exception as e:
        print(f"Fehler beim Lesen der Datei: {e}")
        return {}

def save_data(data):
    """Speichert Daten in der JSON-Datei."""
    try:
        with open(DATA_FILE, 'w') as f:
            json.dump(data, f, indent=4)
    except Exception as e:
        print(f"Fehler beim Speichern der Datei: {e}")

# ... (add_entry, get_entry, search_entries, delete_entry, list_entries Funktionen bleiben unverändert) ...
def add_entry(name, value):
    """Fügt einen neuen Datensatz hinzu oder aktualisiert einen bestehenden."""
    data = load_data()
    data[name] = value
    save_data(data)
    print(f"✅ Datensatz '{name}' wurde gespeichert/aktualisiert.")

def get_entry(name):
    """Ruft einen Datensatz ab und gibt den Wert aus."""
    data = load_data()
    if name in data:
        print(f"{name}: {data[name]}")
    else:
        print(f"❌ Fehler: Datensatz '{name}' nicht gefunden.")
        sys.exit(1)

def search_entries(term):
    """Sucht nach Datensätzen, die den Suchbegriff enthalten (schlüssel oder wert)."""
    data = load_data()
    found = False
    print(f"🔍 Ergebnisse für '{term}' in lokalen Notizen:")
    for name, value in data.items():
        if term.lower() in name.lower() or term.lower() in value.lower():
            print(f"  - {name}: {value}")
            found = True
    if not found:
        print("  (Keine lokalen Ergebnisse gefunden)")

def delete_entry(name):
    """Löscht einen Datensatz."""
    data = load_data()
    if name in data:
        del data[name]
        save_data(data)
        print(f"🗑️ Datensatz '{name}' wurde gelöscht.")
    else:
        print(f"❌ Fehler: Datensatz '{name}' nicht gefunden.")
        sys.exit(1)

def list_entries():
    """Listet alle gespeicherten Schlüssel auf."""
    data = load_data()
    if data:
        print("📋 Gespeicherte Schlüssel:")
        for name in sorted(data.keys()):
            print(f"  - {name}")
    else:
        print("Die Datenbank ist leer.")

# ------------------------------------------------------------------
# NEUE AKTION: HOMELAB API ZUGRIFF
# ------------------------------------------------------------------

def search_homelab_devices(term):
    """Sucht Geräte in der Homelab-Datenbank über die PHP-API."""
    try:
        params = {'action': 'search_devices', 'query': term}
        response = requests.get(HOMELAB_API_URL, params=params)
        response.raise_for_status() # Löst Ausnahme für schlechte Statuscodes (4xx oder 5xx)
        
        result = response.json()

        if result.get('status') == 'success':
            devices = result.get('data', [])
            
            print(f"🌐 Ergebnisse für '{term}' im Homelab Manager:")
            if not devices:
                print("  (Keine Geräte in der Datenbank gefunden)")
                return

            for device in devices:
                name = device.get('name', 'N/A')
                ip = device.get('ip_address', 'N/A')
                mac = device.get('mac_address', 'N/A')
                desc = device.get('notes', 'N/A')
                barc = device.get('barcode_number', 'N/A')

                print("-" * 30)
                print(f"  Name: {name}")
                print(f"  IP:   {ip}")
                print(f"  MAC:  {mac}")
                print(f"  Info: {desc}")
                print(f"  Barcode Nummer: {barc}")
            print("-" * 30)

        else:
            print(f"❌ API-Fehler: {result.get('message', 'Unbekannter Fehler.')}")

    except requests.exceptions.ConnectionError:
        print(f"⚠️ Verbindungsfehler: Konnte keine Verbindung zu {HOMELAB_API_URL} herstellen.")
        print("Stelle sicher, dass der Webserver läuft und die URL korrekt ist.")
    except requests.exceptions.RequestException as e:
        print(f"❌ Allgemeiner API-Fehler: {e}")
    except json.JSONDecodeError:
        print(f"❌ API-Fehler: Ungültige JSON-Antwort vom Server erhalten.")


# ------------------------------------------------------------------
# HAUPT-LOGIK (ARGPARSE)
# ------------------------------------------------------------------

def main():
    parser = argparse.ArgumentParser(
        description="Ein schnelles Python-Tool zum Speichern lokaler Notizen und Suchen in der Homelab-Datenbank."
    )
    subparsers = parser.add_subparsers(dest='command', required=True)

    # Befehle für lokale Notizen
    parser_add = subparsers.add_parser('add', help='Fügt lokalen Datensatz hinzu.')
    parser_add.add_argument('name', type=str, help='Der Name (Schlüssel) des Datensatzes.')
    parser_add.add_argument('value', type=str, help='Der Wert des Datensatzes.')

    parser_get = subparsers.add_parser('get', help='Ruft lokalen Wert ab.')
    parser_get.add_argument('name', type=str, help='Der Schlüssel des Datensatzes.')

    parser_search = subparsers.add_parser('search', help='Sucht in lokalen Notizen.')
    parser_search.add_argument('term', type=str, help='Der Suchbegriff.')

    parser_delete = subparsers.add_parser('delete', help='Löscht lokalen Datensatz.')
    parser_delete.add_argument('name', type=str, help='Der Schlüssel des Datensatzes.')
    
    subparsers.add_parser('list', help='Listet alle lokalen Schlüssel auf.')
    
    # NEUER Befehl: HLAB (Homelab Search)
    parser_hlab = subparsers.add_parser('hlab', help='Sucht Geräte im Homelab Manager über die API.')
    parser_hlab.add_argument('term', type=str, help='Der Suchbegriff (Name, IP, oder MAC).')


    args = parser.parse_args()

    if args.command == 'add':
        add_entry(args.name, args.value)
    elif args.command == 'get':
        get_entry(args.name)
    elif args.command == 'search':
        search_entries(args.term)
    elif args.command == 'delete':
        delete_entry(args.name)
    elif args.command == 'list':
        list_entries()
    elif args.command == 'hlab':
        search_homelab_devices(args.term)

if __name__ == '__main__':
    main()

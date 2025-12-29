#!/bin/bash

# --- 1. Sudo/Root-Rechte überprüfen ---
if [ "$EUID" -ne 0 ]; then
  echo "Dieses Skript muss mit Root-Rechten ausgeführt werden."
  echo "Bitte starte es erneut mit: sudo /bin/bash -c \"\$(curl -sS https://_yurURL_/C/collect_hardware.sh)\" -- <BARCODE> <KEY>"
  exit 1
fi

# --- 2. Parameter überprüfen ---
if [ "$#" -ne 2 ]; then
  echo "Fehler: Ungültige Anzahl an Parametern."
  echo "Verwendung: $0 <BARCODE_NUMMER> <GEHEIMER_SCHLUESSEL>"
  exit 1
fi

# --- Konfiguration aus Parametern übernehmen ---
BARCODE_NUMBER="$1"
SECRET_KEY="$2"
API_URL="https://_yurURL_/hardware_info_collector.php"

# --- Hardware-Informationen sammeln ---
echo "Sammle Hardware-Informationen..."

# --- Betriebssystem erkennen ---
OS_NAME=$(uname -s)

if [ "$OS_NAME" = "Linux" ]; then
    # --- Linux-spezifische Befehle ---
    HOSTNAME=$(hostname)
    OS_INFO=$(lsb_release -d | sed 's/Description:\t*//')
    KERNEL_INFO=$(uname -r)
    CPU_INFO=$(lscpu | grep "Model name" | sed 's/Model name: *//')
    RAM_INFO=$(grep MemTotal /proc/meminfo | awk '{print $2 " " $3}')
    PCI_DEVICES=$(lspci)
    USB_DEVICES=$(lsusb)
    NETWORK_INFO=$(ip a)

elif [ "$OS_NAME" = "FreeBSD" ]; then
    # --- FreeBSD/pfSense-spezifische Befehle ---
    HOSTNAME=$(hostname)
    KERNEL_INFO=$(uname -r)
    
    # pfSense-Version oder generische FreeBSD-Info
    if [ -f "/etc/version" ]; then
        OS_INFO="pfSense $(cat /etc/version)"
    else
        OS_INFO="FreeBSD $(uname -v)"
    fi
    
    CPU_INFO=$(sysctl -n hw.model)
    RAM_INFO_BYTES=$(sysctl -n hw.physmem)
    RAM_INFO=$((RAM_INFO_BYTES / 1024 / 1024))
    RAM_INFO="${RAM_INFO}MB"

    PCI_DEVICES=$(pciconf -lv)
    USB_DEVICES=$(usbdevs -v)
    NETWORK_INFO=$(ifconfig)

elif [ "$OS_NAME" = "Darwin" ]; then
    # --- macOS-spezifische Befehle ---
    HOSTNAME=$(hostname)
    OS_INFO=$(sw_vers -productName)
    KERNEL_INFO=$(uname -r)
    CPU_INFO=$(sysctl -n machdep.cpu.brand_string)
    RAM_INFO_BYTES=$(sysctl -n hw.memsize)
    RAM_INFO=$((RAM_INFO_BYTES / 1024 / 1024 / 1024))
    RAM_INFO="${RAM_INFO}GB"
    
    PCI_DEVICES=$(system_profiler SPPciData)
    USB_DEVICES=$(system_profiler SPUSBData)
    NETWORK_INFO=$(ifconfig)

else
    echo "Unbekanntes Betriebssystem: $OS_NAME. Hardware-Informationen können nicht gesammelt werden."
    exit 1
fi

# Den gesammelten Text formatieren
HARDWARE_INFO="--- Hardware-Bericht ($(date '+%Y-%m-%d %H:%M:%S')) ---
--- System-Bericht für $HOSTNAME ---
Betriebssystem: $OS_INFO
Kernel: $KERNEL_INFO
CPU: $CPU_INFO
RAM: $RAM_INFO
  
--- PCI-Geräte ---
$PCI_DEVICES

--- USB-Geräte ---
$USB_DEVICES

--- Netzwerk-Schnittstellen ---
$NETWORK_INFO"

# --- 3. Zusammenfassung anzeigen ---
echo ""
echo "--------------------------------------------------"
echo "Zusammenfassung der gesammelten Daten:"
echo "--------------------------------------------------"
echo "$HARDWARE_INFO"
echo "--------------------------------------------------"
echo ""

# --- 4. Daten an das PHP-Skript senden ---
echo "Sende Hardware-Informationen an den Homelab Manager..."
RESPONSE=$(curl -s -X POST -F "key=$SECRET_KEY" -F "barcode=$BARCODE_NUMBER" -F "hardware_info=$HARDWARE_INFO" "$API_URL")

# Prüfe die Antwort des Servers
if [ $? -eq 0 ]; then
    echo "Antwort vom Server:"
    echo "$RESPONSE"
else
    echo "Fehler beim Senden der Daten."
fi

exit 0

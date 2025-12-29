# --- 1. Parameter überprüfen ---
param(
    [string]$Barcode,
    [string]$SecretKey
)

if (-not $Barcode -or -not $SecretKey) {
    Write-Host "Fehler: Ungültige Anzahl an Parametern."
    Write-Host "Verwendung: ./collect_hardware.ps1 -Barcode <BARCODE_NUMMER> -SecretKey <GEHEIMER_SCHLUESSEL>"
    exit
}

# --- Konfiguration übernehmen ---
$API_URL = "https://_youURL_/public/hardware_info_collector.php"

# --- Hardware-Informationen sammeln ---
Write-Host "Sammle Hardware-Informationen..."

# System-Informationen
$OS_INFO = (Get-CimInstance -ClassName Win32_OperatingSystem).Caption
$CPU_INFO = (Get-CimInstance -ClassName Win32_Processor).Name
$RAM_GB = [math]::Round(((Get-CimInstance -ClassName Win32_PhysicalMemory | Measure-Object -Sum -Property Capacity).Sum / 1GB), 1)
$RAM_INFO = "$RAM_GB GB"

# PCI-Geräte (oft als PnP-Geräte auf Windows behandelt)
$PCI_DEVICES = (Get-PnpDevice -Class System -PresentOnly | Select-Object FriendlyName, Manufacturer, Status | Out-String).Trim()

# USB-Geräte
$USB_DEVICES = (Get-PnpDevice -Class USB | Select-Object FriendlyName, Manufacturer, Status | Out-String).Trim()

# Netzwerk-Schnittstellen
$NETWORK_INFO = (Get-NetIPAddress | Select-Object InterfaceAlias, AddressFamily, IPAddress, PrefixLength | Out-String).Trim()

# Den gesammelten Text formatieren
$HARDWARE_INFO = @"
--- Hardware-Bericht ($(Get-Date -Format "yyyy-MM-dd HH:mm:ss")) ---
--- System-Bericht für $env:COMPUTERNAME ---
Betriebssystem: $OS_INFO
Kernel: N/A (Windows)
CPU: $CPU_INFO
RAM: $RAM_INFO
  
--- PCI-Geräte ---
$PCI_DEVICES

--- USB-Geräte ---
$USB_DEVICES

--- Netzwerk-Schnittstellen ---
$NETWORK_INFO
"@

# --- Zusammenfassung anzeigen ---
Write-Host ""
Write-Host "--------------------------------------------------"
Write-Host "Zusammenfassung der gesammelten Daten:"
Write-Host "--------------------------------------------------"
Write-Host "$HARDWARE_INFO"
Write-Host "--------------------------------------------------"
Write-Host ""

# --- Daten an das PHP-Skript senden (kompatible Methode) ---
Write-Host "Sende Hardware-Informationen an den Homelab Manager..."

$formData = @{
    key = $SecretKey
    barcode = $Barcode
    hardware_info = $HARDWARE_INFO
}

try {
    Invoke-WebRequest -Uri $API_URL -Method Post -Body $formData -ErrorAction Stop | Out-Null
    Write-Host "Daten erfolgreich gesendet."
} catch {
    Write-Host "Fehler beim Senden der Daten: $($_.Exception.Message)"
}

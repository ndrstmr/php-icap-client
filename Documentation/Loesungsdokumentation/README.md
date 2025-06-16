# Lösungsdokumentation

## A. Allgemeines

- **Zuordnung des Verfahrens:** ICAP-Anbindung für PHP-Applikationen.
- **Fachliche Beschreibung:** Ermöglicht einer PHP-Anwendung, Dateien oder Datenströme zur Überprüfung an einen ICAP-Server (z.B. für Virenscans, Content-Filterung) zu senden.
- **Ansprechpartner:**
  - Technisch: _t.b.d._
  - Fachlich: _t.b.d._
- **Weitere Dokumente:** [GitHub-Repository](https://github.com/ndrstmr/php-icap-client)

## B. Softwarearchitektur

### Technologiestack
- PHP (>=7.4)
- Composer

### Lösungsaufbau
Die Bibliothek wird als Composer-Paket in die PHP-Anwendung eingebunden und läuft im Kontext des PHP-Prozesses (z.B. unter PHP-FPM). Nach der Installation via Composer kann sie in der Anwendungslogik genutzt werden, um Daten an den ICAP-Server zu übertragen.

### Schnittstellen
- **Ausgehend:** TCP/IP-Verbindung zum ICAP-Server. Standard-Port: **1344** (konfigurierbar).

### Authentifizierung
Das ICAP-Protokoll sieht in der Basisversion keine Authentifizierung auf Anwendungsebene vor. Die Absicherung erfolgt in der Regel über Netzwerkmechanismen wie IP-Freigaben oder Firewall-Regeln.

### Drittanbieterlösungen
Die Bibliothek selbst bildet die Drittanbieterlösung. Zusätzlich wird ein externer ICAP-Server benötigt (z.B. eines Virenscanner-Herstellers).

### Verteilungsdiagramm
```
[Endbenutzer] -> [Webserver / PHP-Anwendung mit icap-client] -> [Firewall] -> [ICAP-Server]
```

## C. Bereitstellung der Lösung

### Installationsvorbereitungen
- **Prerequisites (Software):** PHP >= 7.4, Composer.
- **Freischaltungen:** Ausgehende Firewall-Freigabe vom Host zum ICAP-Server auf dem konfigurierten Port (Standard 1344).
- **Datenbanken:** Nicht erforderlich.
- **Zertifikate:**
  - Clientzertifikate: nicht erforderlich.
  - Serverzertifikate: nicht erforderlich, da die Kommunikation in der Regel unverschlüsselt erfolgt.
- **DNS:** Der Hostname des ICAP-Servers muss auflösbar sein.
- **Service-Accounts:** Nicht erforderlich.

### Installation
```bash
composer require ndrstmr/php-icap-client
```
Die Konfiguration des ICAP-Hosts und Ports erfolgt innerhalb des Anwendungscodes.

### Rollback eines Deployments
Ein Rollback erfolgt durch das Ausrollen einer früheren Version der Anwendung, welche eine ältere Version der Bibliothek nutzt oder sie nicht verwendet. Dies geschieht beispielsweise per `composer install` mit dem entsprechenden `composer.lock`.

### Technische Abnahme
1. Ein Testskript in der Anwendung sendet eine Testdatei an den ICAP-Server.
2. Der ICAP-Server beantwortet die Anfrage erfolgreich (Status-Code 200 oder 204).
3. Das positive Ergebnis wird protokolliert.


# Architektur-Dokumentation

## 1. Einführung und Ziele
### Aufgabenstellung
Diese Bibliothek stellt einen PHP-Client für das **Internet Content Adaptation Protocol** (ICAP) bereit. Damit können PHP-Anwendungen Inhalte über einen ICAP-Server prüfen oder verändern lassen.

### Qualitätsziele
- **Testbarkeit**: Die ICAP-Kommunikation ist über Interfaces abstrahiert, sodass Unit-Tests ohne reale Netzwerkverbindung möglich sind.
- **Wartbarkeit**: Klare Aufteilung in Komponenten (Client-Fassade, Transport, Formatter, Parser) erleichtert Erweiterungen.
- **Robustheit**: Fehler (Verbindungsprobleme, Timeouts, unlesbare Dateien) werden über spezifische Exceptions signalisiert.
- **Sicherheit**: Header-Inhalte werden beim Formatieren bereinigt, um Injection-Angriffe zu vermeiden.

### Stakeholder
- **PHP-Entwickler**, die die Bibliothek per Composer in ihre Anwendung einbinden.
- **Projektverantwortliche**, die ICAP-Scanning in ihren Systemen integrieren möchten.

## 2. Randbedingungen
### Technisch
- Benötigt **PHP 8.2** oder neuer.
- Distribution und Autoloading erfolgen über **Composer**.

### Organisatorisch
- Die Bibliothek steht unter der **MIT-Lizenz**.

## 3. Kontextabgrenzung
### Fachlicher Kontext
Die Bibliothek wird in PHP-Anwendungen genutzt, die einen externen ICAP-Server zur Inhaltsanalyse oder -manipulation ansprechen müssen.

### Technischer Kontext
```
+----------------------+        ICAP (TCP/IP)         +------------------+
| PHP-Anwendung        | <--------------------------> | Externer ICAP-   |
|  nutzt IcapClient    |                              | Server           |
+----------------------+                              +------------------+
```
**Schnittstellen:** Die Kommunikation erfolgt über das ICAP-Protokoll auf TCP/IP-Basis.

## 4. Lösungsstrategie
- **Facade-Pattern:** `IcapClient` bietet eine einfache API für OPTIONS/REQMOD/RESPMOD und kapselt die Details von Request-Formatierung, Parsing und Socketkommunikation.
- **Dependency Injection:** Transport- und Socket-Implementierungen können ausgetauscht werden (`TransportInterface`, `SocketClientInterface`).
- **DTOs:** Für Request und Response existieren klare Datenobjekte (`IcapRequest`, `IcapResponse`).
- **Schichtentrennung:** Formatierung und Parsing sind in eigene Klassen ausgelagert, was Wiederverwendbarkeit und Testbarkeit erhöht.

## 5. Bausteinsicht
### Level 1
- **DTO** – Datenobjekte für Requests und Responses.
- **Socket** – Abstraktion der Netzwerkschicht (`SocketClientInterface`, `PhpSocketClient`).
- **Transport** – Verwaltet Verbindungen, Timeouts und Antwortgrößen (`IcapTransport`).
- **Exception** – Spezifische Fehlertypen.
- **IcapClient** – Fassade für den Endnutzer.
- **IcapRequestFormatter/IcapResponseParser** – Umwandlung zwischen Objekt- und Stringrepräsentation.

### Level 2
- **IcapClient** nutzt `TransportInterface` für die Kommunikation und delegiert das Erzeugen/Parsen an Formatter und Parser.
- **SocketClientInterface** definiert die Methoden zum Verbinden, Lesen, Schreiben und Warten auf Daten.
- **IcapRequestFormatter/IcapResponseParser** bilden die ICAP-Protokoll-Logik ab.

Komponentendiagramm:
```
IcapClient
   |-- uses --> TransportInterface ---implemented by---> IcapTransport
   |                                                |
   |                                                +--> SocketClientInterface (z.B. PhpSocketClient)
   |-- uses --> IcapRequestFormatter
   |-- uses --> IcapResponseParser
```

## 6. Verteilungssicht
Die Bibliothek wird als Composer-Paket verteilt und läuft im selben PHP-Prozess wie die hostende Anwendung. Es ist keine separate Laufzeitumgebung erforderlich.

## 7. Querschnittliche Konzepte
### Sicherheit
Beim Formatieren von Requests werden Headerwerte von Zeilenumbrüchen bereinigt, um Manipulationen zu verhindern. Der Transport kann zudem Timeouts setzen, um hängende Verbindungen zu erkennen.

### Fehlerbehandlung
Spezifische Exceptions wie `IcapFileException`, `IcapConnectionException` oder `IcapTimeoutException` ermöglichen feingranulare Fehlerbehandlung durch den Aufrufer.

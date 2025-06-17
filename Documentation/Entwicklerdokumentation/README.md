# Entwicklerdokumentation

## A. Allgemeines

### Fachliche Beschreibung
Eine robuste, moderne und testbare PHP-Implementierung eines ICAP-Clients. Diese Bibliothek ermöglicht die Kommunikation mit ICAP-Servern zur Analyse oder Modifikation von HTTP-Requests und -Responses.

### Weitere Dokumente
- [README.md](../../README.md)

### Tools, Frameworks und Libraries
- PHP (>=8.2)
- Composer
- PHPUnit

### Projektaufbau
Die Quellen liegen unter `src`, die Tests unter `tests`.

```
src/                 # Bibliothekscode
    DTO/             # Datenobjekte
    Exception/       # Fehlertypen
    Socket/          # Socket-Implementierungen
    Transport/       # Transport-Logik
``` 

PSR‑4-Autoloading ist in der `composer.json` hinterlegt:
`"Ndrstmr\\Icap\\": "src/"`

### Code-Konventionen
Der Code folgt dem PSR‑12‑Standard.

### Tests
Unit-Tests prüfen Formatter und Parser, Integrationstests den `PhpSocketClient` sowie den Transport. Ausgeführt werden sie mit:

```
composer install
vendor/bin/phpunit
```

### Versionierung
Die Bibliothek nutzt Semantic Versioning.

## B. Prozessbeschreibung

### Build und Paketierung
Es gibt keinen klassischen Build-Prozess. Abhängigkeiten werden mit `composer install` installiert. Eine Veröffentlichung kann über Packagist erfolgen.

### Deployment
Die Einbindung erfolgt über Composer:

```
composer require ndrstmr/php-icap-client
```

## C. Besonderheiten

### Streaming-Implementierung
Die Bibliothek unterstützt Streaming, um große Dateien speicherschonend zu verarbeiten:

```php
foreach ($client->getRequestIterable('RESPMOD', 'example', ['res-body' => $stream]) as $chunk) {
    $transport->send($chunk);
}
```

### Fehlerbehandlung
Eigene Exception-Klassen decken typische Fehler ab:
- `IcapConnectionException` bei Verbindungsproblemen
- `IcapFileException` bei Dateilesefehlern
- `IcapTimeoutException` bei Zeitüberschreitungen
- `IcapResponseException` bei ungültigen Antworten

### Abhängigkeitsinjektion
Über das `SocketClientInterface` lassen sich unterschiedliche Socket-Implementierungen verwenden. Dadurch ist der Transport austauschbar und Tests können mit Mock-Implementierungen arbeiten.

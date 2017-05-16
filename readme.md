OwnCloud-Plugin für Stud.IP
===========================

Mit diesem Plugin kann man ab der Stud.IP-Version 4.0 eine Owncloud (oder Nextcloud) als persönlichen Dateibereich einbinden und von und zu der Owncloud Dateien nach oder von Stud.IP kopieren.

## Installation

1. Das Plugin wird ganz normal in Stud.IP per Drag&Drop installiert. 
2. Die OwnCloud braucht die [OAuth2-App](https://github.com/owncloud/oauth2/releases), die installiert und aktiviert werden muss. Wer nur Stud.IP gewohnt ist: OAuth2 heißt im Owncloud-Kontext zwar "App", ist aber das gleiche wie bei Stud.IP ein Plugin. Die Dateien müssen in das Owncloud-Verzeichnis unter `./apps/oauth2` abgelegt werden.
3. Die OAuth2 "App" muss als Admin im Webinterface von Owncloud aktiviert werden unter "Einstellungen" (oben rechts unter dem Nutzernamen) -> Apps -> "Deaktivierte Apps anzeigen" -> "aktivieren".
4. Es muss in Owncloud ein Client angelegt werden. Unter Administration -> "Additional" einen neuen Client anlegen. Name ist dabei egal (vielleicht ja Stud.IP).
5. Jetzt hat man einen OAuth2-Client erstellt und kopiert Client-ID und das Secret.

Jetzt muss man sich überlegen, wie das Owncloud-Plugin genutzt werden soll in Stud.IP. Gibt es eine zentrale OwnCloud für alle Stud.IP-Nutzer oder kümmern sich die Nutzer selbst um eine eigene OwnCloud?

Zentral: 

1. Melde Dich im Stud.IP als Root an und gehe unter Admin -> System -> Konfiguration -> Owncloud.
2. Trage die oben gewonnene Client-ID beim Parameter `OWNCLOUD_CLIENT_ID` ein.
3. Trage das oben gewonnene Secret beim Parameter `OWNCLOUD_CLIENT_SECRET` ein.


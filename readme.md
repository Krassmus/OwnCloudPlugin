OwnCloud-Plugin für Stud.IP
===========================

Mit diesem Plugin kann man ab der Stud.IP-Version 4.0 eine Owncloud (oder Nextcloud) als persönlichen Dateibereich einbinden und von und zu der Owncloud Dateien nach oder von Stud.IP kopieren.

## Installation

1. Das Plugin wird ganz normal in Stud.IP per Drag&Drop installiert.
2. Die OwnCloud braucht die [OAuth2-App](https://github.com/owncloud/oauth2/releases), die installiert und aktiviert werden muss. Wer nur Stud.IP gewohnt ist: OAuth2 heißt im Owncloud-Kontext zwar "App", ist aber das gleiche wie bei Stud.IP ein Plugin. Die Dateien müssen in das Owncloud-Verzeichnis unter `./apps/oauth2` abgelegt werden. NextCloud hingegen hat OAuth 2 schon eingebaut.
3. Für OAuth in der OwnCloud hinter einem Apache braucht man das mod_rewrite und mod_env Modul, das die Regel `RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization}]` umsetzt. Wenn das nicht aktiv ist, kann OAuth nicht funktionieren, weil der Apache alle Authorization-Header entfernt.
4. Die OAuth2 "App" muss als Admin im Webinterface von Owncloud aktiviert werden unter "Einstellungen" (oben rechts unter dem Nutzernamen) -> Apps -> "Deaktivierte Apps anzeigen" -> "aktivieren".
5. Es muss in Owncloud ein Client angelegt werden. Unter Administration -> "Additional" einen neuen Client anlegen. Name ist dabei egal (vielleicht ja Stud.IP).
6. Jetzt hat man einen OAuth2-Client erstellt und kopiert Client-ID und das Secret. Wichtig ist dabei, dass man die korrekte Redirect-URI angibt. Owncloud überprüft diese URI penibel. Sie sollte in etwa lauten `https://meinstud.ip/plugins.php/owncloudplugin/oauth/receive_access_token`. Auch sollte HTTPS aktiv sein.

Jetzt muss man sich überlegen, wie das Owncloud-Plugin genutzt werden soll in Stud.IP. Gibt es eine zentrale OwnCloud für alle Stud.IP-Nutzer oder kümmern sich die Nutzer selbst um eine eigene OwnCloud?

Zentral:

1. Melde Dich im Stud.IP als Root an und gehe unter Admin -> System -> Konfiguration -> Owncloud.
2. Trage die oben gewonnene Client-ID beim Parameter `OWNCLOUD_CLIENT_ID` ein.
3. Trage das oben gewonnene Secret beim Parameter `OWNCLOUD_CLIENT_SECRET` ein.

Individuell:

1. Der Nutzer muss dann alleine die obigen Schritte durchführen bzw. seinen OwnCloud-Admin fragen, ob er das für ihn tun kann und die Credentials übergibt.
2. Im persönlichen Dateibereich von Stud.IP 4.0 gibt es in der Sidebar den Punkt "OwnCloud konfigurieren". Da muss er drauf klicken. Ein Dialog öffnet sich.
3. Man muss die Adresse der OwnCloud eintragen (z.B. `https://meineuni/owncloud`) und App-ID und Secret von oben eintragen und die OwnCloud aktiv schalten und speichern.

Die nächsten Schritte sind für beide Wege wieder dieselben:

1. Wer es individuell eingestellt hat, kennt es schon: Im persönlichen Dateibereich von Stud.IP 4.0 gibt es in der Sidebar den Punkt "OwnCloud konfigurieren". Da muss man drauf klicken.
2. Man muss das Häkchen für "aktiviert" setzen und speichern.
3. Das Fenster lädt sich neu und ein Button erscheint oben "OwnCloud für Stud.IP freigeben". Dort klicken.
4. Dann landet man in der OwnCloud und wird aufgefordert sich anzumelden. Vergewissern Sie sich in solchen Situationen immer (nicht nur jetzt), dass die URL stimmt und Sie Ihre Passwörter nicht einer anderen Seite als genau Ihrer OwnCloud zusenden.
5. Die OwnCloud fragt Sie, ob Stud.IP in Ihrem Namen Daten abrufen und verändern darf. Klicken Sie auf "Authorisieren".
6. Jetzt landen Sie wieder in Stud.IP und die Schnittstelle zwischen Stud.IP und OwnCloud sollte eingerichtet sein.

Probleme?

* Manchmal scheint fast alles zu funktionieren. OAuth-Access-Token ist da, Refresh-Token ist da und der Refresh scheint auch zu klappen, aber es werden keine Dateien und Ordner angezeigt. Es erscheint stattdessen eine Fehlermeldung: "No 'Authorization: Basic' header found. Either the client didn't send one, or the server is misconfigured, No 'Authorization: Basic' header found. Either the client didn't send one, or the server is misconfigured, No 'Authorization: Bearer' header found. Either the client didn't send one, or the server is mis-configured". In dem Fall muss man vermutlich in die Datei .htaccess die Zeile einbauen `SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1`


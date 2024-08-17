## Specificatie GGM-GEMMA data-uitwisseling

De GGM objecttypen en relaties worden aangeleverd aan de GEMMA. De GEMMA importeert deze en leidt haar bedrijfsobjectmodellen hiervan af. De GEMMA bedrijfsobjecten worden weer teruggeleverd aan GGM. De GGM kan besluiten de definities van de GEMMA bedrijfsobjecten over te nemen.

De uitwisseling is gemaakt met CSV-bestanden. De samenstelling van de CSV-bestanden is hieronder gespecificeerd

- kolommen met de prefix ' GGM-' worden beheerd door GGM-community
- kolommen met de prefix 'GEMMA-' worden beheerd door VNGR
- Als start neemt GEMMA de GGM definities over en baseert hier de bedrijfsobjecten op
- de GEMMA bedrijfsobjecten kunnen gaan afwijken van de GGM definities. 
  - De definities die verschillen kennen dan zowel een ' GGM-' als een 'GEMMA-' waarde.
  - er kunnen nieuwe GEMMA bedrijfsobjecten bijkomen. Deze hebben dan geen GGM-guid 

### CSV-bestanden

De GGM exportbestanden worden beschikbaar gesteld in de [GGM repository (GitHub)](https://github.com/Gemeente-Delft/Gemeentelijk-Gegevensmodel).

* ggm_export_objects_&lt;datum-tijd&gt;.csv
* ggm_export_relations_&lt;datum-tijd&gt;.csv


In deze repository worden de [GEMMA CSV bestanden](https://github.com/VNG-Realisatie/GEMMA-GGM-Archi-repository/exports) gemaakt en beschikbaar gesteld.

* GEMMA\_Bedrijfsobjecten_element.csv
* GEMMA\_Bedrijfsobjecten_relatie.csv

### Inhoud CSV-bestanden

#### Elementen

Het elementen exportbestand bevat de data-objecten (gemaakt door GGM) of de bedrijfsobjecten (gemaakt door GEMMA).

| Kolom&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | Betekenis  | Eigenschap van GEMMA bedrijfsobject | Toelichting geschreven vanuit GEMMA import- of exportproces |
| :--- | :--- | :--- | :--- |
| nr | regelnummer in CSV bestand  | | import: Voor iedere regel wordt een ArchiMate data-object aangemaakt of bijgewerkt. Het regelnummer zelf wordt niet opgenomen in het data-object, maar wel voor het troubleshooting van import-problemen |
| GEMMA-naam | Naam van het bedrijfsobject in het GEMMA-GGM ArchiMate-model | name  | export: de *GEMMA-naam* kan anders zijn dan de oorspronkelijke GGM-naam. <ul><li>Als de naam gewijzigd is, dan wordt ook de *GGM-GUID* teruggeleverd.</li><li>Als het een nieuw object is, dan worden alleen de GEMMA gegevens geleverd (bijvoorbeeld als meerdere GGM objecttypen zijn samengevoegd tot één bedrijfsobject)</li></ul>|
| GGM-naam | Naam van het objecttype  | GGM-naam | import: wordt overgenomen als name  |
| GEMMA-guid | Uniek id beheerd door GEMMA | Object ID | export: door GEMMA beheerd gegeven  |
| GGM-guid | Uniek en niet wijzigend id van het object  | GGM-guid | import: wordt overgenomen in *GGM-guid*  |
| GEMMA-type | ArchiMate type, altijd business-object  | type  | export: door GEMMA beheerd gegeven  |
| GGM-uml-type  | Het in het GGM UML model gebruikt type, zoals class of enumeration | GGM-uml-type | import: wordt overgenomen in *GGM-uml-type* |
| GEMMA-definitie  | Definitie van het bedrijfsobject  | documentation  | export: gelijk aan omgang met *GEMMA-naam*  |
| GGM-definitie | samenvattende omschrijving van de kenmerken van het object | GGM-definitie  | import: wordt overgenomen in documentation |
| GEMMA-toelichting | Aanvullende toelichting op de definitie | toelichting | export: gelijk aan omgang met *GEMMA-naam*  |
| GGM-toelichting  | Aanvullende toelichting op de definitie | GGM-toelichting | import: indien deze nog niet bestaat of gelijk is aan de huidige waarde, dan overnemen in *toelichting*, anders overnemen in  *GGM-toelichting* |
| GEMMA-synoniemen | Alternatieve naam met min of meer dezelfde betekenis (meerdere mogelijk, comma separated)  | synoniemen  | export: gelijk aan omgang met *GEMMA-naam*  |
| GGM-synoniemen | Alternatieve naam met min of meer dezelfde betekenis (meerdere mogelijk, comma separated)  | GGM-synoniemen | import: indien deze nog niet bestaat of gelijk is aan de huidige waarde, dan overnemen in *synoniemen*, anders overnemen in  *GGM-synoniemen* |
| GEMMA-bron | Extern informatiemodel waaruit GGM de definities heeft overgenomen | bron  | export: gelijk aan omgang met *GEMMA-naam*  |
| GGM-bron | Extern informatiemodel waaruit GGM de definities heeft overgenomen | bron  | import: wordt overgenomen in *bron* |
| GEMMA-url  | URL naar object op GEMMA online | GEMMA URL | export: door GEMMA beheerd gegeven  |
| GEMMA-alternate-name | GGM staat duplicate namen toe. De alternate name is uniek gemaakt door het iv-3 taakveld achter de naam te zetten  | alternate name | export: door GEMMA beheerd gegeven  |
| domein-iv3 | Op Iv3 gebaseerde beleidsdomeinen. Dit zijn de Iv3 taakvelden plus extras om alle objecttypen te kunnen indelen | | import: wordt overgenomen als relatie met grouping beleidsdomein. <br>export: de beleidsdomeinen worden in de GEMMA exportbestanden niet teruggeleverd |
| domein-dcat | Data Catalog Vocabulary (DCAT) is an RDF vocabulary designed to facilitate interoperability between data catalogs published on the Web. | | import: nu altijd leeg, nog bepalen of dit een relatie met een grouping wordt of een property <br>export: de beleidsdomeinen worden in de GEMMA exportbestanden niet teruggeleverd |
| Datum-tijd-export | Datum en tijdstip waarop het exportbestand is gemaakt (ddmmyyyy-hh:mm:ss) | | import: overgenomen als *GGM-datum-tijd-export*<br/>export: aangemaakt door exportscript |

#### Relaties

Het relatie exportbestand bevat de relaties tussen de data-objecten (gemaakt door GGM) of de bedrijfsobjecten (gemaakt door GEMMA). 

De GEMMA relaties tussen de bedrijfsobjecten en de beleidsdomeinen worden niet geëxporteerd. Deze relaties worden door GGM aangeleverd in de elementen CSV.

| Kolom&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;| Betekenis | Eigenschap van GEMMA relatie  | Toelichting geschreven vanuit GEMMA import- of exportproces  |
| :--- | :--- | :--- | :--- |
| nr | regelnummer in CSV bestand  | | import: Voor iedere regel wordt een relatie tussen 2 data-objecten aangemaakt. Het Archimate type relatie wordt afgeleid van het uml-type. Het regelnummer wordt niet opgenomen in het model |
| GEMMA-naam | label van de relatie  | name  | export: de *GEMMA-naam* kan anders zijn dan de oorspronkelijke GGM-naam. <ul><li>Als de naam gewijzigd is, dan wordt ook de *GGM-GUID* teruggeleverd.</li><li>Als het een nieuw object is, dan worden alleen de GEMMA gegevens geleverd (bijvoorbeeld als meerdere GGM objecttypen zijn samengevoegd tot één bedrijfsobject)</li></ul> |
| GGM-naam | label van de relatie  | name of GGM-naam  | import: indien deze nog niet bestaat of gelijk is aan de huidige waarde, dan overnemen in name, anders overnemen in GGM-naam |
| GEMMA-guid | Uniek id van de relatie beheerd door GEMMA  | Object ID | export: door GEMMA beheerd gegeven |
| GGM-guid | Unieke en niet wijzigend id van de relatie  | GGM-guid  | import: wordt overgenomen in *GGM-guid*  |
| GEMMA-type | ArchiMate relatietype (association of specialization-relationship)  | type  | export: door GEMMA beheerd gegeven |
| GGM-uml-type | UML type van de relatie | GGM-uml-type  | import: wordt gebruikt voor bepalen ArchiMate type en wordt overgenomen in *GGM-uml-type*  |
| GEMMA-definitie  | definitie van de relatie, eigenlijk altijd leeg | documentation | export: gelijk aan omgang met *GEMMA-naam* |
| GGM-definitie  | samenvattende omschrijving van de kenmerken van de relatie  | documentation of GGM-definition | import: indien deze nog niet bestaat of gelijk is aan de huidige waarde, dan overnemen in documentation, anders overnemen in GGM-definitie |
| GEMMA-toelichting  | Aanvullende toelichting op de definitie | toelichting | export: gelijk aan omgang met *GEMMA-naam* |
| GGM-toelichting  | Aanvullende toelichting op de definitie | toelichting of GGM-toelichting  | import: indien deze nog niet bestaat of gelijk is aan de huidige waarde, dan overnemen in toelichting, anders overnemen in *GGM-toelichting* |
| GEMMA-source-guid  | uniek id van object waar relatie vandaan komt | source.prop.Object ID | export: door GEMMA beheerd gegeven |
| GGM-source-guid  | Id van het object waar de relatie vandaan komt  | | import: door GGM beheerd gegeven, wordt gebruikt voor aanmaken relatie met juiste data-object  |
| GEMMA-target-guid  | uniek id van object waar relatie binnenkomt | target.prop.Object ID | export: door GEMMA beheerd gegeven |
| GGM-target-guid  | Id van het object waar de relatie naar toe wijst  | | import: door GGM beheerd gegeven, wordt gebruikt voor aanmaken relatie met juiste data-object  |
| Datum-tijd-export  | Datum en tijdstip waarop het exportbestand is gemaakt (ddmmyyyy-hh:mm:ss) | | import: overgenomen als *GGM-datum-tijd-export*<br/>export: aangemaakt door exportscript |
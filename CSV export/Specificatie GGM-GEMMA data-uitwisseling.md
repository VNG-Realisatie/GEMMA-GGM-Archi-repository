## Specificatie GGM-GEMMA data-uitwisseling

Deze specificatie vervangt de vorige [Specificatie_GEMMA-GGM_uitwisseling](https://redactie.gemmaonline.nl/index.php/Specificatie_GEMMA-GGM_uitwisseling "Specificatie GEMMA-GGM uitwisseling")

De GGM objecttypen en relaties worden aangeleverd aan de GEMMA. De GEMMA importeert deze en maakt op basis hiervan haar bedrijfsobjectmodellen. De GEMMA bedrijfsobjecten worden weer teruggeleverd aan GGM. De GGM kan besluiten de definities van de GEMMA bedrijfsobjecten over te nemen.

De uitwisseling is gemaakt met CSV-bestanden. De samenstelling van de CSV-bestanden is hieronder gespecificeerd

- kolommen met de prefix 'ggm-' worden beheerd door GGM-community
- kolommen met de prefix 'gemma-' worden beheerd door VNGR
- Als start neemt GEMMA de GGM definities over en baseert hier de bedrijfsobjecten op
- de GEMMA bedrijfsobjecten kunnen gaan afwijken van de GGM definities. 
  - De definities die verschillen kennen dan zowel een 'ggm-' als een 'gemma-' waarde.
  - er kunnen nieuwe GEMMA bedrijfsobjecten bijkomen. Deze hebben dan geen ggm-guid 

### GGM CSV-bestanden

De GGM exportbestanden worden beschikbaar gesteld in de [GGM repository (GitHub)](https://github.com/Gemeente-Delft/Gemeentelijk-Gegevensmodel). 

#### ggm_export_objects_&lt;datum-tijd&gt;.csv

| Kolom&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | Betekenis | Toelichting import in GEMMA |
|:---|:---|:---|
| nr  | regelnummer in CSV bestand | Voor iedere regel wordt een ArchiMate **data-object** aangemaakt of bijgewerkt.<br><br>Het regelnummer zelf wordt niet opgenomen in het data-object, maar wel voor het troubleshooting van import-problemen |
| ggm-naam | Naam van het objecttype | wordt overgenomen als *name* |
| ggm-definitie | samenvattende omschrijving van de kenmerken van het object  | wordt overgenomen in *documentation* |
| ggm-uml-type | Het in het GGM UML model gebruikt type, zoals class of enumeration | wordt overgenomen in *ggm-uml-type* |
| ggm-toelichting | Aanvullende toelichting op de definitie | indien deze nog niet bestaat of gelijk is aan de huidige waarde, dan overnemen in *toelichting*, anders overnemen in *ggm-toelichting*|
| ggm-synoniemen  |  Alternatieve naam met min of meer dezelfde betekenis (meerdere mogelijk, comma separated)   | indien deze nog niet bestaat of gelijk is aan de huidige waarde, dan overnemen in *synoniemen*, anders overnemen in *ggm-synoniemen*|
| ggm-guid | Uniek en niet wijzigend id van het object | wordt overgenomen in *ggm-guid* |
| bron | Extern informatiemodel waaruit GGM de definities heeft overgenomen | wordt overgenomen in *bron* |
| domein-iv3 | Op Iv3 gebaseerde beleidsdomeinen. Dit zijn de Iv3 taakvelden plus extras om alle objecttypen te kunnen indelen | wordt overgenomen als relatie met *grouping beleidsdomein* |
| domein-dcat |  Data Catalog Vocabulary (DCAT) is an RDF vocabulary designed to facilitate interoperability between data catalogs published on the Web.    | nu altijd leeg, nog bepalen of dit een relatie met een grouping wordt of een property |
| datum-tijd-export | Datum en tijdstip waarop het exportbestand is gemaakt (ddmmyyyy-hh:mm:ss) | overgenomen als ggm-datum-tijd-export  |

#### ggm_export_relations_&lt;datum-tijd&gt;.csv

| Kolom&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | Betekenis | Toelichting import in GEMMA |
|:---|:---|:---|
| nr  | regelnummer in CSV bestand | Voor iedere regel wordt een relatie tussen 2 data-objecten aangemaakt. Het Archimate type relatie wordt afgeleid van het uml-type.<br><br> Het regelnummer wordt niet opgenomen in het model |
| ggm-naam | label van de relatie | wordt overgenomen als *name* |
| ggm-definitie |  samenvattende omschrijving van de kenmerken van de relatie  | wordt overgenomen in *documentation* |
| ggm-uml-type |     | wordt overgenomen in overeenkomend ArchiMate *type* |
| ggm-toelichting | Aanvullende toelichting op de definitie | indien deze nog niet bestaat of gelijk is aan de huidige waarde, dan overnemen in *toelichting*, anders overnemen in *ggm-toelichting*|
| ggm-guid | Unieke en niet wijzigend id van de relatie | wordt overgenomen in *ggm-guid* |
| ggm-source-guid | Id van het object waar de relatie vandaan komt | wordt gebruikt voor aanmaken relatie met juiste data-object |
| ggm-target-guid | Id van het object waar de relatie naar toe wijst | wordt gebruikt voor aanmaken relatie met juiste data-object |
| datum-tijd-export | Datum en tijdstip waarop het exportbestand is gemaakt (ddmmyyyy-hh:mm:ss) | overgenomen als ggm-datum-tijd-export  |

### GEMMA CSV-bestanden

De GEMMA bedrijfsobjectmodellen worden beheerd in de [GGM Archi-repository](https://github.com/VNG-Realisatie/GGM-Archi-repository). Vanuit deze repository worden de [GEMMA CSV bestanden](https://github.com/VNG-Realisatie/GGM-Archi-repository/tree/develop/CSV%20export) gemaakt en beschikbaar gesteld.


#### GEMMA\_Bedrijfsobjecten_element.csv

| Kolom&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | Eigenschap van GEMMA bedrijfsobject | Betekenis |
|:---|:---|:---|
| gemma-naam | name |  Naam van het bedrijfsobject in het GGM ArchiMate-model|
| gemma-definitie | documentation | Definitie van het bedrijfsobject |
| gemma-type | type | ArchiMate type, altijd business-object |
| gemma-toelichting | toelichting | Aanvullende toelichting op de definitie |
| gemma-alternate-name | alternate name | GGM staat duplicate namen toe. De alternate name is uniek gemaakt door het iv-3 taakveld achter de naam te zetten |
| gemma-synoniemen | synoniemen | Alternatieve naam met min of meer dezelfde betekenis (meerdere mogelijk, comma separated) |
| gemma-url | GEMMA URL | URL naar object op GEMMA online |
| gemma-guid | Object ID | Uniek id beheerd door GEMMA |
| gemma-datum-tijd-export | - | Datum en tijdstip waarop het exportbestand is gemaakt |
| ggm-guid | ggm-guid | Id van het door GGM beheerde object. |
| ggm-naam | ggm-naam| GGM naam van bedrijfsobject. Heeft alleen een waarde als deze anders is dan de GEMMA naam |
| ggm-definitie | ggm-definitie | GGM definitie van het bedrijfsobject. Heeft alleen een waarde als deze anders is dan de GEMMA definitie |
| ggm-uml-type |  ggm-uml-type | UML type van het GGM object |
| ggm-datum-tijd-export | ggm-datum-tijd-export | Datum en tijdstip van laatste GGM export waarmee het bedrijfsobject is bijgewerkt |
| ggm-synoniemen | ggm-synoniemen | GGM synoniemen van het bedrijfsobject. Heeft alleen een waarde als deze anders is dan de GEMMA synoniemen |
| bron | bron | Extern informatiemodel waaruit GGM de definities heeft overgenomen |

#### GEMMA\_Bedrijfsobjecten_relatie.csv

| Kolom&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  | Eigenschap van GEMMA relatie | Betekenis |
| :--- | :--- | :--- |
| gemma-naam   | name | label van de relatie |
| gemma-definitie    | documentation | definitie van de relatie, eigenlijk altijd leeg |
| gemma-type   | type | ArchiMate relatietype (association of specialization-relationship) |
| gemma-guid  | Object ID | Uniek id van de relatie beheerd door GEMMA |
| gemma-source-guid | source.prop.Object ID | uniek id van object waar relatie vandaan komt |
| gemma-target-guid | target.prop.Object ID | uniek id van object waar relatie binnenkomt |
| gemma-datum-tijd-export | - | Datum en tijdstip waarop het exportbestand is gemaakt |
| ggm-guid | ggm-guid | Id van het door GGM beheerde object. |
| ggm-naam | ggm-naam| GGM label van de relatie. Heeft alleen een waarde als deze anders is dan het GEMMA label |
| ggm-definitie | ggm-definitie | GGM definitie van de relatie. Heeft alleen een waarde als deze anders is dan de GEMMA definitie |
| ggm-uml-type |  ggm-uml-type | UML type van de relatie|
| ggm-datum-tijd-export | ggm-datum-tijd-export | Datum en tijdstip van laatste GGM export waarmee de relaties is bijgewerkt |

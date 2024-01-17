## Specificatie GGM-GEMMA data-uitwisseling

De GGM objecttypen en relaties worden aangeleverd aan de GEMMA. De GEMMA importeert deze en maakt op basis hiervan haar bedrijfsobjectmodellen. De GEMMA bedrijfsobjecten worden weer teruggeleverd aan GGM. De GGM kan besluiten de definities van de GEMMA bedrijfsobjecten over te nemen.

De uitwisseling is gemaakt met CSV-bestanden.

De samenstelling van de CSV-bestanden is hieronder gespecificeerd

### GGM CSV-bestanden

Deze specificatie vervangt de vorige [Specificatie_GEMMA-GGM_uitwisseling](https://redactie.gemmaonline.nl/index.php/Specificatie_GEMMA-GGM_uitwisseling "Specificatie GEMMA-GGM uitwisseling")

GGM exporteert de objecttypen en relaties.

- Beide in een apart bestand
- Wordt beschikbaar gesteld in de [GGM github repository](https://github.com/Gemeente-Delft/Gemeentelijk-Gegevensmodel)

  
Wensen vanuit GEMMA voor GGM CSV-bestanden

- er zijn in git twee CSV-bestanden, zonder datum in de bestandsnaam.
    - Een nieuwe export leidt tot een nieuwe versie in git.
    - de verschillen met de vorige versie zijn eenvoudig met git in te zien.

#### ggm_export_objects_&lt;datum-tijd&gt;.csv

| Eigenschap | betekenis | toelichting import in GEMMA |
| --- | --- | --- |
| nr  | regelnummer in CSV bestand | Voor iedere regel wordt een ArchiMate **data-object** aangemaakt of bijgewerkt.<br><br>Het regelnummer zelf wordt niet opgenomen in het data-object, maar wel voor het troubleshooting van import-problemen |
| ggm-naam | Naam van het objecttype | wordt overgenomen als *name* |
| ggm-definitie |     | wordt overgenomen in *documentation* |
| ggm-uml-type | Het in het GGM UML model gebruikt type, zoals class of enumeration | wordt overgenomen in *ggm-uml-type* |
| ggm-guid | Unieke en niet wijzigend id van het object | wordt overgenomen in *ggm-guid* |
| bron | Extern informatiemodel waaruit GGM de definities heeft overgenomen | wordt overgenomen in *bron* |
| domein-iv3 (Indeling GGM) | Op Iv3 gebaseerde Iv3-domeinen. Dit zijn de Iv3 taakvelden plus extras om alle objecttypen te kunnen onderbrengen | wordt overgenomen als relatie met *grouping domein-iv3* |
| toelichting | Aanvullende toelichting op de definitie | wordt overgenomen in *toelichting* |
| synoniemen (comma seperated) |     | wordt overgenomen in *synoniemen* |
| domein-dcat |     | nu altijd leeg |
| ggm-datum-tijd-export | Datum en tijdstip waarop het exportbestand is gemaakt | overgenomen als ggm-datum-tijd-export (ddmmyyyy-hh:mm:ss) |

#### ggm_export_relations_&lt;datum-tijd&gt;.csv

| Beheerder eigenschap | eigenschap | betekenis | toelichting import in GEMMA |
| --- | --- | --- | --- |
| GGM | nr  | regelnummer in CSV bestand | regelnummer, handig in communicatie bij problemen. Wordt niet opgenomen in de modellen |
|     | naam (label) |     | wordt overgenomen als *name* |
|     | definitie |     | wordt overgenomen in*documentation*n |
|     | uml-type |     | wordt overgenomen in overeenkomend ArchiMate *type* |
|     | ggm-guid | Unieke en niet wijzigend id van de | wordt overgenomen |
|     | ggm-source-guid | Id van het objecttype waar de relatie vandaan komt | wordt gebruikt voor aanmaken relatie met juiste data-object |
|     | ggm-target-guid | Id van het objecttype waar de relatie naar toe wijst | wordt gebruikt voor aanmaken relatie met juiste data-object |
|     | toelichting |     | wordt overgenomen in *toelichting* |
|     | datum-tijd-export | Datum en tijdstip waarop het exportbestand is gemaakt | overgenomen als ggm-datum-tijd-export (ddmmyyyy-hh:mm:ss) |

### GEMMA CSV-bestanden

In het GGM Archimate-model worden de GEMMA bedrijfsobjecten afgeleid van de geïmporteerde GGM objecttypen en relaties.

De afgeleide GEMMA bedrijfsobjecten worden in CSV-bestanden beschikbaar gesteld

#### GEMMA\_Bedrijfsobjecten_element.csv

| Beheerder eigenschap | in ArchiMate-model | Kolomnaam | toelichting |
| --- | --- | --- | --- |
| GEMMA | name | GEMMA naam | GEMMA naam van het bedrijfsobject |
|     |     | GEMMA type | ArchiMate type, altijd business-object |
|     |     | documentation | GEMMA definitie van het bedrijfsobject |
|     |     | id  | technisch id, heeft alleen betekenis binnen de tool Archi |
|     |     | Object ID | Uniek id beheerd door GEMMA |
|     |     | Let op | waarschuwing dat ggm- eigenschappen buiten GEMMA beheerd worden |
|     |     | GEMMA URL | URL naar object op GEMMA online |
|     |     | Meer specifiek | opsomming van specialisaties van object (comma separated) |
|     |     | alternate name | GGM staat duplicate namen toe. De alternate name is uniek gemaakt door het iv-3 taakveld achter de naam te zetten |
| GGM |     | ggm-guid | Unieke id beheerd door GGM-community in Sparx EA |
|     |     | ggm-name | GGM naam van bedrijfsobject, bestaat alleen als deze anders is dan de GEMMA naam |
|     |     | ggm-definitie | GGM definitie van het bedrijfsobject, bestaat alleen als deze anders is dan de GEMMA definitie |
|     |     | ggm-uml-type | UML type |
|     |     | ggm-datum-tijd-export | datum en tijdstip van export GGM informatiemodel |
|     |     | synoniemen |     |
|     |     | bron | informatiemodel waar object uit overgenomen is |

#### GEMMA\_Bedrijfsobjecten_relatie.csv

| Beheerder eigenschap | eigenschap | toelichting |
| --- | --- | --- |
| GEMMA | folder | GEMMA naam van de relatie |
|     | name | label van de relatie |
|     | type | ArchiMate type, association of specialization |
|     | documentation | definitie van de relatie, eigenlijk altijd leeg |
|     | id  | technisch id, heeft alleen betekenis binnen de tool Archi |
|     | Let op | waarschuwing dat ggm- eigenschappen buiten GEMMA beheerd worden |
|     | Object ID | Uniek id beheerd door GEMMA |
|     | source.name | naam object waar relatie vandaan komt |
|     | source.type | ArchiMate type van object waar relatie vandaan komt |
|     | target.name | naam object waar relatie binnenkomt |
|     | target.type | ArchiMate type van object waar relatie binnenkomt |
|     | source.id | technisch id van Archi |
|     | target.id | technisch id van Archi |
|     | source.prop.Object ID | uniek id van object waar relatie vandaan komt |
|     | target.prop.Object ID | uniek id van object waar relatie binnenkomt |
|     | accessType | niet gebruikt, alleen relevant voor access-relationships |
|     | associationDirected | association-relation met richting, wordt getoond met halve pijl |
|     | influenceStrength | niet gebruikt |
| GGM | ggm-guid | Unieke id beheerd door GGM-community in Sparx EA |
|     | ggm-datum-tijd-export | datum en tijdstip van export GGM informatiemodel |
|     | ggm-uml-type | UML type van relatie |
|     | toelichting |     |

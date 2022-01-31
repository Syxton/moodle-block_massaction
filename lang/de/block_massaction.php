<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
$string['pluginname'] = 'Stapeloperationen';
$string['massaction:addinstance'] = 'Stapeloperationen-Block hinzufügen';
$string['massaction:use'] = 'Benutzung des Stapeloperationen-Blocks';
$string['blockname'] = 'Stapeloperationen';
$string['blocktitle'] = 'Stapeloperationen';
$string['privacy:metadata'] = 'Dieser Block ermöglicht es lediglich, Standard-Aktionen für mehrere Aktivitäten gleichzeitig durchzuführen.
Daher müssen von diesem Block keinerlei Daten gespeichert werden.';

$string['selectall'] = 'Alles auswählen';
$string['selectallinsection'] = 'Alles in Abschnitt auswählen';
$string['deselectall'] = 'Alles abwählen';
$string['withselected'] = 'Mit ausgewählten Aktivitäten';
$string['sectionselect'] = 'Abschnittsauswahl';
$string['sectionselect_help'] = 'Sie können nur Abschnitte auswählen, die Aktivitäten enthalten.
Im Kachel- und Ein-Themen-Format können Sie außerdem nur die Abschnitte auswählen, die aktuell zu sehen sind.';

$string['action_delete'] = 'Löschen';
$string['action_duplicate'] = 'Duplizieren';
$string['action_hide'] = 'Verbergen';
$string['action_makeavailable'] = 'Verfügbar machen';
$string['action_moveleft'] = 'Nach links verschieben';
$string['action_moveright'] = 'Nach rechts verschieben';
$string['action_movetosection'] = 'In Abschnitt verschieben';
$string['action_show'] = 'Anzeigen';
$string['backgroundtaskinformation'] = 'Die von Ihnen gewünschte Aktion wird aus Performancegründen im Hintergrund ausgeführt. Sie können in der Zwischenzeit weiterarbeiten.';
$string['deletecheck'] = 'Löschen mehrerer Aktivitäten bestätigen';
$string['deletecheckconfirm'] = 'Sind Sie sicher, dass Sie die folgenden Aktivität(en) löschen möchten?';
$string['duplicatemaxactivities'] = 'Maximale Anzahl Aktivitäten für Duplizierung';
$string['duplicatemaxactivities_description'] = 'Die maximale Anzahl an Aktivitäten, die im Stapeloperationen-Block direkt ohne Hintergrund-Task dupliziert werden. Wenn auf "0" gesetzt, wird der Duplizierungsvorgang immer als Hintergrund-Task ausgeführt.';
$string['invalidaction'] = 'Unbekannte Aktion: {$a}';
$string['invalidmoduleid'] = 'Ungültige Modul-ID: {$a}';
$string['invalidcoursemodule'] = 'Ungültiges Kursmodul';
$string['invalidcourseid'] = 'Ungültige Kurs-ID';
$string['jsonerror'] = 'Programmierfehler: Ungültiges JSON-format.';
$string['modulename'] = 'Aktivitätsname';
$string['moduletype'] = 'Aktivitätstyp';
$string['noitemselected'] = 'Bitte wählen Sie mindestens eine Aktivität aus, auf die die gewählte Aktion angewendet werden soll';
$string['noaction'] = 'Keine Aktion ausgewählt';
$string['noactionsavailable'] = 'Sie haben keine Berechtigungen, Aktivitäten, die von diesem Block bereitgestellt werden, durchzuführen.';
$string['nomovingtargetselected'] = 'Bitte wählen Sie einen Ziel-Abschnitt';
$string['sectionnotexist'] = 'Ziel-Abschnitt existiert nicht';
$string['unusable'] = 'Stapeloperationen stehen in diesem Kurs-Format oder diesem Theme nicht zur Verfügung';
$string['usage'] = 'Benutzung des Stapeloperationen-Blocks';
$string['usage_help'] = <<<EOB
<p>Dieser Block ermöglicht es Trainer/innen, Aktionen für mehrere Aktivitäten im Bearbeitungsmodus durchzuführen statt sie einzeln auf jede Aktivität anzuwenden.</p>
<p>Um diesen Block zu benutzen, muss im Browser Javascript aktiviert sein und sich der Kurs im Bearbeitungsmodus befinden. Aktuell unterstützte Kurs-Formate sind Wochenformat, Themenformat, Einklappbare Abschnitte, Ein-Themen-Format und Kachelformat.</p>
<p>Unterstützte Aktionen sind Löschen, Einrücken (Verschieben nach links/rechts), Verbergen/Anzeigen und Verschieben in andere Abschnitte.
Um Aktivitäten auszuwählen, setzen Sie einfach den Haken in der Checkbox links neben der jeweiligen Aktivität. Alternativ können Sie alle Aktivitäten im Kurs oder eines
Abschnitts über die Links im Stapelverarbeitungsblock aus- bzw. abwählen.</p>
<p>Um dann eine Aktion darauf anzuwenden, klicken Sie einfach auf die jeweilige Aktion im Stapelverarbeitungs-Block.</p>
EOB;

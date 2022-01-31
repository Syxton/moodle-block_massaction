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
$string['pluginname'] = 'Bloc Actions en Lot';
$string['massaction:use'] = 'Utiliser le bloc Actions en Lot';
$string['blockname'] = 'Actions en Lot';
$string['blocktitle'] = 'Actions en Lot';
$string['usage'] = 'Aide Actions en Lot';

$string['select'] = 'Selectionner';
$string['selectall'] = 'Tout sélectionner';
$string['itemsin'] = 'item(s)';
$string['allitems'] = 'Sélectionner tous les items :';
$string['deselect'] = 'Désélectionner';
$string['deselectall'] = 'Tout désélectionner';
$string['withselected'] = 'Pour ce qui est sélectionné';

$string['action_moveleft'] = 'Diminuer le retrait (déplacer vers la gauche)';
$string['action_moveright'] = 'Faire un retrait (déplacement vers la droite)';
$string['action_hide'] = 'Masquer';
$string['action_show'] = 'Montrer';
$string['action_delete'] = 'Supprimer';
$string['action_moveto'] = 'Déplacer';
$string['action_movetosection'] = 'Déplacer dans la section :';

$string['week'] = 'S';
$string['topic'] = 'Section';
$string['section'] = 'Section';
$string['section_zero'] = 'Général';
$string['selecttarget'] = 'Merci de sélectionner une section cible pour y déplacer des éléments';
$string['noitemselected'] = 'Merci de sélectionner au moins un élément pour appliquer l\'action en lot';



$string['noaction'] = 'Pas d\'action spécifiée';
$string['invalidaction'] = 'Action inconnue : {$a}';
$string['invalidmoduleid'] = 'Id de l\'élément invalide : {$a}';
$string['invalidcoursemodule'] = 'Elément de cours invalide';
$string['invalidcourseid'] = 'Id de cours invalide';
$string['deletecheck'] = 'Confirmer la suppression en lot';
$string['deletecheckpreconfirm'] = 'Etes-vous sûr de vouloir supprimer le(s) élément(s) suivant(s) ?';
$string['deletecheckconfirm'] = 'Etes-vous VRAIMENT sûr de vouloir supprimer le(s) élément(s) suivant(s) ?';
$string['sectionnotexist'] = 'La section cible n\'existe pas';
$string['missingparam'] = 'Erreur de code : il manque le paramètre JSON "{$a}"';

$string['usage_help'] = <<< EOB
<p></p>
<p> Ce bloc permet aux enseignants d'effectuer des actions sur les ressources multiples ou des activités dans l'espace de cours, plutôt que d'avoir à effectuer des actions répétées sur des éléments individuels. </p>
<p> Pour utiliser ce bloc, Javascript doit être activé dans votre navigateur, vous devez être en mode édition dans la page d'accueil du cours. Seuls les cours au format HEBDOMADAIRE ou THEMATIQUE sont pris en charge. </p>
<p>Les actions prises en charge sont : <br>
<ul>
<li>la suppression en lot</li>
<li>le masquage en lot</li>
<li>l'affichage en lot</li>
<li>le déplacement en lot</li>
</ul>
Pour sélectionner un élément pour y effectuer une action, il suffit de cliquer sur la case à droite de celui-ci sur la page du cours, ou vous pouvez sélectionner tous les éléments, ou sélectionner tous les éléments dans une section à l'aide du bloc. Pour effectuer une action, cliquez sur l'action que vous souhaitez effectuer à l'intérieur du bloc. </p>
EOB;

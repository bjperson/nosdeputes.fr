<?php
$surtitre = "Graphes d'activité parlementaire";
$fin = myTools::isFinLegislature();
if ($fin) $test = 'legislature';
else {
  $test = 'lastyear';
  $mois = min(12, floor((time() - strtotime($parlementaire->debut_mandat) ) / (60*60*24*30)));
  $txtmois = ($mois < 2 ? " premier" : "s $mois ".($mois < 12 ? "prem" : "dern")."iers");
}
if ($session == $test) {
 if ($fin) $titre = 'Sur toute la législature';
 else $titre = "Sur le$txtmois mois";
} else $titre = 'Sur la session '.preg_replace('/^(\d{4})/', '\\1-', $session);
$sf_response->setTitle($surtitre.' de '.$parlementaire->nom.' '.strtolower($titre));
echo include_component('parlementaire', 'header', array('parlementaire' => $parlementaire, 'titre' => $surtitre));
?>
<div class="par_session"><p>
<?php if ($session != $test)
  echo '<a href="'.url_for('@parlementaire_plot?slug='.$parlementaire->slug.'&time='.$test).'">';
  else echo '<b>';
  if ($fin) echo 'Toute la législature';
  else echo "Le$txtmois mois";
  if ($session != $test) echo '</a>';
  else echo '</b>';
  foreach ($sessions as $s) {
  echo ', ';
  if ($session != $s['session']) echo '<a href="'.url_for('@parlementaire_plot?slug='.$parlementaire->slug.'&time='.$s['session']).'">';
  else echo '<b>';
  echo 'la session '.preg_replace('/^(\d{4})/', '\\1-', $s['session']);
  if ($session != $s['session']) echo '</a>';
  else echo '</b>';
  } ?>
</p></div>

<?php echo include_component('plot', 'parlementaire', array('parlementaire' => $parlementaire, 'options' => array('plot' => 'all', 'questions' => 'true', 'session' => $session))); ?>
  <div class="explications" id="explications">
    <h2>Explications :</h2>
    <?php //echo link_to("Présence en réunions de commission et séances d'hémicycle",'@parlementaire_presences?slug='.$parlementaire->getSlug()); ?>
    <p class="indent_guillemets"><a href="/faq">voir les questions fréquentes (rubrique FAQ)</a></p>
  </div>

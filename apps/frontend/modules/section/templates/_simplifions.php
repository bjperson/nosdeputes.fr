<div>
<h2>Les dossiers en discussion sur NosDéputés.fr</h2>
<ul>
<?php foreach($lois as $l) : ?>
<li><?php echo link_to(strip_tags($l['titre']), "@loi?loi=".$l['texteloi_id']); if ($l['nb_commentaires'] > 0) { echo ' ('.$l['nb_commentaires'].' commentaire'; if ($l['nb_commentaires'] > 1) echo 's'; echo ')'; } ?></li>
<?php endforeach; ?>
</ul>
</div>
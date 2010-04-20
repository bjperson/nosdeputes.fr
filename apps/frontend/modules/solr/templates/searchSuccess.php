<?php

function link_search($text, $query, $args) 
{
  $extra = '';
  $url = "solr/search?query=".$query;
  foreach($args as $k => $v) {
    if (count($v))
      $extra .= '&'.$k.'='.implode(',', array_keys($v));
  }
  return link_to($text, $url.$extra);
}
?>
<div class="searchbox">
<form>
    <input name="search" value="<?php echo $query; ?>" />
    <input type="submit" value="Rechercher"/>
</form>
</div>
<div class="nb_results">
<p>Résultats <?php echo $results['start']+1; ?> à <?php echo $results['end']-1; ?> sur <?php echo $results['numFound']; ?></p>
</div>
<div class="facets">
<?php   foreach(array_keys($facet) as $k) { if (isset($facet[$k]['values']) && count($facet[$k]['values'])) : ?>
<div class="<?php echo $k; ?>">
<p><?php echo $facet[$k]['name']; ?></p>
<ul>
<?php foreach($facet[$k]['values'] as $value => $nb) : if ($nb) :
$is_selected = isset($selected[$facet[$k]['facet_field']][$facet[$k]['prefix'].$value]) && 
		     $selected[$facet[$k]['facet_field']][$facet[$k]['prefix'].$value];
?>
<li<?php if ($is_selected) echo ' class="selected"'; ?>><?php 
$newargs = $selected;
if ($is_selected) 
  unset($newargs[$facet[$k]['facet_field']][$facet[$k]['prefix'].$value]);
else			      
  $newargs[$facet[$k]['facet_field']][$facet[$k]['prefix'].$value] = 1;
echo link_search($value, 
		 $query, 
		 $newargs
		 ); ?> (<?php echo $nb; ?>)</li>
<?php endif; endforeach; ?>
</ul>
</div>
<?php endif; } ?>
<div class="date">
<p>Dates</p>
<ul>
   <?php foreach($fdates['values'] as $date => $nb) : ?>
    <li><?php 
    $newargs = $selected;
    $newargs['date'][$date] = 1;
    echo link_search($date,
		     $query,
		     $newargs); 
?> (<?php echo $nb['nb'].' '; printf('%02d', $nb['pc']*100/($fdates['max']));?>%)</li>
<?php endforeach; ?>
</ul>
</div>
<div class="results">
<?php foreach ($results['docs'] as $record) : ?>
<div class="item">
   <h4><?php echo $record['id']; ?></h4>
   <p class="content"><?php echo $record['highlighting']; ?></p>
</div>
<div class="record">
</div>
<?php endforeach; ?>
</div>
<div class="pager">
<div class="next">
<?php
if ($results['end']-1 != $results['numFound']) {
  $newargs = $selected;
  $newargs['page'][$results['page'] + 1] = 1;
  echo link_search('page suivante',
		   $query,
		   $newargs); 
}
?>
</div>
<div class="last">
<?php
$newargs = $selected;
$newargs['page'][$results['page'] - 1] = 1;
if ($results['page'] > 1) {
  if (isset($newargs['page'][1]))
    unset($newargs['page'][1]);
  echo link_search('page précédente',
		   $query,
		   $newargs); 
 }
?>
</div>
</div>
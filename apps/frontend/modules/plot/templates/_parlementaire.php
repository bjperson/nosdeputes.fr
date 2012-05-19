<div id="overDiv"></div>
<?php $plotarray = array('parlementaire' => $parlementaire, 'time' => 'lastyear', 'questions' => 'false', 'link' => 'false', 'absolute' => false, 'widthrate' => 1);
if (isset($options['session'])) $plotarray['time'] = $options['session'];
if (isset($options['questions'])) $plotarray['questions'] = $options['questions'];
if (isset($options['link'])) $plotarray['link'] = $options['link'];
if (isset($options['absolute'])) $plotarray['absolute'] = $options['absolute'];
if (isset($options['widthrate'])) $plotarray['widthrate'] = $options['widthrate'];

if ($options['plot'] == 'all' || $options['plot'] == 'total') {
  $plotarray = array_merge($plotarray, array('type' => 'total'));
  echo include_partial('plot/plotParlementaire', $plotarray);
}
if ($options['plot'] == 'all' || $options['plot'] == 'hemicycle') {
  if (!isset($plotarray['type']))
    $plotarray = array_merge($plotarray, array('type' => 'hemicycle'));
  else $plotarray['type'] = 'hemicycle';
  echo include_partial('plot/plotParlementaire', $plotarray);
}
if ($options['plot'] == 'all' || $options['plot'] == 'commission') {
  if (!isset($plotarray['type']))
    $plotarray = array_merge($plotarray, array('type' => 'commission'));
  else $plotarray['type'] = 'commission';
  echo include_partial('plot/plotParlementaire', $plotarray);
} ?>

<?php

class loadHemicyleTask extends sfBaseTask
{
  protected function configure()
  {
    $this->namespace = 'load';
    $this->name = 'Hemicycle';
    $this->briefDescription = 'Load Hemicycle data';
  }
 
  protected function execute($arguments = array(), $options = array())
  {
    // your code here
    $dir = dirname(__FILE__).'/../../batch/hemicycle/out/';
    $manager = new sfDatabaseManager($this->configuration);    

    if (is_dir($dir)) {
      if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
	  $sections = array();
	  if (preg_match('/^\./', $file))
	    continue;
	  echo "$dir$file\n";
	  foreach(file($dir.$file) as $line) {
	    $json = json_decode($line);
	    if (!$json || !$json->intervention || !$json->date || !$json->heure || !$json->source) {
	      echo "ERROR json : ";
	      echo $line;
	      echo "\n";
	      continue;
	    }
	    $id = md5($json->intervention.$json->date.$json->heure.'hemicyle'.$json->context.$json->timestamp);
	    $intervention = Doctrine::getTable('Intervention')->findOneByMd5($id);
	    if(!$intervention) {
	      $intervention = new Intervention();
	      $intervention->md5 = $id;
	      $intervention->setIntervention($json->intervention);
	      if (preg_match('/question/i', $json->contexte))
    		$type = 'question';
	      else
        	$type = 'loi';
	      $intervention->date = $json->date;
	      $intervention->setSeance($type, $json->date, $json->heure, $json->session);
	      $intervention->setSource($json->source);
	      $intervention->setTimestamp($json->timestamp);
          if ($json->timestamp)
            $intervention->setContexte($json->contexte, $json->date.$json->heure, $json->timestamp);
	    }
	    if ($json->numeros_loi)
	      $intervention->setLois($json->numeros_loi);
	    if ($json->amendements)
	      $intervention->setAmendements($json->amendements);
	    if ($json->intervenant) {
	      $p = null;
	      if ($json->intervenant_url) {
            $p = doctrine::getTable('Parlementaire')
                  ->findOneByUrlAn($json->intervenant_url);
            if ($p) {
              $intervention->setParlementaire($p);
              $intervention->setFonction($json->fonction);
            }
	      }
	      if (!$p) {
            $intervention->setPersonnaliteByNom($json->intervenant, $json->fonction);
	      } else $p->free();
	    }
	    $intervention->save();
	    if (!isset($sections[$intervention->getSection()->id]))
	      $sections[$intervention->getSection()->id] = $intervention->getSection();
	    $intervention->free();
	  }
	  foreach(array_values($sections) as $section)
	    $section->updateNbInterventions();
	  if ($section) {
	    $section->free();
	  }
	  unset($sections);
	  unlink($dir.$file);
	}
        closedir($dh);
      }
    }
  }
}

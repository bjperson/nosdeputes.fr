<?php

class sendAlertTask extends sfBaseTask
{
  protected function configure()
  {
    $this->namespace = 'send';
    $this->name = 'Alert';
    $this->briefDescription = 'send alerts';
    $this->addOption('env', null, sfCommandOption::PARAMETER_OPTIONAL, 'Changes the environment this task is run in', 'test');
    $this->addOption('app', null, sfCommandOption::PARAMETER_OPTIONAL, 'Changes the environment this task is run in', 'frontend');
  }
 
  protected function execute($arguments = array(), $options = array())
  {
    $this->configuration = sfProjectConfiguration::getApplicationConfiguration($options['app'], $options['dev'], true);
    $manager = new sfDatabaseManager($this->configuration);
    $context = sfContext::createInstance($this->configuration);
    $this->configuration->loadHelpers('Partial');
    
    $solr = new SolrConnector();
    $query = Doctrine::getTable('Alerte')->createQuery('a')->where('next_mail < NOW()');
    foreach($query->execute() as $alerte) {
      $date = strtotime(preg_replace('/ /', 'T', $alerte->last_mail)."Z")+1-3600;
      $query = $alerte->query." date:[".date('Y-m-d', $date).'T'.date('H:i:s', $date)."Z TO NOW]";
      $results = $solr->search($query, array('sort' => 'date desc', 'hl' => 'yes', 'hl.fragsize'=>500));
      echo "$query\n";
      if (! $results['response']['numFound'])
	continue;
      echo "sending mail to : ".$alerte->email."\n";
      $message = $this->getMailer()->compose(array('no-reply@nosdeputes.fr' => 'Regards Citoyens (ne pas répondre)'), 
					     $alerte->email,
					     '[NosDeputes.fr] Alerte - '.$alerte->titre);
      echo $alerte->titre."\n";
      $text = get_partial('mail/sendAlerteTxt', array('alerte' => $alerte, 'results' => $results['response']));
      $message->setBody($text, 'text/plain');
      try {
	$this->getMailer()->send($message);
	$alerte->last_mail = preg_replace('/T/', ' ', preg_replace('/Z/', '', $results['response']['docs'][$results['response']['numFound'] -1]['date']));
	$alerte->save();
      }catch(Exception $e) {
	echo "ERROR: mail could not be sent ($text)\n";
      }
    }
  }
  
}
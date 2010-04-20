<?php

/**
 * solr actions.
 *
 * @package    cpc
 * @subpackage solr
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12479 2008-10-31 10:54:40Z fabien $
 */
class solrActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfRequest $request A request object
  */
  public function executeSearch(sfWebRequest $request)
  {
    if ($search = $request->getParameter('search')) {
      return $this->redirect('solr/search?query='.$search);
    }
    $this->query = $request->getParameter('query');
    
    $query = preg_replace('/\*/', '', $this->query);

    if (!strlen($query)) {
      $query = '*';
    }

    $nb = 20;
    $deb = ($request->getParameter('page', 1) - 1) * $nb ;
    $fq = '';
    $this->facet = array();

    $this->selected = array();
    if ($on = $request->getParameter('object_name')) {
      $this->selected['object_name'][$on] = 1;
      $fq .= " object_name:$on";
    }
    if ($tags = $request->getParameter('tag')) {
      foreach(explode(',', $tags) as $tag) {
	$this->selected['tag'][$tag] = 1;
	$fq .= ' tag:"'.$tag.'"';
      }
    }
    //Récupère les résultats auprès de SolR
    $s = new SolrConnector();
    $params = array('hl'=>'true', 'fl' => 'id,object_id,object_name', 'hl.fragsize'=>500, "facet"=>"true", "facet.field"=>array("object_name","tag"), "facet.date" => "date", "facet.date.start"=>"2007-05-01T00:00:00Z", "facet.date.end"=>"NOW", "facet.date.gap"=>"+1MONTH", 'fq' => $fq);
    if ($date = $request->getParameter('date')) {
      $dates = explode(',', $date);
      $date = array_pop($dates);
      $period = 'MONTH';
      if (count($dates) == 1)
	$period = 'DAY';
      $query .= ' date:['.$date.' TO '.$date.'+1'.$period.']';
      $this->selected['date'][$date] = 1;
      $params['facet.date.start']=$date;
      $params['facet.date.end'] = $date.'+1'.$period;
      $params['facet.date.gap'] = '+1DAY';
    }
    $results = $s->search($query, $params, $deb, $nb);
    //Reconstitut les résultats
    $this->results = $results['response'];
    for($i = 0 ; $i < count($this->results['docs']) ; $i++) {
      $res = $this->results['docs'][$i];
      $this->results['docs'][$i]['highlighting'] = implode('...', $results['highlighting'][$res['id']]['text']);
      //      $this->results['docs'][$i]['object'] = doctrine::getTable($res['object_name'])->find($res['object_id']);
    }
    $this->results['end'] = $deb + $nb;
    $this->results['page'] = $deb/$nb + 1;
    if ($this->results['end'] > $this->results['numFound']) {
      $this->results['end'] = $this->results['numFound'] + 1;
    }

    //Prépare les facets
    $this->facet['type']['prefix'] = '';
    $this->facet['type']['facet_field'] = 'object_name';
    $this->facet['type']['name'] = 'Types';
    $this->facet['type']['values'] = $results['facet_counts']['facet_fields']['object_name'];

    $tags = $results['facet_counts']['facet_fields']['tag'];
    $this->facet['tag']['prefix'] = '';
    $this->facet['tag']['facet_field'] = 'tag';
    $this->facet['tag']['name'] = 'Tags';
    $this->facet['parlementaire']['prefix'] = 'parlementaire=';
    $this->facet['parlementaire']['facet_field'] = 'tag';
    $this->facet['parlementaire']['name'] = 'Parlementaire';
    foreach($tags as $tag => $nb ) {
      if (!$nb)
	continue;
      if (!preg_match('/=/', $tag))
	$this->facet['tag']['values'][$tag] = $nb;
      if (preg_match('/^parlementaire=(.*)/', $tag, $matches)) {
	$this->facet['parlementaire']['values'][$matches[1]] = $nb;
      }
    }
    if ($results['response']['numFound']) {
      $this->fdates = array();
      $this->fdates['max'] = 1;
      foreach($results['facet_counts']['facet_dates']['date'] as $date => $nb) {
	if (preg_match('/^20/', $date)) {
	  $pc = $nb/$results['response']['numFound'];
	  $this->fdates['values'][$date] = array('nb' => $nb, 'pc' => $pc);
	  if ($this->fdates['max'] < $pc) {
	    $this->fdates['max'] = $pc;
	  }
	}
      }
    }
  }
}
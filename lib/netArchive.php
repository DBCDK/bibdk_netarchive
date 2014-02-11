<?php
require_once("session_start.phpi");
require_once("search_func.phpi");
require_once("XMLparser.phpi");
require_once("session_func.phpi");
require_once("soap_func.phpi");
require_once("template_func.phpi");
require_once("curl_class.phpi");

// get url parameters
($_GET['lid']?$lid=$_GET['lid']:$lid=26082951);
($_GET['lok']?$lok='DK-'.$_GET['lok']:$lok='DK-870970');

$tmp_name = WEB_CACHE_PATH . 'webarkiv_' . md5(session_id() . microtime());
register_shutdown_function(
  create_function('', 
    "unlink('{$tmp_name}.pdf'); 
     unlink('{$tmp_name}_arkiv.pdf'); 
     unlink('{$tmp_name}.json');"
  )
); 

// description and publisher
$dfa=new dfa($lid);

// url to publisher
$netarkiv = new netarkiv();
if ( $url = $netarkiv->get_url_by_publisher($dfa->publisher()) ) {
} elseif ( $faust=$dfa->vp_nr() ) {
  $url = $netarkiv->get_url_by_faust($faust);
}  
// fetch pdf into tmp
$curl = new curl();
$curl->set_url(addi::get_pdf($lid,$lok));
$pdf_content = $curl->get();
file_put_contents($tmp_name . '_arkiv.pdf', $pdf_content);

// Construct the pdf (including the frontpage)
$forside_page = get_template('pdf_forside.json');
load_lang_text($THIS);
$forside_page->setVar('pdffile', $tmp_name . '_arkiv.pdf');
$forside_page->setVar('forklaede_data', addslashes(utf8_decode(html_entity_decode($dfa->data(), ENT_NOQUOTES, 'UTF-8'))));
//$forside_page->setVar("forlag",$dfa->publisher());
//$forside_page->setVar("url",$url);
file_put_contents($tmp_name . '.json', grab_template($forside_page));

// Construct the frontpage, and merge the two pages to one
exec("/var/www/php_exec/webarkiv.py $tmp_name.json", $exec_output, $exec_error);
if ($exec_error) die("Error: $exec_error");

header("Cache-Control:  maxage=1");
header("Pragma: public");
header("Content-Type: application/pdf");
header("Content-Disposition attachment; filename=webarkiv.pdf");
$resulting_pdf = fopen($tmp_name . '.pdf', 'r');
fpassthru($resulting_pdf);
fclose($resulting_pdf);
//unlink($tmp_name . '_arkiv.pdf');
//unlink($tmp_name . '.pdf');
//unlink($tmp_name . '.json');

class dfa
{
  private $target;
  private $search;
  private $record;

  public function __construct($lid)
  {
    global $TARGET;
        
    $this->search=&$TARGET['dfa'];
    $ccl='(lid='.$lid.')';
    $this->search["ccl"]=$ccl;
  
    // do the actual z3950 search to get results in searcharray
    Zsearch($this->search);

    $this->record = XmlToArray($this->search['records'][1]['record']); 
   }

  public function data()
  {
    $data = $this->record['work-records'][0]['manifestation'][0]['xml'][0]['ark_format'][0]['value'];
    return $data;
  }
  
  public function vp_nr()
  {
    $vp_nr=$this->record['work-records'][0]['manifestation'][0]['xml'][0]['vp_nr'][0]['value'];
    return $vp_nr;
  }


  public function publisher()
  {
    $publisher=$this->record['work-records'][0]['manifestation'][0]['xml'][0]['forlag'][0]['value'];
    return $publisher;   
  }

 
}

class addi
{
  
  public static function get_pdf($lid,$lok)
  {
    global $ADDI_USER;
    global $ADDI_GROUP;
    global $ADDI_PASSWORD;

    load_lang_text("addi_webservice", "addi_webservice", "dan");
    $ws_url=ADDI_URL;
    $soap_http = array();
    $soap_http["url"] = ADDI_URL;
    $soap_http["timeout"] = 500;
    $soap_http["SOAPAction"] = 'addi';
    $addi_soap_identifiers .= getext("addi_soap_identifiers", $lid, $lok); // indtil ADDI er fix'et
    $soap_http["post"] = getext("addi_soap_query", $ADDI_USER, $ADDI_GROUP, $ADDI_PASSWORD, $addi_soap_identifiers);

    if ( !soap_exec($soap_http) ) {
       die("forsideservice: No reply");
      return false;
    }
    
    $xml=$soap_http['reply'];

    $url = self::get_url($xml);
    if( $url )
      return $url;
    else
      return false;
  }


  private static function get_url(&$xml)
  {
    $dom=new DOMDocument();
    $dom->loadXML($xml);
    
    $xpath=new DOMXPath($dom);
    $query="/mi:moreInfoResponse/mi:identifierInformation/mi:netArchive";
    
    $nodelist=$xpath->query($query);
    
    return $nodelist->item(0)->nodeValue; 
    
  }  
}

class netarkiv
{
  private $search;

  public function __construct()
  {
    global $TARGET;
    $this->search=$TARGET['netarkiv'];
  }

 
  public function get_url_by_publisher($publisher)
  {
    if( !isset($publisher) )
      return false;

    //   echo $publisher;
  

    unset($this->search['rpn']);
    $pub=html_entity_decode($publisher);

    $patterns=array();
    $patterns[0]="/^Den/";
    $patterns[1]="/^den/";
    $patterns[2]="/^Det/";
    $patterns[3]="/^det/";
    $patterns[4]="/^The/";
    $patterns[5]="/^the/";

    // only remove first occurence of patterns
    $pub=preg_replace($patterns,"",$pub,1);

    $this->search['ccl']='lfl='.$pub;
    Zsearch($this->search);

    $result=XmlToArray($this->search['records'][1]['record']);
 
    $url=$result['xml'][0]['value'];

  
    return $this->fixurl($url);   
  }

  public function get_url_by_faust($faust)
  {
    unset($this->search['rpn']);
    $this->search['ccl']='iv='.$faust;

    Zsearch($this->search);

    $result=XmlToArray($this->search['records'][1]['record']);
    $url=$result['xml'][0]['value'];
    
    return $this->fixurl($url);    
  }

  private function fixurl($url=null)
  {
     // some url's does not start with 'http' - they should
    if( $url )
      {
	$protocol=substr($url,0,4);
	if( $protocol!='http' )
	  $url='http://'.$url;

      }	
    return $url;
  }
  
}
?>
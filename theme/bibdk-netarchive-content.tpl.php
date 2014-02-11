<h1>Kopi fra DBC Webarkiv.</h1>


<p>Kopi af:<br/>
  <?php
    if ( !empty($elements['#bibdk_netarchive_data']['creator']) ) {
      $elements['#bibdk_netarchive_data']['creator'] = $elements['#bibdk_netarchive_data']['creator'] . ' : ';
    }
    echo $elements['#bibdk_netarchive_data']['creator'] . $elements['#bibdk_netarchive_data']['title'];
  ?>
</p>


<p>Dette materiale er lagret i henhold til aftale med udgiveren.<br/>
www.dbc.dk<br/>
e-mail: dbc@dbc.dk
</p>


<object data="<?php echo $elements['#bibdk_netarchive_src']; ?>?page=1&amp;view=Fit" type="application/pdf" width="650" height="900">
  <p>It appears you don't have a PDF plugin for this browser.
     No biggie... you can <a href="<?php echo $elements['#bibdk_netarchive_src']; ?>">click here to download the PDF file.</a></p>
</object>

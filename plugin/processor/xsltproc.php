<?php
// Copyright 2003 by Won-Kyu Park <wkpark at kldp.org>
// All rights reserved. Distributable under GPL see COPYING
// a xml processor plugin for the MoniWiki
//
// Usage: {{{#!xsltproc
// xml codes
// }}}
// $Id$

function processor_xsltproc($formatter,$value) {
  global $DBInfo;
  $xsltproc = "xsltproc ";
  if ($value[0]=='#' and $value[1]=='!') {
    list($line,$value)=explode("\n",$value,2);
    # get parameters
    list($tag,$args)=explode(" ",$line,2);
  }

  $pagename=$formatter->page->name;

  $cache= new Cache_text("docbook");

  if (!$formatter->preview and !$formatter->refresh and $cache->exists($pagename) and $cache->mtime($pagename) > $formatter->page->mtime())
    return $cache->fetch($pagename);

  list($line,$body)=explode("\n",$value,2);
  $buff="";
  while(($line[0]=='<' and $line[1]=='?') or !$line) {
    preg_match("/^<\?xml-stylesheet\s+href=\"([^\"]+)\"/",$line,$match);
    if ($match) {
      if ($DBInfo->hasPage($match[1]))
        $line='<?xml-stylesheet href="'.getcwd().'/'.$DBInfo->text_dir.'/'.$match[1].'" type="text/xml"?>';
      $flag=1;
    }
    $buff.=$line."\n";
    list($line,$body)=explode("\n",$body,2);
    if ($flag) break;
  }
  $src=$buff.$line."\n".$body;

  $tmpf=tempnam("/tmp","XSLT");
  $fp= fopen($tmpf, "w");
  fwrite($fp, $src);
  fclose($fp);

  $cmd="$xsltproc --xinclude $tmpf";

  $fp=popen($cmd,"r");
  #fwrite($fp,$src);

  while($s = fgets($fp, 1024)) $html.= $s;

  pclose($fp);
  unlink($tmpf);

  if (!$html) {
    $src=str_replace("<","&lt;",$value);
    $cache->remove($pagename);
    return "<pre class='code'>$src\n</pre>\n";
  }

  if (function_exists ("iconv") and strtoupper($DBInfo->charset) != 'UTF-8') {
    $new=iconv('UTF-8',$DBInfo->charset,$html);
    if ($new) $html=$new;
  }

  if (!$formatter->preview)
    $cache->update($pagename,$html);

  return $html;
}

// vim:et:sts=2:sw=2
?>
